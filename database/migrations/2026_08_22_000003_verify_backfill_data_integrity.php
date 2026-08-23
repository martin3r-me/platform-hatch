<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Platform\Hatch\Enums\HatchBlockType;
use Platform\Hatch\Models\HatchIntakeSession;

/**
 * Verifikation (#18): Backfill-Trockenlauf + Datenintegrität.
 *
 * Gate vor #04–#06 (Drop-Migrationen für die verbliebenen block_definition_id-
 * Referenzen bzw. hatch_block_definitions selbst, vgl. Kommentar "Vgl.
 * Trockenlauf #18" in #02). Prüft rein lesend:
 *
 *  (a) jeder hatch_template_blocks-Datensatz hat einen gültigen block_type
 *      (NOT NULL, Wert aus HatchBlockType::values())
 *  (b) keine hatch_intake_sessions.answers-Keys "block_{id}" zeigen auf eine
 *      nicht (mehr) existierende hatch_template_blocks-Zeile
 *  (c) jede hatch_project_intake_steps-Zeile hat weiterhin ein gültiges
 *      template_block_id (Referenz besteht; wird zusätzlich durch die FK mit
 *      onDelete('cascade') auf hatch_template_blocks erzwungen)
 *
 * answers liegt verschlüsselt (EncryptedJson-Cast, siehe Encryptable-Trait)
 * in der DB – daher über das Eloquent-Model gelesen statt per Query Builder
 * auf der Rohspalte.
 *
 * Trockenlauf-Garantie: sämtliche Prüfungen laufen in einer Transaktion, die
 * danach IMMER zurückgerollt wird – es findet kein Schreibzugriff statt,
 * unabhängig vom Ergebnis. Das Ergebnis wird geloggt. Schlägt eine der drei
 * Prüfungen fehl, wirft die Migration eine Exception – #04–#06 dürfen dann
 * NICHT freigegeben werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::beginTransaction();

        try {
            $report = $this->verify();
        } finally {
            DB::rollBack();
        }

        Log::info('[hatch #18] Verifikation Backfill-Trockenlauf + Datenintegrität', $report);

        if ($report['blocks_invalid_type'] > 0 || $report['orphaned_answer_keys'] > 0 || $report['steps_orphaned'] > 0) {
            throw new \RuntimeException(
                '[hatch #18] Datenintegritätsprüfung fehlgeschlagen – #04–#06 NICHT freigegeben: '
                . json_encode($report)
            );
        }

        Log::info('[hatch #18] Freigabe für #04–#06: Trockenlauf ohne Datenverlust, alle Prüfungen bestanden.');
    }

    private function verify(): array
    {
        $validTypes = HatchBlockType::values();

        $blocksTotal = DB::table('hatch_template_blocks')->count();
        $blocksInvalidType = DB::table('hatch_template_blocks')
            ->where(function ($query) use ($validTypes) {
                $query->whereNull('block_type')->orWhereNotIn('block_type', $validTypes);
            })
            ->count();

        $existingBlockIds = DB::table('hatch_template_blocks')->pluck('id')->flip();

        $sessionsChecked = 0;
        $orphanedAnswerKeys = 0;

        HatchIntakeSession::query()->orderBy('id')->chunkById(500, function ($sessions) use (&$sessionsChecked, &$orphanedAnswerKeys, $existingBlockIds) {
            foreach ($sessions as $session) {
                $sessionsChecked++;

                foreach (array_keys($session->answers ?? []) as $key) {
                    if (!str_starts_with($key, 'block_')) {
                        continue;
                    }

                    $blockId = (int) substr($key, strlen('block_'));
                    if (!$existingBlockIds->has($blockId)) {
                        $orphanedAnswerKeys++;
                    }
                }
            }
        });

        $stepsTotal = DB::table('hatch_project_intake_steps')->count();
        $stepsOrphaned = DB::table('hatch_project_intake_steps as s')
            ->leftJoin('hatch_template_blocks as b', 's.template_block_id', '=', 'b.id')
            ->where(function ($query) {
                $query->whereNull('s.template_block_id')->orWhereNull('b.id');
            })
            ->count();

        return [
            'blocks_total' => $blocksTotal,
            'blocks_invalid_type' => $blocksInvalidType,
            'sessions_checked' => $sessionsChecked,
            'orphaned_answer_keys' => $orphanedAnswerKeys,
            'steps_total' => $stepsTotal,
            'steps_orphaned' => $stepsOrphaned,
        ];
    }

    public function down(): void
    {
        // Rein lesende Verifikation – nichts zu invertieren.
    }
};
