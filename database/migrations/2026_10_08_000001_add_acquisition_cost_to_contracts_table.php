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
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('acquisition_cost_rate', 5, 2)->nullable()->after('agency_unit_price');
            $table->unsignedInteger('acquisition_cost')->nullable()->after('acquisition_cost_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['acquisition_cost_rate', 'acquisition_cost']);
        });
    }
};
