<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsFaq extends Model
{
    protected $table = 'cms_faqs';

    protected $fillable = [
        'shop_id', 'category_id', 'question', 'answer', 'category',
        'sort_order', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function faqCategory()
    {
        return $this->belongsTo(CmsFaqCategory::class, 'category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
