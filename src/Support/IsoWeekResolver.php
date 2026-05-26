<?php

namespace Platform\Hatch\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Platform\Hatch\Models\HatchProjectIntake;

/**
 * Bestimmt die "logische" ISO-Kalenderwoche, die einer Session zuzuordnen ist.
 *
 * Default: ISO-8601 Standard (Woche beginnt Montag 00:00).
 *
 * Optional konfigurierbar pro Intake via intake_settings.week_cutoff:
 *
 *   {
 *     "week_cutoff": {
 *       "enabled": true,
 *       "rollover_weekday": "monday",   // ab welchem Wochentag die "neue" KW gilt
 *       "rollover_time": "00:00"         // HH:MM (optional)
 *     }
 *   }
 *
 * Beispiel: rollover_weekday="saturday" → eine Antwort am Samstag Mittag
 * zählt bereits zur kommenden ISO-KW. Default (Montag 00:00) entspricht
 * dem ISO-Standard ohne Verschiebung.
 */
class IsoWeekResolver
{
    private const WEEKDAYS = [
        'monday' => CarbonInterface::MONDAY,
        'tuesday' => CarbonInterface::TUESDAY,
        'wednesday' => CarbonInterface::WEDNESDAY,
        'thursday' => CarbonInterface::THURSDAY,
        'friday' => CarbonInterface::FRIDAY,
        'saturday' => CarbonInterface::SATURDAY,
        'sunday' => CarbonInterface::SUNDAY,
    ];

    /**
     * Liefert ['iso_year' => int, 'iso_week' => int] für den Intake zum Zeitpunkt $now.
     */
    public function resolve(?HatchProjectIntake $intake, ?CarbonInterface $now = null): array
    {
        $now = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();
        $settings = $this->extractCutoff($intake);

        if ($settings === null) {
            return $this->isoOf($now);
        }

        // Wenn der Cutoff vor "diese ISO-Woche, Montag 00:00" liegt (z. B. Samstag 12:00),
        // verschieben wir alle Antworten ab dem Cutoff in die kommende KW.
        $rolloverThisWeek = $this->cutoffMomentForIsoWeek($now, $settings);

        if ($now->greaterThanOrEqualTo($rolloverThisWeek)) {
            // Rollover hat in dieser ISO-Woche bereits stattgefunden → kommende KW
            return $this->isoOf($now->addWeek());
        }

        return $this->isoOf($now);
    }

    /**
     * Reine Funktion ohne Intake — nutzt ISO-Standard.
     */
    public function resolveDefault(?CarbonInterface $now = null): array
    {
        return $this->isoOf($now ? CarbonImmutable::instance($now) : CarbonImmutable::now());
    }

    private function isoOf(CarbonImmutable $moment): array
    {
        return [
            'iso_year' => (int) $moment->isoWeekYear,
            'iso_week' => (int) $moment->isoWeek,
        ];
    }

    /**
     * Liest week_cutoff aus dem Intake. Liefert null wenn deaktiviert oder
     * äquivalent zum ISO-Standard (Montag 00:00) — in dem Fall sparen wir
     * uns die Verschiebungs-Rechnung.
     */
    private function extractCutoff(?HatchProjectIntake $intake): ?array
    {
        if (!$intake) {
            return null;
        }

        $settings = $intake->intake_settings ?? null;
        if (!is_array($settings)) {
            return null;
        }

        $cutoff = $settings['week_cutoff'] ?? null;
        if (!is_array($cutoff)) {
            return null;
        }

        if (array_key_exists('enabled', $cutoff) && !$cutoff['enabled']) {
            return null;
        }

        $weekdayKey = strtolower((string) ($cutoff['rollover_weekday'] ?? 'monday'));
        if (!isset(self::WEEKDAYS[$weekdayKey])) {
            return null;
        }

        $time = (string) ($cutoff['rollover_time'] ?? '00:00');
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
            $time = '00:00';
            $m = [null, '0', '0'];
        }

        $weekday = self::WEEKDAYS[$weekdayKey];
        $hour = (int) $m[1];
        $minute = (int) $m[2];

        // Default-Äquivalent zum ISO-Standard → keine Cutoff-Logik nötig.
        if ($weekday === CarbonInterface::MONDAY && $hour === 0 && $minute === 0) {
            return null;
        }

        return [
            'weekday' => $weekday,
            'hour' => $hour,
            'minute' => $minute,
        ];
    }

    /**
     * Liefert den Cutoff-Zeitpunkt innerhalb der ISO-Woche von $now.
     */
    private function cutoffMomentForIsoWeek(CarbonImmutable $now, array $cutoff): CarbonImmutable
    {
        return $now
            ->startOfWeek(CarbonInterface::MONDAY)
            ->addDays($cutoff['weekday'] - CarbonInterface::MONDAY)
            ->setTime($cutoff['hour'], $cutoff['minute']);
    }
}
