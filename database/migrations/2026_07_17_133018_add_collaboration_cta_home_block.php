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
        DB::table('home_blocks')->insert([
            'type' => 'collaboration_cta',
            'title' => '協業パートナーを紹介したい',
            'body' => '取引先や協業できそうな事業者様がいらっしゃれば、下記フォームよりご紹介ください。',
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
        DB::table('home_blocks')->where('type', 'collaboration_cta')->delete();
    }
};
