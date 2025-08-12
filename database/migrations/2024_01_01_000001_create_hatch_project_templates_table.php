<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_project_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('ai_personality')->nullable();
            $table->string('industry_context')->nullable();
            $table->enum('complexity_level', ['simple', 'medium', 'complex'])->default('medium');
            $table->json('ai_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['team_id', 'is_active'], 'hatch_pt_team_active_idx');
            $table->index(['complexity_level', 'is_active'], 'hatch_pt_complexity_idx');
            $table->index(['industry_context', 'is_active'], 'hatch_pt_industry_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_project_templates');
    }
};
