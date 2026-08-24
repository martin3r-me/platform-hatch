<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration #04: block_definition_id aus hatch_template_blocks entfernen.
 *
 * Nach dem Backfill (#02) und der Default-Regel (#03) ist jeder Block
 * self-contained: block_type und die gesamte Laufzeit-Config liegen direkt
 * auf hatch_template_blocks. Die Referenz auf die (in #06 entfallende)
 * hatch_block_definitions wird hier gelöst.
 *
 * Freigegeben durch den Trockenlauf/das Datenintegritäts-Gate in
 * 2026_08_22_000003 (#18). Läuft chronologisch NACH allen lesenden/schreibenden
 * Zugriffen auf block_definition_id (#02 Backfill, #03 Default, #18 Verify) und
 * VOR dem Table-Drop #06 – so ist der FK weg, bevor die Zieltabelle fällt.
 *
 * Zu entfernen (aus create-Migration 2024_01_01_000003):
 *  - FK block_definition_id → hatch_block_definitions
 *  - Index hatch_tb_block_active_idx (block_definition_id, is_active)
 *  - Spalte block_definition_id
 *
 * Der Unique-Constraint hatch_tb_template_block_unique (project_template_id,
 * block_definition_id) wurde bereits in 2026_04_21_000001 entfernt – hier
 * defensiv per try/catch nochmals abgesichert, falls ein Altbestand ihn noch
 * trägt (sonst scheitert dropColumn an der bestehenden Constraint-Referenz).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Der Unique-Constraint wurde regulär bereits in 2026_04_21_000001
        // entfernt – auf manchen Beständen kann er aber noch existieren.
        // WICHTIG: try/catch MUSS den Schema::table()-Aufruf umschließen, nicht
        // nur die Blueprint-Command. Der Drop wird erst beim Ausführen der
        // Blueprint (nach der Closure) als SQL abgesetzt; eine Exception fliegt
        // daher AUSSERHALB der Closure. Steht das try/catch innen, greift es
        // nicht und die Migration bricht mit SQLSTATE 1091 ab.
        try {
            Schema::table('hatch_template_blocks', function (Blueprint $table) {
                $table->dropUnique('hatch_tb_template_block_unique');
            });
        } catch (\Throwable $e) {
            // Constraint existiert nicht mehr – erwarteter Normalfall.
        }

        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            $table->dropForeign(['block_definition_id']);
            $table->dropIndex('hatch_tb_block_active_idx');
            $table->dropColumn('block_definition_id');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            // Spalte nur nullable wiederherstellbar – die ursprüngliche Zuordnung
            // ist nach dem Drop nicht mehr rekonstruierbar.
            $table->foreignId('block_definition_id')->nullable()->after('project_template_id')
                ->constrained('hatch_block_definitions')->onDelete('cascade');
            $table->index(['block_definition_id', 'is_active'], 'hatch_tb_block_active_idx');
        });
    }
};
