<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration #05: block_definition_id aus hatch_project_intake_steps entfernen.
 *
 * hatch_project_intake_steps referenzierte redundant sowohl template_block_id
 * als auch block_definition_id. Laufzeit-Config (block_type, ai_prompt, etc.)
 * kommt seit #01/#02 aus dem self-contained hatch_template_blocks-Datensatz
 * (per template_block_id erreichbar). Die direkte Referenz auf die Definition
 * wird hier entfernt; template_block_id sowie alle KI-/Laufzeit-Spalten
 * (answers, ai_interpretation, ai_confidence, ...) bleiben unangetastet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_project_intake_steps', function (Blueprint $table) {
            $table->dropForeign(['block_definition_id']);
            $table->dropColumn('block_definition_id');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_intake_steps', function (Blueprint $table) {
            $table->foreignId('block_definition_id')->nullable()->after('template_block_id')
                ->constrained('hatch_block_definitions')->onDelete('cascade');
        });
    }
};
