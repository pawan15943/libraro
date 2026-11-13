<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Hindi version of each blog post. Served at the same URL with ?lang=hi (the website's existing
 * language switch), with its own canonical + hreflang. Empty Hindi fields fall back to English.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Long-form guides outgrow TEXT (64 KB); no doctrine/dbal here, so change it directly.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE blogs MODIFY page_content LONGTEXT NOT NULL');
        }

        Schema::table('blogs', function (Blueprint $table) {
            $table->string('page_title_hi')->nullable()->after('page_title');
            $table->longText('page_content_hi')->nullable()->after('page_content');
            $table->text('excerpt_hi')->nullable()->after('excerpt');
            $table->string('meta_title_hi')->nullable()->after('meta_title');
            $table->text('meta_description_hi')->nullable()->after('meta_description');
            $table->text('meta_keyword_hi')->nullable()->after('meta_keyword');
            $table->string('header_image_hi')->nullable()->after('header_image');
            $table->string('image_alt_hi')->nullable()->after('image_alt');
            $table->string('focus_keyword_hi')->nullable()->after('focus_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn([
                'page_title_hi', 'page_content_hi', 'excerpt_hi', 'meta_title_hi', 'meta_description_hi',
                'meta_keyword_hi', 'header_image_hi', 'image_alt_hi', 'focus_keyword_hi',
            ]);
        });
    }
};
