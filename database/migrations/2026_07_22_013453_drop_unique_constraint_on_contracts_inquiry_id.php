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
        // MySQLは外部キーを支えるインデックスが常に1つ以上存在しないと
        // UNIQUE制約を外せないため、先に非UNIQUEの索引を別ステートメントで追加してから外す
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('inquiry_id', 'contracts_inquiry_id_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique('contracts_inquiry_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unique('inquiry_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_inquiry_id_index');
        });
    }
};
