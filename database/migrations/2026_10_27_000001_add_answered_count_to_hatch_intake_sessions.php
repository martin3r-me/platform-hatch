<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Platform\Hatch\Models\HatchIntakeSession;

return new class extends Migration
{
    public function up(): void
    {
        // Anzahl tatsächlich beantworteter Felder. Antworten liegen verschlüsselt vor –
        // ohne diese Spalte kann die Datenbank „nur geöffnet, nichts beantwortet“ nicht erkennen.
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('answered_count')->default(0)->after('current_step');
            $table->index(['project_intake_id', 'answered_count'], 'hatch_is_intake_answered_idx');
        });

        // Bestand nachrechnen
        HatchIntakeSession::query()->with('projectIntake.projectTemplate.templateBlocks')->chunkById(200, function ($sessions) {
            foreach ($sessions as $session) {
                try {
                    $session->answered_count = $session->computeAnsweredCount();
                } catch (\Throwable $e) {
                    $session->answered_count = 1;   // nicht entschlüsselbar → lieber mitzählen als verstecken
                }
                $session->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->dropIndex('hatch_is_intake_answered_idx');
            $table->dropColumn('answered_count');
        });
    }
};
