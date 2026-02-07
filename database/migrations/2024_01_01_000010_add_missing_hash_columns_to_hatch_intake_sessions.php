<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->longText('answers')->nullable()->change();
            $table->longText('metadata')->nullable()->change();
            $table->string('answers_hash')->nullable()->after('answers');
            $table->string('metadata_hash')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            // Nicht zurueck auf json aendern - verschluesselte Daten
            // wuerden den Rollback auf json brechen (kein gueltiges JSON).
            $table->dropColumn(['answers_hash', 'metadata_hash']);
        });
    }
};
