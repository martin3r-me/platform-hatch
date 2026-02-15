<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vereinfacht das Intake-Status-Modell:
 *
 * ALT: status (draft/in_progress/completed/paused/cancelled) + is_active + started_at (manuell)
 * NEU: status (draft/published/closed) — Ein Klick = live
 *
 * Mapping:
 *   draft                          → draft
 *   in_progress + is_active=true   → published
 *   in_progress + is_active=false  → closed
 *   completed                      → closed
 *   paused                         → closed
 *   cancelled                      → closed
 *
 * Abwärtskompatibel: is_active bleibt als Spalte erhalten (wird aus status abgeleitet),
 * started_at bleibt erhalten (wird automatisch beim Veröffentlichen gesetzt).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Bestehende Status-Werte migrieren
        // in_progress + is_active=true → published
        DB::table('hatch_project_intakes')
            ->where('status', 'in_progress')
            ->where('is_active', true)
            ->update(['status' => 'published']);

        // in_progress + is_active=false → closed
        DB::table('hatch_project_intakes')
            ->where('status', 'in_progress')
            ->where('is_active', false)
            ->update(['status' => 'closed']);

        // completed, paused, cancelled → closed
        DB::table('hatch_project_intakes')
            ->whereIn('status', ['completed', 'paused', 'cancelled'])
            ->update(['status' => 'closed']);

        // 2) is_active synchronisieren: published = active, alles andere = inactive
        DB::table('hatch_project_intakes')
            ->where('status', 'published')
            ->update(['is_active' => true]);

        DB::table('hatch_project_intakes')
            ->where('status', '!=', 'published')
            ->update(['is_active' => false]);

        // 3) started_at setzen für published-Intakes ohne started_at
        DB::table('hatch_project_intakes')
            ->where('status', 'published')
            ->whereNull('started_at')
            ->update(['started_at' => DB::raw('created_at')]);

        // 4) Default-Wert für status anpassen (bleibt 'draft')
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Rück-Migration: published → in_progress, closed → completed
        DB::table('hatch_project_intakes')
            ->where('status', 'published')
            ->update([
                'status' => 'in_progress',
                'is_active' => true,
            ]);

        DB::table('hatch_project_intakes')
            ->where('status', 'closed')
            ->update([
                'status' => 'completed',
                'is_active' => true,
            ]);
    }
};
