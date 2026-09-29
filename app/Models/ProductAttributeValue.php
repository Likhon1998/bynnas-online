<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeValue extends Model
{
    protected $fillable = ['product_attribute_id', 'value', 'slug', 'color_hex', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function swatchHex(): string
    {
        if ($this->color_hex && preg_match('/^#[0-9A-Fa-f]{6}$/', $this->color_hex)) {
            return $this->color_hex;
        }

        return color_name_to_hex($this->value);
    }
}
