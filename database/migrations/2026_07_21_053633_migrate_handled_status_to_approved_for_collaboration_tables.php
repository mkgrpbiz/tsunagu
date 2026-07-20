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
        DB::table('collaboration_referrals')->where('status', 'handled')->update(['status' => 'approved']);
        DB::table('collaboration_partner_applications')->where('status', 'handled')->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('collaboration_referrals')->where('status', 'approved')->update(['status' => 'handled']);
        DB::table('collaboration_partner_applications')->where('status', 'approved')->update(['status' => 'handled']);
    }
};
