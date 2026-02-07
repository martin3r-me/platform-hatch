<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->string('respondent_name_hash')->nullable()->after('respondent_email')->index();
            $table->string('respondent_email_hash')->nullable()->after('respondent_name_hash')->index();
            $table->string('answers_hash')->nullable()->after('answers');
            $table->string('metadata_hash')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->dropIndex(['respondent_name_hash']);
            $table->dropIndex(['respondent_email_hash']);
            $table->dropColumn(['respondent_name_hash', 'respondent_email_hash', 'answers_hash', 'metadata_hash']);
        });
    }
};
