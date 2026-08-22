<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Regel für typlose Placeholder-Blöcke (#03).
 *
 * Blöcke ohne block_definition_id (per UI in Template\Show::addQuestionGroup /
 * addFieldToGroup angelegte Platzhalter) durchlaufen den Backfill aus #02 nicht,
 * da dieser ausschließlich Blöcke MIT block_definition_id anfasst. Sie blieben
 * dadurch ohne block_type zurück.
 *
 * Entschiedene Regel: Default-Typ statt "unvollständig"-Flag. Ein fehlender
 * block_type wird als freies Textfeld behandelt ('text') – deckungsgleich mit
 * dem bereits vorhandenen Fallback in IntakeSession/Show.php
 * ($blockDef?->block_type ?? 'text'). Kein zusätzliches Schema nötig.
 *
 * Idempotenz: trifft ausschließlich Blöcke mit block_definition_id IS NULL UND
 * block_type IS NULL. Nach dem Lauf gibt es keine solchen Zeilen mehr.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pending = DB::table('hatch_template_blocks')
            ->whereNull('block_definition_id')
            ->whereNull('block_type')
            ->count();

        Log::info('[hatch #03] Default block_type für typlose Placeholder-Blöcke – Start', [
            'pending' => $pending,
        ]);

        $touched = DB::table('hatch_template_blocks')
            ->whereNull('block_definition_id')
            ->whereNull('block_type')
            ->update([
                'block_type' => 'text',
                'updated_at' => now(),
            ]);

        Log::info('[hatch #03] Default block_type für typlose Placeholder-Blöcke – Fertig', [
            'touched' => $touched,
        ]);
    }

    public function down(): void
    {
        // Nicht sinnvoll reversibel: ob block_type vorher NULL war oder bereits
        // 'text' getragen hat, ist nach dem Lauf nicht mehr unterscheidbar.
    }
};
