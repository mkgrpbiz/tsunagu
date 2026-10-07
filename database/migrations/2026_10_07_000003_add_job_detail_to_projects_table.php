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
            $table->text('job_description')->nullable()->after('employment_type');
            $table->text('work_location')->nullable()->after('job_description');
            $table->text('annual_income')->nullable()->after('work_location');
            $table->text('salary_benefits')->nullable()->after('annual_income');
            $table->text('holidays')->nullable()->after('salary_benefits');
            $table->text('qualifications')->nullable()->after('holidays');
            $table->text('age_requirement')->nullable()->after('qualifications');
            $table->text('working_hours')->nullable()->after('age_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'job_description',
                'work_location',
                'annual_income',
                'salary_benefits',
                'holidays',
                'qualifications',
                'age_requirement',
                'working_hours',
            ]);
        });
    }
};
