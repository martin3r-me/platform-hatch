<?php

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rückwirkende Stempelung bestehender Sessions mit ISO-KW + Jahr.
 *
 * Wir nutzen hier bewusst ISO-Standard (Montag 00:00) — also rohes started_at
 * ohne Berücksichtigung möglicher week_cutoff-Settings. Begründung: zum
 * Zeitpunkt der Erfassung gab es noch keine Cutoff-Logik, alle alten Sessions
 * sind nach ISO-Standard zu interpretieren. Ein nachträgliches Anwenden eines
 * heute gesetzten Cutoffs würde die historische Zuordnung verzerren.
 *
 * Zusätzlich: bestehender "Wochenfeedback – KW 18/2026"-Intake auf den
 * Platzhalter-Namen umstellen, damit der Public-Link künftig automatisch
 * die aktuelle KW anzeigt.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('hatch_intake_sessions')
            ->whereNull('iso_week')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $stamp = $row->started_at ?? $row->created_at;
                    if (!$stamp) {
                        continue;
                    }
                    $moment = CarbonImmutable::parse($stamp);
                    DB::table('hatch_intake_sessions')
                        ->where('id', $row->id)
                        ->update([
                            'iso_year' => (int) $moment->isoWeekYear,
                            'iso_week' => (int) $moment->isoWeek,
                        ]);
                }
            });

        // Bestehende Wochenfeedback-Intakes auf Platzhalter umstellen.
        // Bewusst konservativ: nur exakter Match auf das aktuelle KW-Pattern,
        // damit individuell benannte Intakes nicht überschrieben werden.
        DB::table('hatch_project_intakes')
            ->where('name', 'like', 'Wochenfeedback – KW %/%')
            ->update([
                'name' => 'Wochenfeedback – KW {{iso_week}}/{{iso_year}}',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Rückstempelung ist nicht reversibel ohne Verlust. iso_week/iso_year
        // werden durch die parent-Migration weggeräumt — hier nichts zu tun.
        // Den Namens-Rewrite rollen wir bewusst nicht zurück, da wir nicht
        // wissen können, welche KW der ursprüngliche Intake-Name trug.
    }
};
