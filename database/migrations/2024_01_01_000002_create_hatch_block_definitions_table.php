<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
            // Exit/Qualität (früher separate Migration)
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

    public function down(): void
    {
        Schema::dropIfExists('hatch_block_definitions');
    }
};
