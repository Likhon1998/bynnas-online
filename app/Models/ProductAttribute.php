<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    public const TYPE_SELECT = 'select';
    public const TYPE_COLOR = 'color';

    public const TYPES = [
        self::TYPE_SELECT => 'Options (chips)',
        self::TYPE_COLOR => 'Color swatches',
    ];

    /** Attributes created for every shop (matches the backfill migration). */
    public const DEFAULTS = [
        ['name' => 'Color', 'slug' => 'color', 'type' => self::TYPE_COLOR],
        ['name' => 'Size', 'slug' => 'size', 'type' => self::TYPE_SELECT],
        ['name' => 'Material', 'slug' => 'material', 'type' => self::TYPE_SELECT],
        ['name' => 'Storage', 'slug' => 'storage', 'type' => self::TYPE_SELECT],
        ['name' => 'RAM', 'slug' => 'ram', 'type' => self::TYPE_SELECT],
        ['name' => 'Weight', 'slug' => 'weight', 'type' => self::TYPE_SELECT],
    ];

    protected $fillable = ['shop_id', 'name', 'slug', 'type', 'is_filterable', 'sort_order'];

    protected $casts = [
        'is_filterable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order')->orderBy('value');
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function isColor(): bool
    {
        return $this->type === self::TYPE_COLOR;
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId)->orderBy('sort_order')->orderBy('name');
    }

    /** Seed the default attribute set for shops that have none yet (deleted defaults stay deleted). */
    public static function ensureDefaults(int $shopId): void
    {
        if (self::where('shop_id', $shopId)->exists()) {
            return;
        }

        foreach (self::DEFAULTS as $i => $def) {
            self::firstOrCreate(
                ['shop_id' => $shopId, 'slug' => $def['slug']],
                ['name' => $def['name'], 'type' => $def['type'], 'is_filterable' => true, 'sort_order' => $i + 1],
            );
        }
    }

    public static function uniqueSlug(int $shopId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'attribute';
        $slug = $base;
        $n = 2;
        while (self::where('shop_id', $shopId)->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
