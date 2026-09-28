<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'name', 'slug', 'image_path', 'icon', 'description',
        'is_featured', 'product_count_label', 'filter_options',
    ];

    public function iconKey(): string
    {
        return \App\Support\CategoryIcons::resolve(
            $this->icon ?: \App\Support\CategoryIcons::suggest($this->name)
        );
    }

    public function iconMeta(): array
    {
        return \App\Support\CategoryIcons::meta($this->iconKey());
    }

    protected $casts = [
        'is_featured' => 'boolean',
        'filter_options' => 'array',
    ];

    public function filterConfig(): array
    {
        return \App\Support\CategoryFilterConfig::for($this);
    }

    public function sidebarFiltersEnabled(): bool
    {
        return (bool) ($this->filterConfig()['enabled'] ?? false);
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (blank($category->slug) && filled($category->name)) {
                $base = \Illuminate\Support\Str::slug($category->name) ?: 'category';
                $slug = $base;
                $i = 1;
                while (static::query()
                    ->where('shop_id', $category->shop_id)
                    ->when($category->exists, fn ($q) => $q->where('id', '!=', $category->id))
                    ->where('slug', $slug)
                    ->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // A category will have many products later
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeWhereSlugOrId($query, string|int $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->where('slug', $value);
            if (ctype_digit((string) $value)) {
                $q->orWhere('id', (int) $value);
            }
        });
    }
}