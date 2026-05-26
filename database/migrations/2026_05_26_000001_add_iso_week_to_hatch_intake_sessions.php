<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            // ISO-8601 Kalenderwoche (1–53) und zugehöriges ISO-Jahr, automatisch
            // beim Anlegen einer Session gestempelt. Erlaubt Auswertungen pro KW
            // ohne dass für jede KW ein neuer Intake angelegt werden muss.
            $table->unsignedTinyInteger('iso_week')->nullable()->after('status');
            $table->unsignedSmallInteger('iso_year')->nullable()->after('iso_week');

            $table->index(['iso_year', 'iso_week'], 'hatch_intake_sessions_iso_year_week_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->dropIndex('hatch_intake_sessions_iso_year_week_idx');
            $table->dropColumn(['iso_week', 'iso_year']);
        });
    }
};
