<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Generic catalog: configurable attributes (Color, Size, Material…) linked to each sellable
 * product row. Rows sharing `variant_group` form one product family on the storefront.
 * Legacy color/storage/ram columns stay as mirrors for older reports and receipts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->string('type', 20)->default('select');
            $table->boolean('is_filterable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value', 120);
            $table->string('slug', 120);
            $table->string('color_hex', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_attribute_id', 'slug']);
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_id']);
            $table->index('product_attribute_value_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->longText('description')->nullable()->after('short_description');
            $table->string('seo_title')->nullable()->after('description');
            $table->string('meta_description', 500)->nullable()->after('seo_title');
            $table->string('og_title')->nullable()->after('meta_description');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->index(['shop_id', 'slug']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->change();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'slug']);
            $table->dropColumn(['slug', 'description', 'seo_title', 'meta_description', 'og_title', 'og_description', 'og_image']);
        });

        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }

    private function backfill(): void
    {
        $now = now();
        $defaults = [
            ['name' => 'Color', 'slug' => 'color', 'type' => 'color', 'sort_order' => 1],
            ['name' => 'Size', 'slug' => 'size', 'type' => 'select', 'sort_order' => 2],
            ['name' => 'Material', 'slug' => 'material', 'type' => 'select', 'sort_order' => 3],
            ['name' => 'Storage', 'slug' => 'storage', 'type' => 'select', 'sort_order' => 4],
            ['name' => 'RAM', 'slug' => 'ram', 'type' => 'select', 'sort_order' => 5],
            ['name' => 'Weight', 'slug' => 'weight', 'type' => 'select', 'sort_order' => 6],
        ];

        foreach (DB::table('shops')->pluck('id') as $shopId) {
            $attributeIds = [];
            foreach ($defaults as $def) {
                $attributeIds[$def['slug']] = DB::table('product_attributes')->insertGetId(array_merge($def, [
                    'shop_id' => $shopId,
                    'is_filterable' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }

            $valueIds = [];
            $resolveValue = function (string $attrSlug, string $label, ?string $hex = null) use (&$valueIds, $attributeIds, $now) {
                $slug = Str::slug($label) ?: substr(md5($label), 0, 12);
                $key = $attrSlug.'|'.$slug;
                if (! isset($valueIds[$key])) {
                    $valueIds[$key] = DB::table('product_attribute_values')->insertGetId([
                        'product_attribute_id' => $attributeIds[$attrSlug],
                        'value' => $label,
                        'slug' => $slug,
                        'color_hex' => $hex && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : null,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                return $valueIds[$key];
            };

            $usedSlugs = [];
            DB::table('products')->where('shop_id', $shopId)->orderBy('id')
                ->select(['id', 'name', 'color', 'color_hex', 'storage', 'ram'])
                ->chunkById(200, function ($products) use (&$usedSlugs, $resolveValue, $attributeIds, $now) {
                    foreach ($products as $product) {
                        $base = Str::slug((string) $product->name) ?: 'product';
                        $slug = isset($usedSlugs[$base]) ? $base.'-'.$product->id : $base;
                        $usedSlugs[$slug] = true;
                        DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);

                        foreach (['color' => $product->color, 'storage' => $product->storage, 'ram' => $product->ram] as $attr => $raw) {
                            $label = trim((string) $raw);
                            if ($label === '') {
                                continue;
                            }
                            if ($attr !== 'color') {
                                $label = str_replace(' ', '', normalize_memory_size($label) ?? $label);
                            }
                            DB::table('product_variant_values')->insert([
                                'product_id' => $product->id,
                                'product_attribute_id' => $attributeIds[$attr],
                                'product_attribute_value_id' => $resolveValue($attr, $label, $attr === 'color' ? $product->color_hex : null),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                });
        }
    }
};
