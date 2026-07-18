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
        Schema::create('home_page_content', function (Blueprint $table) {
            $table->id();
            $table->string('hero_tagline');
            $table->text('closing_message');
            $table->string('referral_cta_heading');
            $table->text('referral_cta_body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_page_content');
    }
};
