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

    /**
     * Get image URL with fallback to local blog image or dynamic SVG placeholder
     */
    public function getHeaderImageUrlAttribute()
    {
        if (!empty($this->header_image) && file_exists(public_path($this->header_image))) {
            return asset('public/' . $this->header_image);
        }
        if (file_exists(public_path('img/blog.png'))) {
            return asset('public/img/blog.png');
        }
        return 'https://placehold.co/800x450/1e3c72/ffffff?text=' . urlencode($this->page_title ?: 'Libraro Blog');
    }

    /**
     * Return JSON-LD Schema array for SEO
     */
    public function getJsonLdSchemaAttribute()
    {
        $imageUrl = $this->header_image ? asset('public/' . $this->header_image) : asset('public/img/blog.png');
        $url = route('blog-detail', ['slug' => $this->page_slug]);

        return [
            '@context' => 'https://schema.org',
            '@type' => $this->schema_type ?: 'BlogPosting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
            'headline' => $this->meta_title ?: $this->page_title,
            'description' => $this->meta_description ?: ($this->excerpt ?: substr(strip_tags($this->page_content), 0, 160)),
            'image' => [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
            ],
            'datePublished' => $this->published_at ? $this->published_at->toIso8601String() : $this->created_at->toIso8601String(),
            'dateModified' => $this->updated_at ? $this->updated_at->toIso8601String() : $this->created_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $this->author_name ?: 'Libraro Team',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Libraro'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('public/img/favicon.ico'),
                ],
            ],
        ];
    }
}

