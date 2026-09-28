<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    protected $table = 'cms_pages';

    protected $fillable = [
        'shop_id', 'title', 'slug', 'excerpt', 'body',
        'meta_title', 'meta_description', 'show_in_footer',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'show_in_footer' => 'boolean',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            if (blank($page->slug) && filled($page->title)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
