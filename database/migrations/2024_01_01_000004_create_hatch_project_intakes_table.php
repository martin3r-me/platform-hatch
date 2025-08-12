<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_project_intakes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_template_id')->constrained('hatch_project_templates')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('ai_conversation_id')->nullable()->constrained('hatch_ai_conversations')->onDelete('set null');
            $table->string('thread_id')->nullable();
            $table->string('workflow_instance_id')->nullable();
            $table->string('next_step_trigger')->nullable();
            $table->string('workflow_status')->default('running');
            $table->integer('current_step')->default(0);
            $table->json('conversation_history')->nullable();
            $table->decimal('ai_confidence_score', 3, 2)->default(0.00);
            $table->json('user_preferences')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('owned_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['team_id', 'status', 'is_active'], 'hatch_pi_team_status_idx');
            $table->index(['owned_by_user_id', 'status'], 'hatch_pi_user_status_idx');
            $table->index(['project_template_id', 'status'], 'hatch_pi_template_status_idx');
            $table->index(['ai_confidence_score', 'status'], 'hatch_pi_confidence_idx');
            $table->index(['thread_id', 'workflow_status'], 'hatch_pi_thread_status_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_project_intakes');
    }
};
