<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eigene Platzhalter eines Teams (z. B. "Standort", "Firmenname").
        // Werte pro Erhebung liegen in hatch_project_intakes.intake_settings.placeholder_values.
        Schema::create('hatch_placeholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('description')->nullable();
            $table->text('default_value')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_placeholders');
    }
};
