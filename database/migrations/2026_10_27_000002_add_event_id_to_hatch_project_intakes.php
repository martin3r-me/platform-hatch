<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional verknüpftes Event aus dem Modul events (events_events.id).
        // Bewusst ohne Fremdschlüssel: das Events-Modul ist nicht überall installiert.
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->nullable()->after('event_reference')->index();
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_intakes', function (Blueprint $table) {
            $table->dropIndex(['event_id']);
            $table->dropColumn('event_id');
        });
    }
};
