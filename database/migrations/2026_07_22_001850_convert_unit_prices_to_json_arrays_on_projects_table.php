<?php

use App\Models\Project;
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
            $table->json('tsunagu_unit_prices')->nullable()->after('tsunagu_unit_price');
            $table->json('agency_unit_prices')->nullable()->after('agency_unit_price');
        });

        Project::query()->each(function (Project $project) {
            $project->updateQuietly([
                'tsunagu_unit_prices' => $project->tsunagu_unit_price === null ? null : [$project->tsunagu_unit_price],
                'agency_unit_prices' => $project->agency_unit_price === null ? null : [$project->agency_unit_price],
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tsunagu_unit_price', 'agency_unit_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('tsunagu_unit_price')->nullable()->after('tsunagu_unit_prices');
            $table->unsignedInteger('agency_unit_price')->nullable()->after('agency_unit_prices');
        });

        Project::query()->each(function (Project $project) {
            $project->updateQuietly([
                'tsunagu_unit_price' => $project->tsunagu_unit_prices[0] ?? null,
                'agency_unit_price' => $project->agency_unit_prices[0] ?? null,
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tsunagu_unit_prices', 'agency_unit_prices']);
        });
    }
};
