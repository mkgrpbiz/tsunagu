<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // doctrine/dbal未導入のためSchema::table()->change()は使わず、生SQLで変更する
        // (SQLiteはVARCHAR/TEXTを型強制しないため、MySQL系接続のみ実施すれば十分)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY payment_timing TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY payment_timing VARCHAR(255) NULL');
        }
    }
};
