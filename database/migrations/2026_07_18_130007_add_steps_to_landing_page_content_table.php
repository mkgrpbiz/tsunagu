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
        Schema::table('landing_page_content', function (Blueprint $table) {
            $table->string('steps_title')->default('ご参加の流れ')->after('hero_suffix');
            $table->string('step1')->default('下のボタンから登録フォームへ')->after('steps_title');
            $table->string('step2')->default('プロフィールとパスワードを入力')->after('step1');
            $table->string('step3')->default('マイページから案件を選んで紹介開始')->after('step2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_page_content', function (Blueprint $table) {
            $table->dropColumn(['steps_title', 'step1', 'step2', 'step3']);
        });
    }
};
