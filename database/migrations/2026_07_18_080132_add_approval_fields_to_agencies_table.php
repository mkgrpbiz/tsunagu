<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('referred_by_agency_id');
            $table->text('review_note')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('review_note');
            $table->foreignId('approved_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('approved_by_user_id');

            $table->string('activity_type')->nullable()->after('last_login_at');
            $table->json('desired_activities')->nullable()->after('activity_type');
            $table->text('current_activity')->nullable()->after('desired_activities');
            $table->text('track_record')->nullable()->after('current_activity');
            $table->text('media_urls')->nullable()->after('track_record');
            $table->text('self_pr')->nullable()->after('media_urls');
        });

        // Existing agencies predate the approval system — treat them as already approved
        // so nobody currently using the platform gets locked out by this migration.
        DB::table('agencies')->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn([
                'status', 'review_note', 'approved_at', 'last_login_at',
                'activity_type', 'desired_activities', 'current_activity', 'track_record', 'media_urls', 'self_pr',
            ]);
        });
    }
};
