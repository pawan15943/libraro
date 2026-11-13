<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Blog extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
        'reading_time' => 'integer',
    ];

    /**
     * Scope for published blogs only
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('published_at')
                           ->orWhere('published_at', '<=', now());
                     });
    }

    /**
     * Scope for featured blogs
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1);
    }

    /**
     * Helper to calculate reading time in minutes
     */
    public static function calculateReadingTime($content)
    {
        $wordCount = str_word_count(strip_tags($content ?? ''));
        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Get tags array safely
     */
    public function getTagsArrayAttribute()
    {
        if (is_array($this->tags)) {
            return $this->tags;
        }
        $decoded = json_decode($this->tags, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (!empty($this->tags)) {
            return array_map('trim', explode(',', $this->tags));
        }
        return [];
    }

    /**
     * Get Categories models safely
     */
    public function getCategoriesModelsAttribute()
    {
        $categoryIds = is_array($this->categories_id) 
            ? $this->categories_id 
            : (json_decode($this->categories_id, true) ?? []);

        if (!empty($categoryIds)) {
            return Category::whereIn('id', $categoryIds)->get();
        }
        return collect();
    }

    /**
     * Formatted publication date
     */
    public function getFormattedDateAttribute()
    {
        $date = $this->published_at ?? $this->created_at;
        return $date ? $date->format('M d, Y') : '';
    }

    /* ------------------------------------------------------------------
     | Language (English default, Hindi at ?lang=hi). Empty Hindi falls back to English.
     * ------------------------------------------------------------------ */

    /** Fields that have a Hindi twin column named {field}_hi. */
    public const TRANSLATABLE = [
        'page_title', 'page_content', 'excerpt', 'meta_title', 'meta_description',
        'meta_keyword', 'header_image', 'image_alt', 'focus_keyword',
    ];

    /** True when this post has a real Hindi version (title + body). */
    public function hasHindi(): bool
    {
        return !empty($this->page_title_hi) && !empty(trim(strip_tags($this->page_content_hi ?? '')));
    }

    /** The language this post is actually shown in for a requested site language. */
    public function contentLang(?string $lang): string
    {
        return ($lang === 'hi' && $this->hasHindi()) ? 'hi' : 'en';
    }

    /** A field in the given language, falling back to English. */
    public function tr(string $field, ?string $lang = 'en')
    {
        if ($lang === 'hi' && in_array($field, self::TRANSLATABLE, true)) {
            $hi = $this->{$field . '_hi'};
            if (is_string($hi) ? trim($hi) !== '' : !empty($hi)) {
                return $hi;
            }
        }
        return $this->{$field};
    }

    /** Short summary for cards and meta description. */
    public function summary(?string $lang = 'en', int $limit = 155): string
    {
        $text = $this->tr('excerpt', $lang) ?: strip_tags($this->tr('page_content', $lang) ?? '');
        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode($text))), $limit);
    }

    /** Public URL of the post in a language (English is the clean URL). */
    public function urlFor(?string $lang = 'en'): string
    {
        $url = route('blog-detail', ['slug' => $this->page_slug]);
        return $lang === 'hi' ? $url . '?lang=hi' : $url;
    }

    /** Reading time for the language shown. */
    public function readingTimeFor(?string $lang = 'en'): int
    {
        if ($lang === 'hi' && $this->hasHindi()) {
            // Devanagari is not counted by str_word_count; split on whitespace instead.
            $words = count(preg_split('/\s+/u', trim(strip_tags($this->page_content_hi)), -1, PREG_SPLIT_NO_EMPTY));
            return max(1, (int) ceil($words / 180));
        }
        return (int) ($this->reading_time ?: self::calculateReadingTime($this->page_content));
    }

    /** Cover image URL in a language, falling back to English, then the default blog image. */
    public function headerImageUrlFor(?string $lang = 'en'): string
    {
        foreach (array_unique([$this->tr('header_image', $lang), $this->header_image]) as $path) {
            if (!empty($path) && file_exists(public_path($path))) {
                return asset('public/' . $path);
            }
        }
        if (file_exists(public_path('img/site/blog-cover-1600.webp'))) {
            return asset('public/img/site/blog-cover-1600.webp');
        }
        return asset('public/img/blog.png');
    }

    /**
     * Questions and answers from the article's FAQ section, for FAQPage schema.
     * Convention (editable in CKEditor): an <h2> containing "FAQ", "Frequently asked" or "सवाल",
     * then each question as an <h3> followed by its answer paragraphs/lists.
     */
    public function faqs(?string $lang = 'en'): array
    {
        $html = $this->tr('page_content', $lang);
        if (empty($html) || !class_exists(\DOMDocument::class)) {
            return [];
        }
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="lb-root">' . $html . '</div>');
        libxml_clear_errors();
        $root = $dom->getElementById('lb-root');
        if (!$root) {
            return [];
        }

        $out = [];
        $inFaq = false;
        $q = null;
        $a = '';
        $flush = function () use (&$out, &$q, &$a) {
            if ($q !== null && trim($a) !== '') {
                $out[] = ['q' => trim($q), 'a' => trim(preg_replace('/\s+/u', ' ', $a))];
            }
            $q = null;
            $a = '';
        };
        foreach ($root->childNodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            $text = trim($node->textContent);
            if ($tag === 'h2') {
                $flush();
                $inFaq = (bool) preg_match('/faq|frequently asked|सवाल|प्रश्न/iu', $text);
                continue;
            }
            if (!$inFaq) {
                continue;
            }
            if ($tag === 'h3') {
                $flush();
                $q = $text;
            } elseif ($q !== null) {
                $a .= ' ' . $text;
            }
        }
        $flush();
        return $out;
    }

    /** BlogPosting (+ FAQPage) structured data for the language shown. */
    public function jsonLdFor(?string $lang = 'en'): array
    {
        $lang = $this->contentLang($lang);
        $url = $this->urlFor($lang);
        $logo = file_exists(public_path('img/libraro.png')) ? asset('public/img/libraro.png') : asset('public/img/favicon.ico');
        $keywords = $this->tr('meta_keyword', $lang) ?: implode(', ', $this->tags_array);

        $post = [
            '@context' => 'https://schema.org',
            '@type' => $this->schema_type ?: 'BlogPosting',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline' => \Illuminate\Support\Str::limit($this->tr('page_title', $lang), 110, ''),
            'description' => $this->tr('meta_description', $lang) ?: $this->summary($lang, 160),
            'image' => ['@type' => 'ImageObject', 'url' => $this->headerImageUrlFor($lang), 'width' => 1600, 'height' => 900],
            'inLanguage' => $lang === 'hi' ? 'hi-IN' : 'en-IN',
            'keywords' => $keywords,
            'wordCount' => count(preg_split('/\s+/u', trim(strip_tags($this->tr('page_content', $lang) ?? '')), -1, PREG_SPLIT_NO_EMPTY)),
            'datePublished' => optional($this->published_at ?? $this->created_at)->toIso8601String(),
            'dateModified' => optional($this->updated_at ?? $this->created_at)->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $this->author_name ?: 'Libraro Team', 'url' => route('about-us')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Libraro',
                'url' => route('/'),
                'logo' => ['@type' => 'ImageObject', 'url' => $logo],
            ],
        ];

        $faqs = $this->faqs($lang);
        if (!$faqs) {
            return [$post];
        }
        return [$post, [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'inLanguage' => $post['inLanguage'],
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ]];
    }

    /** Kept for older callers: English cover URL. */
    public function getHeaderImageUrlAttribute()
    {
        return $this->headerImageUrlFor('en');
    }

    /** Kept for older callers: English BlogPosting schema. */
    public function getJsonLdSchemaAttribute()
    {
        return $this->jsonLdFor('en')[0];
    }
}

