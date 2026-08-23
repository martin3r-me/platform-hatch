<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration #06: Tabelle hatch_block_definitions droppen.
 *
 * Letzter Schritt der block_definition-Ablösung. Config und block_type sind
 * seit #02/#03 vollständig auf hatch_template_blocks gespiegelt; die letzten
 * beiden FKs auf diese Tabelle wurden entfernt:
 *  - hatch_project_intake_steps.block_definition_id in #05 (2026_08_22_000002)
 *  - hatch_template_blocks.block_definition_id     in #04 (2026_08_22_000004)
 *
 * Muss daher chronologisch NACH #04/#05 laufen, sonst blockiert ein noch
 * bestehender FK den Table-Drop.
 *
 * down() rekonstruiert das Schema aus der ursprünglichen create-Migration
 * (2024_01_01_000002) – Daten sind nach dem Drop nicht wiederherstellbar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hatch_block_definitions');
    }

    public function down(): void
    {
        Schema::create('hatch_block_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('block_type');
            $table->text('ai_prompt')->nullable();
            $table->json('conditional_logic')->nullable();
            $table->json('response_format')->nullable();
            $table->json('fallback_questions')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('logic_config')->nullable();
            $table->json('ai_behavior')->nullable();
            $table->json('exit_conditions')->nullable();
            $table->decimal('min_confidence_threshold', 3, 2)->default(0.80);
            $table->integer('max_clarification_attempts')->default(3);
            $table->integer('max_messages_per_block')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();

            $table->index(['team_id', 'is_active'], 'hatch_bd_team_active_idx');
            $table->index(['block_type', 'is_active'], 'hatch_bd_type_active_idx');
            $table->index('uuid');
        });
    }
};
