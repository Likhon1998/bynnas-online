<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsReview extends Model
{
    protected $table = 'cms_reviews';

    protected $fillable = [
        'shop_id', 'product_id', 'customer_name', 'customer_title',
        'rating', 'body', 'avatar_path', 'is_featured',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'rating' => 'integer',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
