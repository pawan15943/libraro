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
        Schema::table('blogs', function (Blueprint $table) {
            $table->text('excerpt')->nullable()->after('page_content');
            $table->string('author_name')->default('Libraro Team')->after('excerpt');
            $table->string('author_image')->nullable()->after('author_name');
            $table->string('status')->default('published')->after('author_image'); // published, draft, scheduled
            $table->timestamp('published_at')->nullable()->after('status');
            $table->string('canonical_url')->nullable()->after('published_at');
            $table->string('meta_robots')->default('index, follow')->after('canonical_url');
            $table->string('image_alt')->nullable()->after('header_image');
            $table->string('focus_keyword')->nullable()->after('meta_og');
            $table->string('schema_type')->default('BlogPosting')->after('focus_keyword');
            $table->integer('reading_time')->default(1)->after('schema_type');
            $table->unsignedBigInteger('views_count')->default(0)->after('reading_time');
            $table->boolean('is_featured')->default(0)->after('views_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn([
                'excerpt',
                'author_name',
                'author_image',
                'status',
                'published_at',
                'canonical_url',
                'meta_robots',
                'image_alt',
                'focus_keyword',
                'schema_type',
                'reading_time',
                'views_count',
                'is_featured',
            ]);
        });
    }
};
