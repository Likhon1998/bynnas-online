<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'category_id', 'brand_id', 'name', 'slug', 'barcode', 'sku',
        'variant_group', 'color', 'color_hex', 'storage', 'ram',
        'requires_imei',
        'cost_price', 'selling_price', 'original_price',
        'sale_price', 'sale_starts_at', 'sale_ends_at',
        'pos_discount_type', 'pos_discount_value',
        'stock_quantity', 'reserved_stock', 'availability', 'filter_attributes', 'alert_quantity', 'reorder_quantity',
        'image', 'image_2', 'image_3',
        'short_description', 'description', 'brand_name', 'rating', 'review_count',
        'seo_title', 'meta_description', 'og_title', 'og_description', 'og_image',
        'is_best_seller', 'is_featured', 'is_new_arrival', 'is_published',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->getAttributes()['slug'] ?? null) && filled($product->name)) {
                $product->slug = static::uniqueSlug((int) $product->shop_id, (string) $product->name, $product->id);
            }
        });
    }

    public function getSlugAttribute(?string $value): string
    {
        return filled($value) ? $value : (string) $this->getKey();
    }

    public static function uniqueSlug(int $shopId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $n = 2;
        while (static::where('shop_id', $shopId)->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /** Storefront links use the slug; numeric IDs keep old links and admin routes working. */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug') {
            return static::where('slug', $value)->first()
                ?? (ctype_digit((string) $value) ? static::find((int) $value) : null);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'pos_discount_value' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'rating' => 'decimal:1',
        'is_best_seller' => 'boolean',
        'is_featured' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_published' => 'boolean',
        'requires_imei' => 'boolean',
        'filter_attributes' => 'array',
        'reserved_stock' => 'integer',
    ];

    /** Physical units on hand (warehouse/store). */
    public function physicalStock(): int
    {
        return max(0, (int) $this->stock_quantity);
    }

    /** Units held for unpaid/packing web COD orders. */
    public function reservedStock(): int
    {
        return max(0, (int) ($this->reserved_stock ?? 0));
    }

    /** Sellable units = physical − reserved. */
    public function availableStock(): int
    {
        return max(0, $this->physicalStock() - $this->reservedStock());
    }

    public function scopeAvailableForSale($query)
    {
        return $query->whereRaw('stock_quantity > COALESCE(reserved_stock, 0)');
    }

    public function scopeInStockVisible($query)
    {
        return $query->availableForSale();
    }

    /** Stored gallery paths (new table first, then legacy columns). */
    public function imagePaths(): array
    {
        $gallery = $this->relationLoaded('galleryImages')
            ? $this->galleryImages
            : $this->galleryImages()->get();

        if ($gallery->isNotEmpty()) {
            return $gallery->pluck('path')->filter()->values()->all();
        }

        return array_values(array_filter([
            $this->image,
            $this->image_2,
            $this->image_3,
        ]));
    }

    public function galleryImages()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variantValues()
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    /**
     * Attribute values for this row, ordered like the attribute list (falls back to legacy columns).
     *
     * @return Collection<int, array{attribute_id: int|null, slug: string, attribute: string, type: string, value: string, value_slug: string, hex: string|null}>
     */
    public function variantOptions(): Collection
    {
        $rows = $this->relationLoaded('variantValues')
            ? $this->variantValues
            : $this->variantValues()->with(['attribute', 'attributeValue'])->get();
        $rows->loadMissing(['attribute', 'attributeValue']);

        $options = $rows
            ->filter(fn (ProductVariantValue $v) => $v->attribute && $v->attributeValue)
            ->sortBy(fn (ProductVariantValue $v) => [$v->attribute->sort_order, $v->attribute->name])
            ->map(fn (ProductVariantValue $v) => [
                'attribute_id' => (int) $v->attribute->id,
                'slug' => (string) $v->attribute->slug,
                'attribute' => (string) $v->attribute->name,
                'type' => (string) $v->attribute->type,
                'value' => (string) $v->attributeValue->value,
                'value_slug' => (string) $v->attributeValue->slug,
                'hex' => $v->attribute->isColor() ? $v->attributeValue->swatchHex() : null,
            ])
            ->values();

        if ($options->isNotEmpty()) {
            return $options;
        }

        $legacy = collect();
        foreach (['color' => 'Color', 'storage' => 'Storage', 'ram' => 'RAM'] as $column => $label) {
            if (filled($this->{$column})) {
                $legacy->push([
                    'attribute_id' => null,
                    'slug' => $column,
                    'attribute' => $label,
                    'type' => $column === 'color' ? ProductAttribute::TYPE_COLOR : ProductAttribute::TYPE_SELECT,
                    'value' => (string) $this->{$column},
                    'value_slug' => Str::slug((string) $this->{$column}),
                    'hex' => $column === 'color' ? $this->swatchHex() : null,
                ]);
            }
        }

        return $legacy;
    }

    /** "Red / XL" style label of the variant options. */
    public function variantLabel(): string
    {
        return $this->variantOptions()->pluck('value')->implode(' / ');
    }

    public function seoTitle(): string
    {
        return (string) ($this->seo_title ?: $this->storefrontDisplayName());
    }

    public function seoDescription(): string
    {
        $text = $this->meta_description ?: ($this->short_description ?: strip_tags((string) $this->description));

        return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $text) ?? ''), 160, '…');
    }

    public function imeis()
    {
        return $this->hasMany(ProductImei::class);
    }

    public function availableImeis()
    {
        return $this->hasMany(ProductImei::class)->available()->orderBy('id');
    }

    /** Sync IMEI list (available only). Returns count of available IMEIs. */
    public function syncAvailableImeis(array $imeis): int
    {
        $normalized = collect($imeis)
            ->map(fn ($v) => ProductImei::normalize((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values();

        $keep = $normalized->all();

        // Remove available IMEIs no longer in the list (never delete sold history).
        $query = $this->imeis()->available();
        if ($keep !== []) {
            $query->whereNotIn('imei', $keep);
        }
        $query->delete();

        foreach ($normalized as $imei) {
            $existing = ProductImei::where('imei', $imei)->first();
            if ($existing) {
                if ((int) $existing->product_id === (int) $this->id && $existing->status === ProductImei::STATUS_AVAILABLE) {
                    continue;
                }
                if ((int) $existing->product_id !== (int) $this->id) {
                    throw new \InvalidArgumentException("IMEI {$imei} already belongs to another product.");
                }
                // Sold/reserved — leave alone
                continue;
            }

            $this->imeis()->create([
                'imei' => $imei,
                'status' => ProductImei::STATUS_AVAILABLE,
            ]);
        }

        return $this->imeis()->available()->count();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    /** Products marked as new arrivals for shop filter + homepage. */
    public function scopeNewArrivals($query)
    {
        return $query->where('is_new_arrival', true);
    }

    /** Products marked as trending (homepage Trending + bestsellers filter). */
    public function scopeTrending($query)
    {
        return $query->where('is_best_seller', true);
    }

    /** Whether the storefront should show a "New" badge. */
    public function showsAsNew(): bool
    {
        return (bool) $this->is_new_arrival;
    }

    /** Active timed sale: offer price lower than list price within the window. */
    public function isTimedSaleActive(?CarbonInterface $at = null): bool
    {
        $at = $at ?? now();

        if ($this->sale_price === null || $this->sale_starts_at === null || $this->sale_ends_at === null) {
            return false;
        }

        if ((float) $this->sale_price >= (float) $this->selling_price) {
            return false;
        }

        return $at->greaterThanOrEqualTo($this->sale_starts_at)
            && $at->lessThanOrEqualTo($this->sale_ends_at);
    }

    /**
     * Permanent product discount price (from product form), or null if none / invalid.
     */
    public function permanentDiscountPrice(): ?float
    {
        $type = $this->pos_discount_type;
        if (! in_array($type, ['percent', 'fixed', 'tk'], true) || $this->pos_discount_value === null) {
            return null;
        }

        $list = (float) $this->selling_price;
        if ($list <= 0) {
            return null;
        }

        $value = (float) $this->pos_discount_value;
        if ($value <= 0) {
            return null;
        }

        $price = in_array($type, ['fixed', 'tk'], true)
            ? $list - $value
            : $list * (1 - min(99.99, $value) / 100);

        $price = round(max(0, $price), 2);

        return $price < $list ? $price : null;
    }

    public function hasPermanentDiscount(): bool
    {
        return $this->permanentDiscountPrice() !== null;
    }

    /** Discounted / sale pricing active (timed campaign or permanent product discount). */
    public function isOnSale(?CarbonInterface $at = null): bool
    {
        return $this->isTimedSaleActive($at) || $this->hasPermanentDiscount();
    }

    /** Price charged now (best of timed sale / permanent discount / list). */
    public function currentPrice(): float
    {
        $list = (float) $this->selling_price;
        $prices = [$list];

        if ($this->isTimedSaleActive()) {
            $prices[] = (float) $this->sale_price;
        }

        $permanent = $this->permanentDiscountPrice();
        if ($permanent !== null) {
            $prices[] = $permanent;
        }

        return round(min($prices), 2);
    }

    /** List / compare-at price shown struck through during an active sale. */
    public function compareAtPrice(): ?float
    {
        return $this->isOnSale() ? (float) $this->selling_price : null;
    }

    public function discountPercent(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }

        $base = (float) $this->selling_price;
        if ($base <= 0) {
            return 0;
        }

        return (int) round((1 - ($this->currentPrice() / $base)) * 100);
    }

    public function clearPermanentDiscount(): void
    {
        $this->forceFill([
            'pos_discount_type' => null,
            'pos_discount_value' => null,
        ])->save();
    }

    public function applyPermanentDiscount(string $type, float $value): void
    {
        $type = $type === 'tk' ? 'fixed' : $type;
        if (! in_array($type, ['percent', 'fixed'], true)) {
            throw new \InvalidArgumentException('Invalid discount type.');
        }

        $this->forceFill([
            'pos_discount_type' => $type,
            'pos_discount_value' => round($value, 2),
        ])->save();
    }

    public function scopeOnSale($query, ?CarbonInterface $at = null)
    {
        $at = $at ?? now();

        return $query->where(function ($q) use ($at) {
            $q->where(function ($timed) use ($at) {
                $timed->whereNotNull('sale_price')
                    ->whereNotNull('sale_starts_at')
                    ->whereNotNull('sale_ends_at')
                    ->whereRaw('sale_price < selling_price')
                    ->where('sale_starts_at', '<=', $at)
                    ->where('sale_ends_at', '>=', $at);
            })->orWhere(function ($permanent) {
                $permanent->whereNotNull('pos_discount_type')
                    ->whereNotNull('pos_discount_value')
                    ->where('pos_discount_value', '>', 0)
                    ->whereIn('pos_discount_type', ['percent', 'fixed', 'tk']);
            });
        });
    }

    public function clearSale(): void
    {
        $this->forceFill([
            'sale_price' => null,
            'sale_starts_at' => null,
            'sale_ends_at' => null,
        ])->save();
    }

    public function applySale(float $salePrice, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        $this->forceFill([
            'sale_price' => round($salePrice, 2),
            'sale_starts_at' => Carbon::parse($startsAt),
            'sale_ends_at' => Carbon::parse($endsAt),
        ])->save();
    }

    /** Model name without trailing "— Green / 8GB" style suffixes used for admin clarity. */
    public function storefrontDisplayName(): string
    {
        if (! $this->variant_group) {
            return (string) $this->name;
        }

        // Suffix separator needs surrounding spaces so names like "T-Shirt" stay intact.
        $name = trim(preg_replace('/\s+[—\-–]\s+.*$/u', '', (string) $this->name) ?? '');

        return $name !== '' ? $name : (string) $this->name;
    }

    /** Clean product title for receipts (without admin “— Color / Storage” suffix). */
    public function receiptDisplayName(): string
    {
        return $this->storefrontDisplayName();
    }

    /**
     * Configuration lines shown under each item on POS / online receipts.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function receiptSpecLines(): array
    {
        $lines = [];

        $brand = trim((string) ($this->brand_name ?: $this->brand?->name ?: ''));
        if ($brand !== '') {
            $lines[] = ['label' => 'Brand', 'value' => $brand];
        }

        foreach ($this->variantOptions() as $option) {
            $lines[] = ['label' => $option['attribute'], 'value' => $option['value']];
        }

        if (filled($this->barcode)) {
            $lines[] = ['label' => 'Code', 'value' => (string) $this->barcode];
        } elseif (filled($this->sku)) {
            $lines[] = ['label' => 'SKU', 'value' => (string) $this->sku];
        }

        return $lines;
    }

    public function swatchHex(): string
    {
        if ($this->color_hex && preg_match('/^#[0-9A-Fa-f]{6}$/', $this->color_hex)) {
            return $this->color_hex;
        }

        return color_name_to_hex($this->color);
    }
}