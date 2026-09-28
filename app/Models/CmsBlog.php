<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsBlog extends Model
{
    protected $table = 'cms_blogs';

    protected $fillable = [
        'shop_id', 'category_id', 'title', 'slug', 'excerpt', 'body',
        'cover_image', 'author_name', 'is_published', 'is_featured',
        'views_count', 'reading_time', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $blog) {
            if (blank($blog->slug) && filled($blog->title)) {
                $blog->slug = Str::slug($blog->title);
            }
            if ($blog->is_published && blank($blog->published_at)) {
                $blog->published_at = now();
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(CmsBlogCategory::class, 'category_id');
    }

    public function scopePublished($query)
    {
        // Match admin UI (date-only): a post dated today is live all day,
        // even if the stored time is later this afternoon.
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhereDate('published_at', '<=', now()->toDateString());
            });
    }

    public function coverUrl(): string
    {
        if ($this->cover_image) {
            return public_storage_url($this->cover_image);
        }

        return 'https://images.unsplash.com/photo-1432888498266-38ffec3eaf0a?w=800&q=80';
    }

    public function viewsLabel(): string
    {
        $n = (int) $this->views_count;
        if ($n >= 1000) {
            return number_format($n / 1000, $n >= 10000 ? 0 : 1).'K Views';
        }

        return $n.' '.( $n === 1 ? 'View' : 'Views');
    }

    public function readingTimeLabel(): string
    {
        $minutes = (int) ($this->reading_time ?: max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200)));

        return $minutes.' min read';
    }
}
