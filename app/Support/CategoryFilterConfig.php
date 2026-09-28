<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryFilterConfig
{
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'price_enabled' => true,
            'groups' => [
                [
                    'key' => 'availability',
                    'label' => 'Availability',
                    'type' => 'availability',
                    'enabled' => true,
                    'options' => [
                        ['value' => 'in_stock', 'label' => 'In Stock'],
                        ['value' => 'pre_order', 'label' => 'Pre Order'],
                        ['value' => 'up_coming', 'label' => 'Up Coming'],
                        ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
                    ],
                ],
            ],
        ];
    }

    public static function for(Category $category): array
    {
        $config = $category->filter_options;
        if (! is_array($config) || $config === []) {
            return self::defaults();
        }

        return array_replace_recursive(self::defaults(), $config);
    }

    public static function fromRequest(Request $request): array
    {
        $groups = [];
        foreach ($request->input('filter_groups', []) as $group) {
            if (! is_array($group)) {
                continue;
            }

            $label = trim((string) ($group['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $type = in_array($group['type'] ?? '', ['availability', 'brand', 'storage', 'ram', 'color', 'custom'], true)
                ? $group['type']
                : 'custom';

            $key = trim((string) ($group['key'] ?? ''));
            if ($key === '') {
                $key = Str::slug($label, '_');
            }
            $key = Str::slug($key, '_') ?: 'filter';

            $options = [];
            foreach ($group['options'] ?? [] as $option) {
                if (! is_array($option)) {
                    continue;
                }
                $optLabel = trim((string) ($option['label'] ?? ''));
                if ($optLabel === '') {
                    continue;
                }
                $optValue = trim((string) ($option['value'] ?? ''));
                if ($optValue === '') {
                    $optValue = Str::slug($optLabel, '_');
                }
                $options[] = [
                    'value' => Str::slug($optValue, '_') ?: Str::slug($optLabel, '_'),
                    'label' => $optLabel,
                ];
            }

            if ($type === 'availability' && $options === []) {
                $options = self::defaults()['groups'][0]['options'];
            }

            $groups[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'enabled' => ! empty($group['enabled']),
                'options' => $options,
            ];
        }

        return [
            'enabled' => $request->boolean('filter_enabled'),
            'price_enabled' => $request->boolean('price_enabled'),
            'groups' => $groups !== [] ? $groups : self::defaults()['groups'],
        ];
    }

    public static function facetValues(Category $category, string $type, string $key): Collection
    {
        $products = $category->products()
            ->where(function ($q) {
                $q->where('is_published', true)->orWhereNull('is_published');
            })
            ->with('brand')
            ->get();

        return match ($type) {
            'brand' => $products->map(fn ($p) => $p->brand?->name ?? $p->brand_name)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->map(fn ($name) => ['value' => Str::slug($name, '_'), 'label' => $name]),
            'storage' => unique_memory_sizes($products->pluck('storage'))
                ->map(fn ($v) => ['value' => Str::slug((string) $v, '_'), 'label' => (string) $v]),
            'ram' => unique_memory_sizes($products->pluck('ram'))
                ->map(fn ($v) => ['value' => Str::slug((string) $v, '_'), 'label' => (string) $v]),
            'color' => $products->map(fn ($p) => $p->displayColor() ?: $p->color)->filter()->unique()->sort()->values()
                ->map(fn ($v) => ['value' => Str::slug((string) $v, '_'), 'label' => (string) $v]),
            'custom' => $products->map(fn ($p) => data_get($p->filter_attributes, $key))
                ->flatten()
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->map(fn ($v) => ['value' => Str::slug((string) $v, '_'), 'label' => (string) $v]),
            default => collect(),
        };
    }
}
