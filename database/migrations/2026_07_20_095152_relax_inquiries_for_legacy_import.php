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
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['invite_link_id']);
            $table->dropForeign(['line_user_id']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->unsignedBigInteger('invite_link_id')->nullable()->change();
            $table->unsignedBigInteger('line_user_id')->nullable()->change();
            $table->boolean('is_legacy_import')->default(false)->after('status');
            $table->string('legacy_line_display_name')->nullable()->after('email');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreign('invite_link_id')->references('id')->on('invite_links')->restrictOnDelete();
            $table->foreign('line_user_id')->references('id')->on('line_users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['invite_link_id']);
            $table->dropForeign(['line_user_id']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->unsignedBigInteger('invite_link_id')->nullable(false)->change();
            $table->unsignedBigInteger('line_user_id')->nullable(false)->change();
            $table->dropColumn(['is_legacy_import', 'legacy_line_display_name']);
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreign('invite_link_id')->references('id')->on('invite_links')->restrictOnDelete();
            $table->foreign('line_user_id')->references('id')->on('line_users')->restrictOnDelete();
        });
    }
};
