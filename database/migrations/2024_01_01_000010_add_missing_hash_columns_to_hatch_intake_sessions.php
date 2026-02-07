<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->string('answers_hash')->nullable()->after('answers');
            $table->string('metadata_hash')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_intake_sessions', function (Blueprint $table) {
            $table->dropColumn(['answers_hash', 'metadata_hash']);
        });
    }
};
