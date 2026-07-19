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
            $table->unsignedInteger('sort_order')->default(0)->after('category_id');
        });

        Project::orderBy('id')->get(['id', 'category_id'])
            ->groupBy('category_id')
            ->each(function ($projects) {
                $projects->each(function (Project $project, int $index) {
                    $project->update(['sort_order' => $index + 1]);
                });
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
