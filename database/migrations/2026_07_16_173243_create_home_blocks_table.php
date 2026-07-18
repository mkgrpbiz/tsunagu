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
        Schema::create('home_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $sortOrder = 0;

        if (Schema::hasTable('home_benefits')) {
            foreach (DB::table('home_benefits')->orderBy('group')->orderBy('sort_order')->get() as $benefit) {
                DB::table('home_blocks')->insert([
                    'type' => 'text',
                    'title' => $benefit->title,
                    'body' => $benefit->description,
                    'sort_order' => ++$sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasColumn('home_page_content', 'referral_cta_heading')) {
            $content = DB::table('home_page_content')->first();

            if ($content) {
                DB::table('home_blocks')->insert([
                    'type' => 'referral_cta',
                    'title' => $content->referral_cta_heading,
                    'body' => $content->referral_cta_body,
                    'sort_order' => ++$sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('home_blocks')->insert([
            'type' => 'sales_materials',
            'title' => '営業素材',
            'sort_order' => ++$sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('home_blocks')->insert([
            'type' => 'announcements',
            'title' => '新着情報',
            'sort_order' => ++$sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::dropIfExists('home_benefits');

        if (Schema::hasColumn('home_page_content', 'referral_cta_heading')) {
            Schema::table('home_page_content', function (Blueprint $table) {
                $table->dropColumn(['referral_cta_heading', 'referral_cta_body']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_page_content', function (Blueprint $table) {
            $table->string('referral_cta_heading')->nullable();
            $table->text('referral_cta_body')->nullable();
        });

        Schema::create('home_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::dropIfExists('home_blocks');
    }
};
