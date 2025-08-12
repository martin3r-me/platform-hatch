<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_project_intake_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_intake_id')->constrained('hatch_project_intakes')->onDelete('cascade');
            $table->foreignId('template_block_id')->constrained('hatch_template_blocks')->onDelete('cascade');
            $table->foreignId('block_definition_id')->constrained('hatch_block_definitions')->onDelete('cascade');
            $table->json('answers')->nullable();
            $table->json('ai_interpretation')->nullable();
            $table->boolean('user_clarification_needed')->default(false);
            $table->json('ai_suggestions')->nullable();
            $table->json('validation_errors')->nullable();
            $table->decimal('ai_confidence', 3, 2)->default(0.00);
            $table->json('conversation_context')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            // Exit-Tracking (früher separate Migration)
            $table->string('exit_reason')->nullable();
            $table->json('exit_conditions_met')->nullable();
            $table->integer('message_count')->default(0);
            $table->integer('clarification_attempts')->default(0);
            $table->timestamp('exited_at')->nullable();
            $table->decimal('final_confidence', 3, 2)->nullable();
            $table->json('exit_quality_metrics')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['project_intake_id', 'is_completed'], 'hatch_pis_intake_completed_idx');
            $table->index(['template_block_id', 'is_completed'], 'hatch_pis_block_completed_idx');
            $table->index(['team_id', 'is_completed'], 'hatch_pis_team_completed_idx');
            $table->index(['user_clarification_needed', 'is_completed'], 'hatch_pis_clarification_idx');
            $table->index(['ai_confidence', 'is_completed'], 'hatch_pis_confidence_idx');
            $table->index('uuid');
            
            // Achtung: Kein Unique mehr – mehrere Steps pro Block möglich (Chatverlauf)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_project_intake_steps');
    }
};
