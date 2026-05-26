<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            // Owner-seitige Intake-Konfiguration (z. B. week_cutoff für die
            // automatische KW-Zuordnung der Sessions). Bewusst getrennt von
            // user_preferences, das Respondenten-bezogene Daten enthält.
            $table->json('intake_settings')->nullable()->after('user_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->dropColumn('intake_settings');
        });
    }
};
