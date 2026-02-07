<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE hatch_intake_sessions MODIFY answers LONGTEXT NULL');
        DB::statement('ALTER TABLE hatch_intake_sessions MODIFY metadata LONGTEXT NULL');
    }

    public function down(): void
    {
        // Nicht zurueck auf JSON aendern - verschluesselte Daten
        // sind kein gueltiges JSON und wuerden den Rollback brechen.
    }
};
