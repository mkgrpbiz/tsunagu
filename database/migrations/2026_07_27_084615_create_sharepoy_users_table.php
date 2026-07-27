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
        Schema::create('sharepoy_users', function (Blueprint $table) {
            $table->id();
            $table->string('sharepoy_user_id')->unique();
            $table->string('referrer_sharepoy_user_id')->nullable();
            $table->string('name');
            $table->string('name_kana');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sharepoy_users');
    }
};
