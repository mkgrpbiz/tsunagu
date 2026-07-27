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
        Schema::create('sharepoy_deposit_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sharepoy_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->date('deposit_date');
            $table->unsignedInteger('tsunagu_unit_price');
            $table->unsignedInteger('agency_unit_price');
            $table->unsignedInteger('count');
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sharepoy_deposit_records');
    }
};
