<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Interne Veranstaltungsnummer – nur für das Team, nie im Public-View.
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->string('event_reference', 100)->nullable()->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->dropIndex(['event_reference']);
            $table->dropColumn('event_reference');
        });
    }
};
