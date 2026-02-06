<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
