<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Rewritten, SEO-led blog content (English + Hindi) for the existing posts.
 *
 *   php artisan db:seed --class=BlogContentSeeder
 *
 * One file per post in database/seeders/blog-content/{slug}.php. Posts are matched by slug, so ids,
 * URLs, views and publish dates stay as they are. Safe to run again: it overwrites the same fields.
 *
 * Link placeholders inside the HTML are resolved per language when seeding:
 *   {{blog:some-slug}}  → that post (Hindi version on the Hindi page)
 *   {{register}}  {{find}}  {{blog}}  {{home}}
 */
class BlogContentSeeder extends Seeder
{
    /** Clean topic set used by the blog hub. */
    public const TOPICS = [
        'software'   => 'Library Software',
        'seats'      => 'Seats & Attendance',
        'fees'       => 'Fees & Finance',
        'operations' => 'Library Operations',
        'business'   => 'Start a Library Business',
    ];

    public function run(): void
    {
        $topicIds = [];
        foreach (self::TOPICS as $key => $name) {
            $topicIds[$key] = Category::firstOrCreate(['name' => $name])->id;
        }

        $files = glob(database_path('seeders/blog-content/*.php'));
        sort($files);
        $done = 0;

        foreach ($files as $file) {
            $post = require $file;
            $blog = Blog::where('page_slug', $post['slug'])->first();
            if (!$blog) {
                $this->command?->warn("Skipped {$post['slug']}: no post with this slug.");
                continue;
            }

            $en = $post['en'];
            $hi = $post['hi'] ?? null;

            $blog->fill([
                'page_title'       => $en['title'],
                'meta_title'       => $en['meta_title'],
                'meta_description' => $en['meta_description'],
                'meta_keyword'     => implode(', ', $en['keywords']),
                'focus_keyword'    => $en['focus_keyword'],
                'excerpt'          => $en['excerpt'],
                'image_alt'        => $en['image_alt'],
                'page_content'     => $this->links($en['content'], 'en'),
                'tags'             => json_encode($post['tags'], JSON_UNESCAPED_UNICODE),
                'categories_id'    => json_encode([$topicIds[$post['topic']]]),
                'is_featured'      => !empty($post['featured']) ? 1 : 0,
                'author_name'      => 'Libraro Team',
                'schema_type'      => 'BlogPosting',
                'meta_robots'      => 'index, follow',
                'canonical_url'    => null,
                'status'           => 'published',
            ]);
            $blog->reading_time = Blog::calculateReadingTime($blog->page_content);

            $cover = 'img/blog/' . $post['slug'] . '.webp';
            if (file_exists(public_path($cover))) {
                $blog->header_image = $cover;
            }

            if ($hi) {
                $blog->fill([
                    'page_title_hi'       => $hi['title'],
                    'meta_title_hi'       => $hi['meta_title'],
                    'meta_description_hi' => $hi['meta_description'],
                    'meta_keyword_hi'     => implode(', ', $hi['keywords']),
                    'focus_keyword_hi'    => $hi['focus_keyword'],
                    'excerpt_hi'          => $hi['excerpt'],
                    'image_alt_hi'        => $hi['image_alt'],
                    'page_content_hi'     => $this->links($hi['content'], 'hi'),
                ]);
                $coverHi = 'img/blog/' . $post['slug'] . '-hi.webp';
                $blog->header_image_hi = file_exists(public_path($coverHi)) ? $coverHi : null;
            }

            $blog->save();
            $done++;
        }

        $this->command?->info("Blog content updated for {$done} posts.");
    }

    /** Resolve link placeholders for one language. */
    private function links(string $html, string $lang): string
    {
        $q = $lang === 'hi' ? '?lang=hi' : '';
        $html = preg_replace_callback('/\{\{blog:([a-z0-9-]+)\}\}/', fn ($m) => route('blog-detail', ['slug' => $m[1]]) . $q, $html);
        return strtr($html, [
            '{{register}}' => route('register'),
            '{{find}}'     => route('find-my-library') . $q,
            '{{blog}}'     => route('blog') . $q,
            '{{home}}'     => route('/') . $q,
        ]);
    }
}
