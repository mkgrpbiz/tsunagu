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
        DB::table('home_blocks')
            ->where('type', 'cta')
            ->where('title', 'パートナー様専用LINE')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('home_blocks')->insert([
            'type' => 'cta',
            'title' => 'パートナー様専用LINE',
            'body' => '連携用LINEになりますので、必ず追加お願いします。',
            'button_text' => '問い合わせ / LINE追加',
            'button_url' => 'https://lin.ee/PUUezt3',
            'sort_order' => (int) DB::table('home_blocks')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
