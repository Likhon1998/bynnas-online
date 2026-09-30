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

    protected static function booted(): void
    {
        $sync = function (CmsReview $review) {
            foreach (array_unique(array_filter([$review->product_id, $review->getOriginal('product_id')])) as $productId) {
                static::syncProductRating((int) $productId);
            }
        };

        static::saved($sync);
        static::deleted($sync);
    }

    /** Storefront stars/review counts come only from published reviews. */
    public static function syncProductRating(int $productId): void
    {
        $stats = static::where('product_id', $productId)
            ->where('is_published', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        Product::whereKey($productId)->update([
            'rating' => round((float) ($stats->average ?? 0), 1),
            'review_count' => (int) ($stats->total ?? 0),
        ]);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
