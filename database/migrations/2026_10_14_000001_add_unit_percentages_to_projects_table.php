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
            $table->json('tsunagu_unit_percentages')->nullable()->after('tsunagu_unit_prices');
            $table->json('agency_unit_percentages')->nullable()->after('agency_unit_prices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tsunagu_unit_percentages', 'agency_unit_percentages']);
        });
    }
};
