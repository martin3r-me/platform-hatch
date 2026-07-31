<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Macht template_block self-contained: alle Konfigurations-Spalten der
     * BlockDefinition werden auf dem TemplateBlock gespiegelt. Reines Schema –
     * alle Spalten nullable, kein Default, kein Backfill (siehe #02).
     */
    public function up(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            $table->string('block_type')->nullable()->after('description');
            $table->json('logic_config')->nullable()->after('block_type');
            $table->json('validation_rules')->nullable()->after('logic_config');
            $table->json('conditional_logic')->nullable()->after('validation_rules');
            $table->json('response_format')->nullable()->after('conditional_logic');
            $table->json('fallback_questions')->nullable()->after('response_format');
            $table->text('ai_prompt')->nullable()->after('fallback_questions');
            $table->json('ai_behavior')->nullable()->after('ai_prompt');
            $table->json('exit_conditions')->nullable()->after('ai_behavior');
            $table->decimal('min_confidence_threshold', 3, 2)->nullable()->after('exit_conditions');
            $table->integer('max_clarification_attempts')->nullable()->after('min_confidence_threshold');
            $table->integer('max_messages_per_block')->nullable()->after('max_clarification_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
