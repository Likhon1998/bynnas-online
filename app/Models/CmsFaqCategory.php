<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsFaqCategory extends Model
{
    protected $table = 'cms_faq_categories';

    protected $fillable = [
        'shop_id', 'name', 'slug', 'icon', 'sort_order', 'is_active',
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

    public function faqs()
    {
        return $this->hasMany(CmsFaq::class, 'category_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
