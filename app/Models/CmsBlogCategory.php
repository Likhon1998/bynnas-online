<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsBlogCategory extends Model
{
    protected $table = 'cms_blog_categories';

    protected $fillable = [
        'shop_id', 'name', 'slug', 'color', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $cat) {
            if (blank($cat->slug) && filled($cat->name)) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function blogs()
    {
        return $this->hasMany(CmsBlog::class, 'category_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
