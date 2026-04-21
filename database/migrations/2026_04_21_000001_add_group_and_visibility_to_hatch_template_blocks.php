<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            $table->uuid('group_uuid')->nullable()->after('block_definition_id');
            $table->json('visibility_rules')->nullable()->after('is_required');

            $table->index(['project_template_id', 'group_uuid'], 'hatch_tb_template_group_idx');
        });

        // Unique-Constraint (project_template_id, block_definition_id) entfernen –
        // verhindert, dass dieselbe BlockDefinition mehrfach in einem Template
        // (z. B. in unterschiedlichen Abfrage-Gruppen) verwendet werden kann.
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            try {
                $table->dropUnique('hatch_tb_template_block_unique');
            } catch (\Throwable $e) {
                // Constraint existiert ggf. bereits nicht mehr — ignorieren.
            }
        });
    }

    public function down(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            $table->dropIndex('hatch_tb_template_group_idx');
            $table->dropColumn(['group_uuid', 'visibility_rules']);
            $table->unique(['project_template_id', 'block_definition_id'], 'hatch_tb_template_block_unique');
        });
    }
};
