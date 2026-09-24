<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eigener Dank-Text nach dem Abschließen einer Umfrage (null = Standardtext).
        Schema::table('hatch_project_templates', function (Blueprint $table) {
            $table->text('completion_message')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_project_templates', function (Blueprint $table) {
            $table->dropColumn('completion_message');
        });
    }
};
