<?php
declare(strict_types=1);

namespace App\Modules\Events;

use App\Core\Database;

class EventRepository
{
    public static function all(): array
    {
        $tEvents = Database::table('events');
        return Database::fetchAll("SELECT * FROM {$tEvents} ORDER BY start_date DESC, id DESC");
    }

    public static function find(int $id): ?array
    {
        $tEvents = Database::table('events');
        return Database::fetchOne("SELECT * FROM {$tEvents} WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    public static function upcomingForIndex(): array
    {
        $tEvents = Database::table('events');
        $today = date('Y-m-d');
        return Database::fetchAll(
            "SELECT * FROM {$tEvents} WHERE show_on_index = 1 AND end_date >= :today ORDER BY start_date ASC",
            ['today' => $today]
        );
    }

    /**
     * Lädt die Teilnehmergruppen einer Veranstaltung inkl. automatisch berechneter (oder manuell überschriebener) freier Plätze.
     * Für die Platzberechnung zählen nur bestätigte reguläre Anmeldungen (is_confirmed = 1 AND is_waitlist = 0).
     */
    public static function getGroupsWithSeats(?int $eventId): array
    {
        $tGroups = Database::table('event_groups');
        $tRegs   = Database::table('registrations');

        if ($eventId === null) {
            return Database::fetchAll("SELECT * FROM {$tGroups} WHERE event_id IS NULL ORDER BY sort_order ASC, id ASC");
        }

        $sql = "SELECT g.*,
                       (SELECT COUNT(*) FROM {$tRegs} r WHERE r.event_id = g.event_id AND r.group_id = g.id AND r.is_confirmed = 1 AND r.is_waitlist = 0) AS confirmed_count,
                       (SELECT COUNT(*) FROM {$tRegs} r WHERE r.event_id = g.event_id AND r.group_id = g.id AND r.is_confirmed = 1 AND r.is_waitlist = 1) AS waitlist_count,
                       (SELECT COUNT(*) FROM {$tRegs} r WHERE r.event_id = g.event_id AND r.group_id = g.id) AS total_count
                FROM {$tGroups} g
                WHERE g.event_id = :eid
                ORDER BY g.sort_order ASC, g.id ASC";
        $groups = Database::fetchAll($sql, ['eid' => $eventId]);

        foreach ($groups as &$g) {
            $maxSeats  = (int)$g['max_seats'];
            $confirmed = (int)$g['confirmed_count'];
            $override  = $g['manual_free_override'];

            if ($override !== null && $override !== '') {
                $free = max(0, (int)$override);
                $isOverridden = true;
            } elseif ($maxSeats > 0) {
                $free = max(0, $maxSeats - $confirmed);
                $isOverridden = false;
            } else {
                $free = null;
                $isOverridden = false;
            }

            $g['calculated_free'] = $free;
            $g['is_overridden']   = $isOverridden;
            $g['is_full']         = ((int)$g['is_locked'] === 1) || ($free !== null && $free <= 0);
        }
        unset($g);

        return $groups;
    }

    public static function getFields(?int $eventId, bool $includeOrgaOnly = true): array
    {
        $tFields = Database::table('form_fields');
        $orgaClause = $includeOrgaOnly ? '' : ' AND is_orga_only = 0';
        if ($eventId === null) {
            return Database::fetchAll("SELECT * FROM {$tFields} WHERE event_id IS NULL {$orgaClause} ORDER BY sort_order ASC, id ASC");
        }
        return Database::fetchAll(
            "SELECT * FROM {$tFields} WHERE event_id = :eid {$orgaClause} ORDER BY sort_order ASC, id ASC",
            ['eid' => $eventId]
        );
    }

    public static function getSavedViews(?int $eventId): array
    {
        $tViews = Database::table('saved_views');
        if ($eventId === null) {
            return Database::fetchAll("SELECT * FROM {$tViews} WHERE event_id IS NULL ORDER BY sort_order ASC, id ASC");
        }
        return Database::fetchAll(
            "SELECT * FROM {$tViews} WHERE event_id = :eid ORDER BY sort_order ASC, id ASC",
            ['eid' => $eventId]
        );
    }

    /**
     * Kopiert Teilnehmergruppen, Formularfelder und gespeicherte Listen von einer Quell-Veranstaltung
     * (oder aus der globalen Standard-Vorlage, wenn $sourceEventId === null) in eine neue Veranstaltung.
     */
    public static function cloneStructure(?int $sourceEventId, int $targetEventId): void
    {
        $tGroups = Database::table('event_groups');
        $tFields = Database::table('form_fields');
        $tViews  = Database::table('saved_views');

        // 1. Gruppen klonen (inkl. show_in_registration)
        $sourceGroups = self::getGroupsWithSeats($sourceEventId);
        foreach ($sourceGroups as $g) {
            Database::query(
                "INSERT INTO {$tGroups} (event_id, name_de, name_en, max_seats, manual_free_override, is_locked, show_on_index, show_in_registration, sort_order)
                 VALUES (:eid, :nde, :nen, :max, NULL, 0, :show, :showreg, :sort)",
                [
                    'eid'     => $targetEventId,
                    'nde'     => $g['name_de'],
                    'nen'     => $g['name_en'],
                    'max'     => (int)$g['max_seats'],
                    'show'    => (int)$g['show_on_index'],
                    'showreg' => (int)($g['show_in_registration'] ?? 1),
                    'sort'    => (int)$g['sort_order'],
                ]
            );
        }

        // 2. Formularfelder klonen (mit Kollisionsschutz: is_orga_only => is_required = 0)
        $sourceFields = self::getFields($sourceEventId, true);
        foreach ($sourceFields as $f) {
            $isOrgaOnly  = (int)$f['is_orga_only'] === 1 ? 1 : 0;
            $isRequired  = $isOrgaOnly === 1 ? 0 : ((int)$f['is_required'] === 1 ? 1 : 0);
            $isSensitive = (int)($f['is_sensitive'] ?? 0) === 1 ? 1 : 0;
            $isEncrypted = $isSensitive === 1 ? 1 : ((int)($f['is_encrypted'] ?? 0) === 1 ? 1 : 0);

            Database::query(
                "INSERT INTO {$tFields}
                 (event_id, field_key, field_type, label_de, label_en, help_de, help_en, options_json, is_required, is_encrypted, is_sensitive, is_orga_only, system_mapping, sort_order)
                 VALUES (:eid, :fkey, :ftype, :lde, :len, :hde, :hen, :opt, :req, :enc, :sens, :orga, :map, :sort)",
                [
                    'eid'   => $targetEventId,
                    'fkey'  => $f['field_key'],
                    'ftype' => $f['field_type'],
                    'lde'   => $f['label_de'],
                    'len'   => $f['label_en'],
                    'hde'   => $f['help_de'],
                    'hen'   => $f['help_en'],
                    'opt'   => $f['options_json'],
                    'req'   => $isRequired,
                    'enc'   => $isEncrypted,
                    'sens'  => $isSensitive,
                    'orga'  => $isOrgaOnly,
                    'map'   => $f['system_mapping'],
                    'sort'  => (int)$f['sort_order'],
                ]
            );
        }

        // 3. Gespeicherte Listen-Ansichten klonen
        $sourceViews = self::getSavedViews($sourceEventId);
        foreach ($sourceViews as $v) {
            Database::query(
                "INSERT INTO {$tViews} (event_id, name, config_json, sort_order)
                 VALUES (:eid, :name, :cfg, :sort)",
                [
                    'eid'  => $targetEventId,
                    'name' => $v['name'],
                    'cfg'  => $v['config_json'],
                    'sort' => (int)$v['sort_order'],
                ]
            );
        }
    }
}
