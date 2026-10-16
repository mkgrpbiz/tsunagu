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
        Schema::create('bimoni_sharepoy_link_logs', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->longText('pasted_text');
            $table->json('summary');
            $table->unsignedInteger('saved_count');
            $table->unsignedInteger('unmatched_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bimoni_sharepoy_link_logs');
    }
};
