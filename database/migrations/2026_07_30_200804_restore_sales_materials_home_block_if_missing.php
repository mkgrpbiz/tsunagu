<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('home_blocks')->where('type', 'sales_materials')->exists()) {
            return;
        }

        DB::table('home_blocks')->insert([
            'type' => 'sales_materials',
            'title' => '営業素材',
            'sort_order' => (int) DB::table('home_blocks')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
