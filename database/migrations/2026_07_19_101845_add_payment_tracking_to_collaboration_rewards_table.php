<?php

use App\Models\CollaborationReward;
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
        Schema::table('collaboration_rewards', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->date('payment_due_date')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_due_date');
        });

        CollaborationReward::whereNull('payment_due_date')->get()->each(function (CollaborationReward $reward) {
            $reward->updateQuietly([
                'payment_due_date' => $reward->month->copy()->addMonthNoOverflow()->day(5),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collaboration_rewards', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_due_date', 'paid_at']);
        });
    }
};
