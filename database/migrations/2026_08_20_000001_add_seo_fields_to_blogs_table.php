<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Idempotent: each column is added only if it does not already exist,
     * so it is safe to run on databases that were partially patched by hand.
     */
    public function up(): void
    {
        $columns = [
            'excerpt'       => fn (Blueprint $t) => $t->text('excerpt')->nullable()->after('page_content'),
            'author_name'   => fn (Blueprint $t) => $t->string('author_name')->default('Libraro Team')->after('excerpt'),
            'author_image'  => fn (Blueprint $t) => $t->string('author_image')->nullable()->after('author_name'),
            'status'        => fn (Blueprint $t) => $t->string('status')->default('published')->after('author_image'), // published, draft, scheduled
            'published_at'  => fn (Blueprint $t) => $t->timestamp('published_at')->nullable()->after('status'),
            'canonical_url' => fn (Blueprint $t) => $t->string('canonical_url')->nullable()->after('published_at'),
            'meta_robots'   => fn (Blueprint $t) => $t->string('meta_robots')->default('index, follow')->after('canonical_url'),
            'image_alt'     => fn (Blueprint $t) => $t->string('image_alt')->nullable()->after('header_image'),
            'focus_keyword' => fn (Blueprint $t) => $t->string('focus_keyword')->nullable()->after('meta_og'),
            'schema_type'   => fn (Blueprint $t) => $t->string('schema_type')->default('BlogPosting')->after('focus_keyword'),
            'reading_time'  => fn (Blueprint $t) => $t->integer('reading_time')->default(1)->after('schema_type'),
            'views_count'   => fn (Blueprint $t) => $t->unsignedBigInteger('views_count')->default(0)->after('reading_time'),
            'is_featured'   => fn (Blueprint $t) => $t->boolean('is_featured')->default(0)->after('views_count'),
        ];

        // Added one at a time so each "after" anchor already exists when referenced
        foreach ($columns as $name => $define) {
            if (!Schema::hasColumn('blogs', $name)) {
                Schema::table('blogs', function (Blueprint $table) use ($define) {
                    $define($table);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
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
        ], fn ($c) => Schema::hasColumn('blogs', $c)));

        if ($columns) {
            Schema::table('blogs', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
