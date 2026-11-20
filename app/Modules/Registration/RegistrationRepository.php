<?php
declare(strict_types=1);

namespace App\Modules\Registration;

use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Database;
use App\Modules\Events\EventRepository;

class RegistrationRepository
{
    /**
     * Erzeugt eine formatierte Protokollzeile mit aktuellem Datum & Uhrzeit.
     */
    public static function formatLogLine(string $text, bool $includeActor = false): string
    {
        $actor = '';
        if ($includeActor && Auth::check()) {
            $u = Auth::user();
            $actor = ' (durch ' . ($u['name'] ?? 'SL') . ')';
        }
        return date('d.m.Y H:i:s') . ' – ' . $text . $actor;
    }

    /**
     * Hängt eine neue Zeile an das schreibgeschützte Verlaufs-Protokoll (history_log) einer Anmeldung an.
     */
    public static function appendHistoryLog(int $registrationId, string $message, bool $includeActor = false): void
    {
        $tRegs = Database::table('registrations');
        $current = (string)Database::fetchScalar("SELECT history_log FROM {$tRegs} WHERE id = :id", ['id' => $registrationId]);
        $line = self::formatLogLine($message, $includeActor);
        $updated = trim($current) !== '' ? (rtrim($current) . "\n" . $line) : $line;
        Database::query("UPDATE {$tRegs} SET history_log = :log WHERE id = :id", [
            'log' => $updated,
            'id'  => $registrationId,
        ]);
    }

    /**
     * Prüft zum Zeitpunkt der E-Mail-Bestätigung (oder manuellen Freischaltung), ob die gewählte Teilnehmergruppe
     * bereits voll ist. Falls ja (und die Anmeldung nicht über den Geheim-Link kam), wird der Teilnehmer auf die
     * Warteliste (is_waitlist = 1) gesetzt.
     */
    public static function determineWaitlistStatusOnConfirm(int $eventId, int $groupId, bool $isSecretUnlock): bool
    {
        if ($isSecretUnlock) {
            return false; // Geheim-Link überspringt immer die Warteliste
        }
        $groups = EventRepository::getGroupsWithSeats($eventId);
        foreach ($groups as $g) {
            if ((int)$g['id'] === $groupId) {
                return (bool)$g['is_full'];
            }
        }
        return false;
    }

    /**
     * Berechnet den aktuellen Wartelistenplatz (1, 2, 3...) einer Anmeldung innerhalb ihrer Teilnehmergruppe
     * anhand der Reihenfolge der E-Mail-Bestätigung (confirmed_at ASC, id ASC).
     */
    public static function getWaitlistPosition(int $eventId, int $groupId, int $registrationId): int
    {
        $tRegs = Database::table('registrations');
        $rows = Database::fetchAll(
            "SELECT id FROM {$tRegs}
             WHERE event_id = :eid AND group_id = :gid AND is_confirmed = 1 AND is_waitlist = 1
             ORDER BY confirmed_at ASC, id ASC",
            ['eid' => $eventId, 'gid' => $groupId]
        );
        $pos = 1;
        foreach ($rows as $r) {
            if ((int)$r['id'] === $registrationId) {
                return $pos;
            }
            $pos++;
        }
        return $pos;
    }

    /**
     * Speichert eine neue Anmeldung inklusive aller dynamischen Feldwerte und erstem Protokolleintrag.
     */
    public static function create(
        int $eventId,
        int $groupId,
        array $coreData,
        array $fields,
        array $submittedValues
    ): int {
        $tRegs = Database::table('registrations');

        $plainEmail     = trim((string)($coreData['email'] ?? ''));
        $encEmail       = $plainEmail !== '' ? Crypto::encrypt($plainEmail) : '';
        $emailHash      = $plainEmail !== '' ? Crypto::blindIndex($plainEmail) : '';
        $isConfirmed    = (int)($coreData['is_confirmed'] ?? 0);
        $isWaitlist     = (int)($coreData['is_waitlist'] ?? 0);
        $isSecretUnlock = (int)($coreData['is_secret_unlock'] ?? 0);
        $confirmedAt    = $isConfirmed === 1 ? ($coreData['confirmed_at'] ?? date('Y-m-d H:i:s')) : null;

        $initialLog = $coreData['history_log'] ?? self::formatLogLine(
            $isConfirmed === 1
                ? 'Anmeldung manuell im Backend eingetragen und direkt freigeschaltet'
                : 'Anmeldung über das Online-Formular eingegangen' . ($isSecretUnlock === 1 ? ' (über Geheim-Link)' : '')
        );

        Database::query(
            "INSERT INTO {$tRegs}
             (event_id, group_id, email, email_hash, first_name, last_name, birth_date, doi_token, is_confirmed, is_waitlist, waitlist_confirmed, is_secret_unlock, is_orga_confirmed, amount_paid, is_fully_paid, language, confirmed_at, history_log)
             VALUES (:eid, :gid, :email, :ehash, :fname, :lname, :bdate, :token, :conf, :wait, :wconf, :sec, :oconf, :paid, :fullpaid, :lang, :cat, :hlog)",
            [
                'eid'      => $eventId,
                'gid'      => $groupId,
                'email'    => $encEmail,
                'ehash'    => $emailHash,
                'fname'    => $coreData['first_name'],
                'lname'    => $coreData['last_name'],
                'bdate'    => $coreData['birth_date'] ?: null,
                'token'    => $coreData['doi_token'] ?? '',
                'conf'     => $isConfirmed,
                'wait'     => $isWaitlist,
                'wconf'    => (int)($coreData['waitlist_confirmed'] ?? 0),
                'sec'      => $isSecretUnlock,
                'oconf'    => (int)($coreData['is_orga_confirmed'] ?? 0),
                'paid'     => (float)($coreData['amount_paid'] ?? 0.0),
                'fullpaid' => (int)($coreData['is_fully_paid'] ?? 0),
                'lang'     => $coreData['language'] ?? 'de',
                'cat'      => $confirmedAt,
                'hlog'     => $initialLog,
            ]
        );

        $regId = Database::lastInsertId();
        self::saveFieldValues($regId, $fields, $submittedValues);

        return $regId;
    }

    /**
     * Aktualisiert die dynamischen Feldwerte einer bestehenden Anmeldung.
     */
    public static function saveFieldValues(int $registrationId, array $fields, array $submittedValues): void
    {
        $tVals = Database::table('registration_values');

        foreach ($fields as $field) {
            if ($field['field_type'] === 'heading') {
                continue;
            }
            $fieldId = (int)$field['id'];
            $key = $field['field_key'];
            if (!array_key_exists($key, $submittedValues) && !array_key_exists($fieldId, $submittedValues)) {
                continue;
            }
            $rawVal = (string)($submittedValues[$key] ?? $submittedValues[$fieldId] ?? '');
            $shouldEncrypt = ((int)($field['is_encrypted'] ?? 0) === 1) || ((int)($field['is_sensitive'] ?? 0) === 1);

            if ($shouldEncrypt && $rawVal !== '') {
                $storedVal = Crypto::encrypt($rawVal);
                $encFlag = 1;
            } else {
                $storedVal = $rawVal;
                $encFlag = 0;
            }

            Database::query(
                "INSERT INTO {$tVals} (registration_id, field_id, field_value, is_encrypted)
                 VALUES (:rid, :fid, :val, :enc)
                 ON DUPLICATE KEY UPDATE field_value = VALUES(field_value), is_encrypted = VALUES(is_encrypted)",
                [
                    'rid' => $registrationId,
                    'fid' => $fieldId,
                    'val' => $storedVal,
                    'enc' => $encFlag,
                ]
            );
        }
    }

    /**
     * Lädt eine einzelne Anmeldung inkl. entschlüsselter E-Mail und Feldwerte.
     */
    public static function findWithValues(int $registrationId): ?array
    {
        $tRegs   = Database::table('registrations');
        $tGroups = Database::table('event_groups');
        $tVals   = Database::table('registration_values');
        $tFields = Database::table('form_fields');

        $reg = Database::fetchOne(
            "SELECT r.*, g.name_de AS group_name_de, g.name_en AS group_name_en
             FROM {$tRegs} r
             LEFT JOIN {$tGroups} g ON g.id = r.group_id
             WHERE r.id = :id LIMIT 1",
            ['id' => $registrationId]
        );

        if (!$reg) {
            return null;
        }

        $reg['email'] = Crypto::decrypt((string)$reg['email']);

        $rows = Database::fetchAll(
            "SELECT rv.field_id, rv.field_value, rv.is_encrypted, f.field_key, f.is_sensitive
             FROM {$tVals} rv
             INNER JOIN {$tFields} f ON f.id = rv.field_id
             WHERE rv.registration_id = :rid",
            ['rid' => $registrationId]
        );

        $valuesByKey = [];
        $valuesById  = [];
        foreach ($rows as $row) {
            $val = (string)$row['field_value'];
            if ((int)$row['is_encrypted'] === 1 || Crypto::isEncrypted($val)) {
                $val = Crypto::decrypt($val);
            }
            $valuesByKey[$row['field_key']] = $val;
            $valuesById[(int)$row['field_id']] = $val;
        }

        $reg['values'] = $valuesByKey;
        $reg['values_by_id'] = $valuesById;
        return $reg;
    }

    /**
     * Lädt alle Anmeldungen einer Veranstaltung inkl. entschlüsselter E-Mail, Feldwerte und Alter zum Veranstaltungsbeginn.
     */
    public static function allForEvent(int $eventId, ?string $eventStartDate = null): array
    {
        $tRegs   = Database::table('registrations');
        $tGroups = Database::table('event_groups');
        $tVals   = Database::table('registration_values');
        $tFields = Database::table('form_fields');

        $regs = Database::fetchAll(
            "SELECT r.*, g.name_de AS group_name_de, g.name_en AS group_name_en
             FROM {$tRegs} r
             LEFT JOIN {$tGroups} g ON g.id = r.group_id
             WHERE r.event_id = :eid
             ORDER BY r.last_name ASC, r.first_name ASC, r.id ASC",
            ['eid' => $eventId]
        );

        if (empty($regs)) {
            return [];
        }

        $allVals = Database::fetchAll(
            "SELECT rv.registration_id, rv.field_id, rv.field_value, rv.is_encrypted, f.field_key
             FROM {$tVals} rv
             INNER JOIN {$tFields} f ON f.id = rv.field_id
             INNER JOIN {$tRegs} r ON r.id = rv.registration_id
             WHERE r.event_id = :eid",
            ['eid' => $eventId]
        );

        $groupedVals = [];
        foreach ($allVals as $row) {
            $val = (string)$row['field_value'];
            if ((int)$row['is_encrypted'] === 1 || Crypto::isEncrypted($val)) {
                $val = Crypto::decrypt($val);
            }
            $groupedVals[(int)$row['registration_id']][$row['field_key']] = $val;
        }

        foreach ($regs as &$r) {
            $rid = (int)$r['id'];
            $r['email'] = Crypto::decrypt((string)$r['email']);
            $r['values'] = $groupedVals[$rid] ?? [];
            $r['age_at_event'] = calculate_age_at($r['birth_date'] ?? null, $eventStartDate);
            $r['is_under18'] = ($r['age_at_event'] !== null && $r['age_at_event'] < 18);
        }
        unset($r);

        return $regs;
    }
}
