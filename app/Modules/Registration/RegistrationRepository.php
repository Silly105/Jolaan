<?php
declare(strict_types=1);

namespace App\Modules\Registration;

use App\Core\Crypto;
use App\Core\Database;

class RegistrationRepository
{
    /**
     * Speichert eine neue Anmeldung inklusive aller dynamischen Feldwerte.
     * Die E-Mail-Adresse im Stammsatz wird AES-256-GCM verschlüsselt und mit einem HMAC-SHA256 Blind-Index (email_hash) versehen.
     */
    public static function create(
        int $eventId,
        int $groupId,
        array $coreData,
        array $fields,
        array $submittedValues
    ): int {
        $tRegs = Database::table('registrations');

        $plainEmail = trim((string)($coreData['email'] ?? ''));
        $encEmail   = $plainEmail !== '' ? Crypto::encrypt($plainEmail) : '';
        $emailHash  = $plainEmail !== '' ? Crypto::blindIndex($plainEmail) : '';

        Database::query(
            "INSERT INTO {$tRegs}
             (event_id, group_id, email, email_hash, first_name, last_name, birth_date, doi_token, is_confirmed, is_orga_confirmed, amount_paid, is_fully_paid, language)
             VALUES (:eid, :gid, :email, :ehash, :fname, :lname, :bdate, :token, :conf, :oconf, :paid, :fullpaid, :lang)",
            [
                'eid'      => $eventId,
                'gid'      => $groupId,
                'email'    => $encEmail,
                'ehash'    => $emailHash,
                'fname'    => $coreData['first_name'],
                'lname'    => $coreData['last_name'],
                'bdate'    => $coreData['birth_date'] ?: null,
                'token'    => $coreData['doi_token'] ?? '',
                'conf'     => (int)($coreData['is_confirmed'] ?? 0),
                'oconf'    => (int)($coreData['is_orga_confirmed'] ?? 0),
                'paid'     => (float)($coreData['amount_paid'] ?? 0.0),
                'fullpaid' => (int)($coreData['is_fully_paid'] ?? 0),
                'lang'     => $coreData['language'] ?? 'de',
            ]
        );

        $regId = Database::lastInsertId();
        self::saveFieldValues($regId, $fields, $submittedValues);

        return $regId;
    }

    /**
     * Aktualisiert die dynamischen Feldwerte einer bestehenden Anmeldung.
     * Felder mit is_encrypted = 1 oder is_sensitive = 1 werden AES-256-GCM verschlüsselt.
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
