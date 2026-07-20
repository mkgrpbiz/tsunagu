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
            'type' => 'collaboration_partner_application_cta',
            'title' => '共創パートナー申請',
            'body' => '共同事業や新サービスの開発など、継続的な共創にご興味があれば、下記フォームよりご申請ください。',
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
        DB::table('home_blocks')->where('type', 'collaboration_partner_application_cta')->delete();
    }
};
