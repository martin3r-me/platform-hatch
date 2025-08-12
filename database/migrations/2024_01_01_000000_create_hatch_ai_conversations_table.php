<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->json('session_data')->nullable();
            $table->json('user_preferences')->nullable();
            $table->json('conversation_flow')->nullable();
            $table->string('ai_model_version')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->integer('max_tokens')->default(4000);
            $table->json('conversation_state')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['team_id', 'is_active'], 'hatch_ac_team_active_idx');
            $table->index(['ai_model_version', 'is_active'], 'hatch_ac_model_active_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_ai_conversations');
    }
};
