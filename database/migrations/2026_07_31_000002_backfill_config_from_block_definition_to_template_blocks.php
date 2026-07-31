<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Daten-Backfill (#02): block_definition → template_block.
 *
 * Nachdem #01 die Konfigurations-Spalten auf hatch_template_blocks angelegt hat
 * (alle nullable, kein Backfill), spiegelt diese Migration für jeden Block mit
 * block_definition_id den block_type sowie sämtliche Config-Spalten der
 * zugehörigen BlockDefinition in den Block. Danach ist jeder Block
 * self-contained und nicht mehr auf die Definition angewiesen.
 *
 * Merge-Regel für name/description: der Block gewinnt. Nur wenn der Block keinen
 * eigenen (nicht-leeren) Wert trägt, wird der Wert der Definition übernommen.
 *
 * Idempotenz: Es werden ausschließlich Blöcke mit block_definition_id UND noch
 * leerem block_type angefasst. Da jede BlockDefinition per Schema einen
 * block_type (NOT NULL) besitzt, ist nach dem Lauf block_type immer gesetzt –
 * ein erneuter Lauf trifft keine Zeilen mehr. Bereits gepflegte (ggf. inzwischen
 * abweichende) Block-Configs werden dadurch nicht überschrieben.
 *
 * Vgl. Trockenlauf #18. Config-Tabelle (kleine Zeilenzahl), dennoch chunked.
 */
return new class extends Migration
{
    /**
     * Config-Spalten, die 1:1 von der Definition in den Block gespiegelt werden.
     * Rohwerte werden direkt kopiert – die JSON-Spalten enthalten bereits
     * gültigen JSON-Text, ein Re-Encode würde nur Doppel-Kodierung riskieren.
     */
    private const CONFIG_COLUMNS = [
        'block_type',
        'logic_config',
        'validation_rules',
        'conditional_logic',
        'response_format',
        'fallback_questions',
        'ai_prompt',
        'ai_behavior',
        'exit_conditions',
        'min_confidence_threshold',
        'max_clarification_attempts',
        'max_messages_per_block',
    ];

    public function up(): void
    {
        // Zähler VORHER dokumentieren.
        $withDefinition = DB::table('hatch_template_blocks')
            ->whereNotNull('block_definition_id')
            ->count();
        $pending = DB::table('hatch_template_blocks')
            ->whereNotNull('block_definition_id')
            ->whereNull('block_type')
            ->count();

        Log::info('[hatch #02] Backfill block_definition → template_block – Start', [
            'blocks_with_definition' => $withDefinition,
            'pending_before'         => $pending,
        ]);

        $touched = 0;

        DB::table('hatch_template_blocks')
            ->whereNotNull('block_definition_id')
            ->whereNull('block_type')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$touched) {
                // Definitionen des Chunks in einem Rutsch laden (kein N+1).
                $definitionIds = collect($rows)->pluck('block_definition_id')->unique()->all();
                $definitions = DB::table('hatch_block_definitions')
                    ->whereIn('id', $definitionIds)
                    ->get()
                    ->keyBy('id');

                foreach ($rows as $row) {
                    $def = $definitions->get($row->block_definition_id);
                    if (!$def) {
                        // Sollte dank FK nicht vorkommen; defensiv überspringen.
                        continue;
                    }

                    $update = [
                        'name'        => $this->preferBlock($row->name, $def->name),
                        'description' => $this->preferBlock($row->description, $def->description),
                        'updated_at'  => now(),
                    ];

                    foreach (self::CONFIG_COLUMNS as $column) {
                        $update[$column] = $def->{$column} ?? null;
                    }

                    DB::table('hatch_template_blocks')
                        ->where('id', $row->id)
                        ->update($update);

                    $touched++;
                }
            });

        // Zähler NACHHER dokumentieren.
        $pendingAfter = DB::table('hatch_template_blocks')
            ->whereNotNull('block_definition_id')
            ->whereNull('block_type')
            ->count();

        Log::info('[hatch #02] Backfill block_definition → template_block – Fertig', [
            'blocks_with_definition' => $withDefinition,
            'touched'                => $touched,
            'pending_after'          => $pendingAfter,
        ]);
    }

    public function down(): void
    {
        // Nicht sinnvoll reversibel: Der ursprüngliche (großteils leere) Zustand
        // der Config-Spalten sowie die vor dem Merge leeren name/description-Werte
        // sind nach dem Backfill nicht mehr rekonstruierbar. Die Config-Spalten
        // werden ohnehin durch die Down-Migration von #01 entfernt.
    }

    /**
     * Merge-Regel: der Wert des Blocks gewinnt; nur ein leerer (null oder
     * ausschließlich Whitespace) Block-Wert fällt auf die Definition zurück.
     */
    private function preferBlock(?string $blockValue, ?string $definitionValue): ?string
    {
        if ($blockValue !== null && trim($blockValue) !== '') {
            return $blockValue;
        }

        return $definitionValue;
    }
};
