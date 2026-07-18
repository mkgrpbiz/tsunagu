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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('invite_link_id')->constrained()->restrictOnDelete();
            $table->foreignId('line_user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('name_kana');
            $table->string('email');
            $table->string('status')->default('new');
            $table->timestamp('guidance_sent_at')->nullable();
            $table->timestamp('inquired_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
