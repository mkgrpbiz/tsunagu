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
        Schema::table('sharepoy_deposit_records', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('inquiry_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sharepoy_deposit_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
        });
    }
};
