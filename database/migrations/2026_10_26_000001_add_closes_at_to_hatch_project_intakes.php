<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optionaler Zeitpunkt (UTC), zu dem eine veröffentlichte Erhebung automatisch geschlossen wird.
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->timestamp('closes_at')->nullable()->after('completed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->dropIndex(['closes_at']);
            $table->dropColumn('closes_at');
        });
    }
};
