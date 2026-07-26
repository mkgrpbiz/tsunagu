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
        Schema::table('home_page_content', function (Blueprint $table) {
            $table->string('og_image_path')->nullable()->after('app_icon_source_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_page_content', function (Blueprint $table) {
            $table->dropColumn('og_image_path');
        });
    }
};
