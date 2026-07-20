<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('tsunagu_unit_price')->nullable()->change();
            $table->unsignedInteger('agency_unit_price')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('tsunagu_unit_price')->nullable(false)->change();
            $table->unsignedInteger('agency_unit_price')->nullable(false)->change();
        });
    }
};
