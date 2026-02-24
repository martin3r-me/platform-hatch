<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hatch_lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name'); // slug
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('hatch_lookup_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_id')->constrained('hatch_lookups')->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['lookup_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hatch_lookup_values');
        Schema::dropIfExists('hatch_lookups');
    }
};
