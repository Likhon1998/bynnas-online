<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariantValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Product + Variant + Attribute layer.
 *
 * Every `products` row is a sellable variant (own price, stock, code, photos). Rows that share
 * `variant_group` are one product family; attribute values (Color, Size, …) tell them apart.
 */
class ProductVariantService
{
    /**
     * Save attribute values for a product row.
     *
     * @param  array<int|string, mixed>  $input  attribute_id => "Red" | ['value' => 'Red', 'hex' => '#dc2626']
     */
    public function syncValues(Product $product, array $input): void
    {
        $attributes = ProductAttribute::where('shop_id', $product->shop_id)->get()->keyBy('id');
        $keep = [];

        foreach ($input as $attributeId => $raw) {
            $attribute = $attributes->get((int) $attributeId);
            if (! $attribute) {
                continue;
            }

            $label = trim((string) (is_array($raw) ? ($raw['value'] ?? '') : $raw));
            if ($label === '') {
                continue;
            }
            $hex = is_array($raw) ? ($raw['hex'] ?? null) : null;

            $value = $this->resolveValue($attribute, $label, $hex);
            ProductVariantValue::updateOrCreate(
                ['product_id' => $product->id, 'product_attribute_id' => $attribute->id],
                ['product_attribute_value_id' => $value->id],
            );
            $keep[] = $attribute->id;
        }

        $product->variantValues()
            ->when($keep !== [], fn ($q) => $q->whereNotIn('product_attribute_id', $keep))
            ->delete();

        $this->mirrorLegacyColumns($product->fresh());
    }

    public function resolveValue(ProductAttribute $attribute, string $label, ?string $hex = null): ProductAttributeValue
    {
        $label = trim($label);
        if (in_array($attribute->slug, ['storage', 'ram'], true)) {
            $label = str_replace(' ', '', normalize_memory_size($label) ?? $label);
        }

        $slug = Str::slug($label) ?: substr(md5($label), 0, 12);
        $hex = $hex && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : null;

        $value = ProductAttributeValue::firstOrCreate(
            ['product_attribute_id' => $attribute->id, 'slug' => $slug],
            [
                'value' => $label,
                'color_hex' => $attribute->isColor() ? ($hex ?? color_name_to_hex($label)) : null,
                'sort_order' => (int) ProductAttributeValue::where('product_attribute_id', $attribute->id)->max('sort_order') + 1,
            ],
        );

        if ($attribute->isColor() && $hex && $value->color_hex !== $hex) {
            $value->update(['color_hex' => $hex]);
        }

        return $value;
    }

    /** Keep color/storage/ram columns in sync for receipts, legacy filters and reports. */
    public function mirrorLegacyColumns(Product $product): void
    {
        $bySlug = $product->variantValues()->with(['attribute', 'attributeValue'])->get()
            ->filter(fn ($v) => $v->attribute && $v->attributeValue)
            ->keyBy(fn ($v) => $v->attribute->slug);

        $color = $bySlug->get('color')?->attributeValue;

        $product->forceFill([
            'color' => $color?->value,
            'color_hex' => $color?->color_hex,
            'storage' => $bySlug->get('storage')?->attributeValue?->value,
            'ram' => $bySlug->get('ram')?->attributeValue?->value,
        ])->saveQuietly();
    }

    /** Internal product code used when the seller leaves barcode empty. */
    public function generateCode(): string
    {
        do {
            $code = 'BS'.now()->format('ymd').strtoupper(Str::random(5));
        } while (Product::where('barcode', $code)->exists());

        return $code;
    }

    /**
     * Storefront variant picker for a product family.
     *
     * @return array{groups: list<array<string, mixed>>, specs: list<array{label: string, value: string}>}
     */
    public function storefrontOptions(Product $product): array
    {
        $current = $product->variantOptions();
        $specs = $current->map(fn ($o) => ['label' => $o['attribute'], 'value' => $o['value']])->values()->all();

        if (! $product->variant_group) {
            return ['groups' => [], 'specs' => $specs];
        }

        $family = Product::query()
            ->where('shop_id', $product->shop_id)
            ->where('variant_group', $product->variant_group)
            ->where(fn ($q) => $q->where('is_published', true)->orWhereNull('is_published'))
            ->with(['variantValues.attribute', 'variantValues.attributeValue', 'galleryImages'])
            ->get();

        if (! $family->contains('id', $product->id)) {
            $family->push($product);
        }
        if ($family->count() < 2) {
            return ['groups' => [], 'specs' => $specs];
        }

        $optionsById = $family->mapWithKeys(fn (Product $p) => [
            $p->id => $p->variantOptions()->keyBy('slug'),
        ]);
        $currentBySlug = $current->keyBy('slug');

        $sortOrder = ProductAttribute::where('shop_id', $product->shop_id)->pluck('sort_order', 'slug');
        $attributes = $optionsById->flatten(1)
            ->unique('slug')
            ->sortBy(fn ($o) => [(int) ($sortOrder[$o['slug']] ?? 99), $o['attribute']])
            ->values();

        $website = app(WebsiteService::class);
        $groups = [];
        $grouped = [];

        foreach ($attributes as $attr) {
            $slug = $attr['slug'];
            $values = $optionsById->map(fn (Collection $opts) => $opts->get($slug))->filter()->unique('value_slug')->values();
            if ($values->count() < 2) {
                continue;
            }
            $grouped[] = $slug;

            $options = [];
            foreach ($values as $value) {
                $candidates = $family->filter(fn (Product $p) => ($optionsById[$p->id]->get($slug)['value_slug'] ?? null) === $value['value_slug']);
                $match = $this->bestMatch($candidates, $optionsById, $currentBySlug, $slug);
                if (! $match) {
                    continue;
                }

                $options[] = [
                    'label' => $value['value'],
                    'hex' => $value['hex'],
                    'image' => $attr['type'] === ProductAttribute::TYPE_COLOR ? $website->productImageUrl($match) : null,
                    'url' => route('website.product', $match),
                    'product_id' => $match->id,
                    'active' => ($currentBySlug->get($slug)['value_slug'] ?? null) === $value['value_slug'],
                    'available' => $match->availableStock() > 0,
                    'price' => $match->currentPrice(),
                ];
            }

            $groups[] = [
                'attribute' => $attr['attribute'],
                'slug' => $slug,
                'type' => $attr['type'],
                'options' => $options,
            ];
        }

        $specs = $current->reject(fn ($o) => in_array($o['slug'], $grouped, true))
            ->map(fn ($o) => ['label' => $o['attribute'], 'value' => $o['value']])
            ->values()
            ->all();

        return ['groups' => $groups, 'specs' => $specs];
    }

    /** Prefer the row that keeps the shopper's other selections, then in-stock, then cheapest. */
    private function bestMatch(Collection $candidates, Collection $optionsById, Collection $currentBySlug, string $changing): ?Product
    {
        return $candidates->sortBy(fn (Product $p) => [
            -$currentBySlug->keys()
                ->reject(fn ($s) => $s === $changing)
                ->filter(fn ($s) => ($optionsById[$p->id]->get($s)['value_slug'] ?? null) === $currentBySlug->get($s)['value_slug'])
                ->count(),
            $p->availableStock() > 0 ? 0 : 1,
            $p->currentPrice(),
            $p->id,
        ])->first();
    }
}
