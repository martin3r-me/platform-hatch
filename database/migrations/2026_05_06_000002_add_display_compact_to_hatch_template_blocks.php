<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            // Wenn true, wird die Gruppe dieses Blocks im Overview-Modus als
            // kompakte Tabellenzeile dargestellt. Aufeinanderfolgende
            // compact-Gruppen mit identischer Feld-Struktur werden zu einer
            // gemeinsamen Tabelle zusammengefasst (z. B. Mo–Fr-Wochenfeedback).
            $table->boolean('display_compact')->default(false)->after('visibility_rules');
        });
    }

    public function down(): void
    {
        Schema::table('hatch_template_blocks', function (Blueprint $table) {
            $table->dropColumn('display_compact');
        });
    }
};
