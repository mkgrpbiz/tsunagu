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
        Schema::table('landing_page_content', function (Blueprint $table) {
            $table->string('benefits_title')->default('パートナーのメリット')->after('step3');
            $table->text('benefits_body')->nullable()->after('benefits_title');
        });

        DB::table('landing_page_content')->update([
            'benefits_body' => <<<'TEXT'
                紹介導線を収益化|既にお持ちのSNS・コミュニティ・人脈などを活かして収益化できます。
                無理な営業は不要|営業活動やクロージングは不要。つなぐことに集中できます。
                自由に案件を選択可能|ご自身のSNS・コミュニティに合った案件だけを自由に選んでご利用いただけます。
                横のつながりも収益につながる|人脈や取引先のご紹介など、横のつながりから継続的な収益化も目指せます。
                TEXT,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_page_content', function (Blueprint $table) {
            $table->dropColumn(['benefits_title', 'benefits_body']);
        });
    }
};
