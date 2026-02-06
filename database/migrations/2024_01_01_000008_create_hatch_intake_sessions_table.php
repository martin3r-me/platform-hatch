<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_intake_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('session_token', 64)->unique();
            $table->foreignId('project_intake_id')->constrained('hatch_project_intakes')->onDelete('cascade');
            $table->string('status')->default('started');
            $table->json('answers')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_email')->nullable();
            $table->integer('current_step')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_intake_id', 'status'], 'hatch_is_intake_status_idx');
            $table->index('session_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_intake_sessions');
    }
};
