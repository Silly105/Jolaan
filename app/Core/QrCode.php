<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 100 % nativer, lokal in PHP implementierter SVG-QR-Code-Generator nach ISO/IEC 18004
 * (Byte-Modus, Fehlerkorrekturlevel L, Versionen 1 bis 10).
 * Benötigt keinerlei externe Bibliotheken, keine GD-Extension und keine externen Server-Aufrufe.
 */
class QrCode
{
    /**
     * Kapazitätstabelle für Fehlerkorrekturlevel L (Version => [total_data_codewords, ec_codewords_per_block, num_blocks_g1, data_per_block_g1, num_blocks_g2, data_per_block_g2])
     */
    private const VERSION_LEVEL_L = [
        1  => [19,  7,  1, 19, 0, 0],
        2  => [34,  10, 1, 34, 0, 0],
        3  => [55,  15, 1, 55, 0, 0],
        4  => [80,  20, 1, 80, 0, 0],
        5  => [108, 26, 1, 108, 0, 0],
        6  => [136, 18, 2, 68, 0, 0],
        7  => [156, 20, 2, 78, 0, 0],
        8  => [194, 24, 2, 97, 0, 0],
        9  => [232, 30, 2, 116, 0, 0],
        10 => [274, 18, 2, 68, 2, 69],
    ];

    /**
     * Positionen der Alignment-Patterns für Version 1..10
     */
    private const ALIGNMENT_POS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Vorberechnete 15-Bit Format-Informationen für Level L (01) und Masken 0..7 (nach BCH & XOR mit 0x5412)
     */
    private const FORMAT_BITS_L = [
        0 => 0x77C4,
        1 => 0x72F3,
        2 => 0x7DAA,
        3 => 0x789D,
        4 => 0x662F,
        5 => 0x6318,
        6 => 0x6C41,
        7 => 0x6976,
    ];

    /**
     * Vorberechnete 18-Bit Versions-Informationen für Version 7..10
     */
    private const VERSION_BITS = [
        7  => 0x07C94,
        8  => 0x085BC,
        9  => 0x09A99,
        10 => 0x0A4D3,
    ];

    /**
     * Erzeugt ein gestochen scharfes Inline-<svg> für den übergebenen Text (z. B. otpauth:// URI).
     */
    public static function renderSvg(string $text, int $pixelSize = 180, int $margin = 2): string
    {
        $matrix = self::encodeMatrix($text);
        $size   = count($matrix);
        $total  = $size + ($margin * 2);

        $path = '';
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix[$y][$x] === 1) {
                    $px = $x + $margin;
                    $py = $y + $margin;
                    $path .= "M{$px},{$py}h1v1h-1z";
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;display:block;">'
            . '<rect width="100%%" height="100%%" fill="#ffffff"/>'
            . '<path d="%s" fill="#0f172a"/>'
            . '</svg>',
            $total,
            $total,
            $pixelSize,
            $pixelSize,
            $path
        );
    }

    /**
     * Kodiert einen String in eine 2D-QR-Matrix (0 = hell, 1 = dunkel).
     */
    public static function encodeMatrix(string $data): array
    {
        $bytes = array_values(unpack('C*', $data));
        $len   = count($bytes);

        // Passende QR-Version (1..10) wählen
        $version = 1;
        foreach (self::VERSION_LEVEL_L as $v => $cfg) {
            $maxDataWords = $cfg[0];
            $headerBytes  = ($v <= 9) ? 2 : 3; // 4 Bit Mode + 8/16 Bit Länge
            if ($len + $headerBytes <= $maxDataWords) {
                $version = $v;
                break;
            }
        }

        [$totalDataWords, $ecWordsPerBlock, $numG1, $dataG1, $numG2, $dataG2] = self::VERSION_LEVEL_L[$version];

        // 1. Bitstrom für Byte-Modus (0100) aufbauen
        $bits = [0, 1, 0, 0];
        $lenBits = ($version <= 9) ? 8 : 16;
        for ($i = $lenBits - 1; $i >= 0; $i--) {
            $bits[] = ($len >> $i) & 1;
        }
        foreach ($bytes as $b) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($b >> $i) & 1;
            }
        }

        // Terminator (bis zu 4 Null-Bits)
        $maxBits = $totalDataWords * 8;
        for ($i = 0; $i < 4 && count($bits) < $maxBits; $i++) {
            $bits[] = 0;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Codewords aus Bits extrahieren und bis totalDataWords mit 0xEC / 0x11 auffüllen
        $dataWords = [];
        for ($i = 0, $n = count($bits); $i < $n; $i += 8) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bits[$i + $j];
            }
            $dataWords[] = $val;
        }
        $padBytes = [0xEC, 0x11];
        $padIdx   = 0;
        while (count($dataWords) < $totalDataWords) {
            $dataWords[] = $padBytes[$padIdx % 2];
            $padIdx++;
        }

        // 2. Blöcke aufteilen & Reed-Solomon Fehlerkorrektur berechnen
        $dataBlocks = [];
        $ecBlocks   = [];
        $offset     = 0;
        for ($b = 0; $b < $numG1; $b++) {
            $block = array_slice($dataWords, $offset, $dataG1);
            $offset += $dataG1;
            $dataBlocks[] = $block;
            $ecBlocks[]   = self::reedSolomonRemainder($block, $ecWordsPerBlock);
        }
        for ($b = 0; $b < $numG2; $b++) {
            $block = array_slice($dataWords, $offset, $dataG2);
            $offset += $dataG2;
            $dataBlocks[] = $block;
            $ecBlocks[]   = self::reedSolomonRemainder($block, $ecWordsPerBlock);
        }

        // Interleaving der Daten- und EC-Codewords
        $finalWords = [];
        $maxBlockLen = max($dataG1, $dataG2);
        for ($i = 0; $i < $maxBlockLen; $i++) {
            foreach ($dataBlocks as $blk) {
                if (isset($blk[$i])) {
                    $finalWords[] = $blk[$i];
                }
            }
        }
        for ($i = 0; $i < $ecWordsPerBlock; $i++) {
            foreach ($ecBlocks as $blk) {
                $finalWords[] = $blk[$i];
            }
        }

        $finalBits = [];
        foreach ($finalWords as $w) {
            for ($i = 7; $i >= 0; $i--) {
                $finalBits[] = ($w >> $i) & 1;
            }
        }

        // 3. Matrix initialisieren & Funktionsmuster einzeichnen
        $size     = 17 + ($version * 4);
        $matrix   = array_fill(0, $size, array_fill(0, $size, 0));
        $isFunc   = array_fill(0, $size, array_fill(0, $size, false));

        // Finder-Pattern + Separatoren
        self::drawFinderPattern($matrix, $isFunc, 0, 0, $size);
        self::drawFinderPattern($matrix, $isFunc, $size - 7, 0, $size);
        self::drawFinderPattern($matrix, $isFunc, 0, $size - 7, $size);

        // Alignment-Pattern
        $align = self::ALIGNMENT_POS[$version];
        $nAlign = count($align);
        for ($i = 0; $i < $nAlign; $i++) {
            for ($j = 0; $j < $nAlign; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $nAlign - 1) || ($i === $nAlign - 1 && $j === 0)) {
                    continue;
                }
                self::drawAlignmentPattern($matrix, $isFunc, $align[$i], $align[$j]);
            }
        }

        // Timing-Pattern
        for ($i = 8; $i < $size - 8; $i++) {
            $bit = ($i % 2 === 0) ? 1 : 0;
            if (!$isFunc[6][$i]) {
                $matrix[6][$i] = $bit;
                $isFunc[6][$i] = true;
            }
            if (!$isFunc[$i][6]) {
                $matrix[$i][6] = $bit;
                $isFunc[$i][6] = true;
            }
        }

        // Dark Module & Format-Info-Plätze reservieren
        $matrix[(4 * $version) + 9][8] = 1;
        $isFunc[(4 * $version) + 9][8] = true;

        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $isFunc[8][$i] = true;
                $isFunc[$i][8] = true;
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $isFunc[8][$size - 1 - $i] = true;
            $isFunc[$size - 1 - $i][8] = true;
        }

        // Versions-Information (ab Version 7)
        if ($version >= 7) {
            $vBits = self::VERSION_BITS[$version];
            for ($i = 0; $i < 18; $i++) {
                $bit = ($vBits >> $i) & 1;
                $a = intdiv($i, 3);
                $b = ($i % 3) + $size - 11;
                $matrix[$b][$a] = $bit;
                $isFunc[$b][$a] = true;
                $matrix[$a][$b] = $bit;
                $isFunc[$a][$b] = true;
            }
        }

        // 4. Datenbits im Zickzack von rechts unten nach links oben eintragen
        $bitIdx = 0;
        $totalBits = count($finalBits);
        $upward = true;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                $y = $upward ? ($size - 1 - $vert) : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    if (!$isFunc[$y][$x]) {
                        $matrix[$y][$x] = ($bitIdx < $totalBits) ? $finalBits[$bitIdx] : 0;
                        $bitIdx++;
                    }
                }
            }
            $upward = !$upward;
        }

        // 5. Beste Maske (0..7) mit niedrigster Penalty wählen
        $bestMask   = 0;
        $bestMatrix = $matrix;
        $minPenalty = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::applyMaskAndFormat($matrix, $isFunc, $size, $mask);
            $penalty   = self::calculatePenalty($candidate, $size);
            if ($penalty < $minPenalty) {
                $minPenalty = $penalty;
                $bestMask   = $mask;
                $bestMatrix = $candidate;
            }
        }

        return $bestMatrix;
    }

    private static function drawFinderPattern(array &$matrix, array &$isFunc, int $ox, int $oy, int $size): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $x = $ox + $dx;
                $y = $oy + $dy;
                if ($x < 0 || $x >= $size || $y < 0 || $y >= $size) {
                    continue;
                }
                if ($dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6) {
                    $isDark = ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));
                    $matrix[$y][$x] = $isDark ? 1 : 0;
                } else {
                    $matrix[$y][$x] = 0;
                }
                $isFunc[$y][$x] = true;
            }
        }
    }

    private static function drawAlignmentPattern(array &$matrix, array &$isFunc, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $isDark = (max(abs($dx), abs($dy)) !== 1);
                $matrix[$cy + $dy][$cx + $dx] = $isDark ? 1 : 0;
                $isFunc[$cy + $dy][$cx + $dx] = true;
            }
        }
    }

    private static function applyMaskAndFormat(array $matrix, array $isFunc, int $size, int $mask): array
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($isFunc[$y][$x]) {
                    continue;
                }
                $invert = match ($mask) {
                    0 => (($y + $x) % 2 === 0),
                    1 => ($y % 2 === 0),
                    2 => ($x % 3 === 0),
                    3 => (($y + $x) % 3 === 0),
                    4 => (((intdiv($y, 2) + intdiv($x, 3)) % 2) === 0),
                    5 => ((($y * $x) % 2 + ($y * $x) % 3) === 0),
                    6 => (((($y * $x) % 2 + ($y * $x) % 3) % 2) === 0),
                    7 => (((($y + $x) % 2 + ($y * $x) % 3) % 2) === 0),
                };
                if ($invert) {
                    $matrix[$y][$x] ^= 1;
                }
            }
        }

        $fBits = self::FORMAT_BITS_L[$mask];
        for ($i = 0; $i <= 5; $i++) {
            $matrix[8][$i] = ($fBits >> $i) & 1;
        }
        $matrix[8][7] = ($fBits >> 6) & 1;
        $matrix[8][8] = ($fBits >> 7) & 1;
        $matrix[7][8] = ($fBits >> 8) & 1;
        for ($i = 9; $i < 15; $i++) {
            $matrix[14 - $i][8] = ($fBits >> $i) & 1;
        }

        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 1 - $i] = ($fBits >> $i) & 1;
        }
        for ($i = 8; $i < 15; $i++) {
            $matrix[$size - 15 + $i][8] = ($fBits >> $i) & 1;
        }

        return $matrix;
    }

    private static function calculatePenalty(array $matrix, int $size): int
    {
        $penalty = 0;
        for ($y = 0; $y < $size; $y++) {
            $runColor = $matrix[$y][0];
            $runLen   = 1;
            for ($x = 1; $x < $size; $x++) {
                if ($matrix[$y][$x] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $penalty += 3;
                    } elseif ($runLen > 5) {
                        $penalty++;
                    }
                } else {
                    $runColor = $matrix[$y][$x];
                    $runLen   = 1;
                }
            }
        }
        for ($x = 0; $x < $size; $x++) {
            $runColor = $matrix[0][$x];
            $runLen   = 1;
            for ($y = 1; $y < $size; $y++) {
                if ($matrix[$y][$x] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $penalty += 3;
                    } elseif ($runLen > 5) {
                        $penalty++;
                    }
                } else {
                    $runColor = $matrix[$y][$x];
                    $runLen   = 1;
                }
            }
        }
        return $penalty;
    }

    /**
     * Reed-Solomon Polynomdivision über GF(256) mit Primitivpolynom 0x11D.
     */
    private static function reedSolomonRemainder(array $data, int $degree): array
    {
        $gen = [1];
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            $nextGen = array_fill(0, count($gen) + 1, 0);
            for ($j = 0, $n = count($gen); $j < $n; $j++) {
                $nextGen[$j]     ^= $gen[$j];
                $nextGen[$j + 1] ^= self::gfMultiply($gen[$j], $root);
            }
            $gen  = $nextGen;
            $root = self::gfMultiply($root, 0x02);
        }
        array_shift($gen); // Führende 1 entfernen

        $result = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($result);
            $result[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $result[$i] ^= self::gfMultiply($gen[$i], $factor);
            }
        }
        return $result;
    }

    private static function gfMultiply(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z;
    }
}
