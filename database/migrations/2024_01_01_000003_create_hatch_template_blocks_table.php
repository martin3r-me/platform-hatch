<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_template_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_template_id')->constrained('hatch_project_templates')->onDelete('cascade');
            $table->foreignId('block_definition_id')->nullable()->constrained('hatch_block_definitions')->onDelete('cascade');
            $table->string('name')->nullable(); // Block-Name (optional, da über BlockDefinition verfügbar)
            $table->text('description')->nullable(); // Block-Beschreibung (optional, da über BlockDefinition verfügbar)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['project_template_id', 'is_active'], 'hatch_tb_template_active_idx');
            $table->index(['block_definition_id', 'is_active'], 'hatch_tb_block_active_idx');
            $table->index(['sort_order', 'is_active'], 'hatch_tb_sort_active_idx');
            $table->index('uuid');
            
            // Unique constraint für Template + Block Definition Kombination
            $table->unique(['project_template_id', 'block_definition_id'], 'hatch_tb_template_block_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_template_blocks');
    }
};
