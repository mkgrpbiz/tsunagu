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
        Schema::create('collaboration_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->date('month');
            $table->unsignedInteger('reward_amount')->nullable();
            $table->string('status')->default('pending_approval');
            $table->timestamps();

            $table->unique(['client_name', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaboration_rewards');
    }
};
