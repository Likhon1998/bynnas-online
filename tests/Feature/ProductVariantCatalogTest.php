<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Shop;
use App\Models\User;
use App\Services\ProductVariantService;
use App\Support\CategoryFilterConfig;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductVariantCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = $this->staff('Admin');
        ProductAttribute::ensureDefaults($this->shop->id);
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id])->save();
        $user->assignRole($role);

        return $user;
    }

    private function attr(string $slug): ProductAttribute
    {
        return ProductAttribute::where('shop_id', $this->shop->id)->where('slug', $slug)->firstOrFail();
    }

    public function test_variable_product_creates_one_row_per_variant_with_generated_codes(): void
    {
        $color = $this->attr('color');
        $size = $this->attr('size');

        $response = $this->actingAs($this->admin, 'admin')->post(route('products.store'), [
            'product_mode' => 'variable',
            'name' => 'Test Tee',
            'cost_price' => 100,
            'selling_price' => 200,
            'variants' => [
                ['options' => [$color->id => ['value' => 'Red', 'hex' => '#ff0000'], $size->id => ['value' => 'M']], 'stock_quantity' => 3],
                ['options' => [$color->id => ['value' => 'Red'], $size->id => ['value' => 'L']], 'selling_price' => 220],
            ],
        ]);

        $response->assertRedirect(route('products.index'));
        $rows = Product::where('variant_group', 'test-tee')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertStringStartsWith('BS', $rows[0]->barcode);
        $this->assertNotSame($rows[0]->barcode, $rows[1]->barcode);
        $this->assertSame('Red', $rows[0]->color);
        $this->assertSame('#ff0000', $rows[0]->color_hex);
        $this->assertSame(['Red', 'M'], $rows[0]->variantOptions()->pluck('value')->all());
        $this->assertSame(3, (int) $rows[0]->stock_quantity);
        $this->assertEquals(220, (float) $rows[1]->selling_price);
        $this->assertSame('Test Tee', $rows[1]->storefrontDisplayName());
        $this->assertNotEmpty($rows[0]->slug);
    }

    public function test_duplicate_variant_combinations_are_rejected(): void
    {
        $size = $this->attr('size');

        $this->actingAs($this->admin, 'admin')->post(route('products.store'), [
            'product_mode' => 'variable',
            'name' => 'Dupes',
            'cost_price' => 10,
            'selling_price' => 20,
            'variants' => [
                ['options' => [$size->id => ['value' => 'M']]],
                ['options' => [$size->id => ['value' => 'm']]],
            ],
        ])->assertSessionHasErrors('variants');

        $this->assertSame(0, Product::count());
    }

    public function test_simple_product_without_barcode_saves_seo_fields_and_attributes(): void
    {
        $material = $this->attr('material');

        $this->actingAs($this->admin, 'admin')->post(route('products.store'), [
            'product_mode' => 'simple',
            'name' => 'Ceramic Mug',
            'cost_price' => 150,
            'selling_price' => 390,
            'attributes' => [$material->id => ['value' => 'Ceramic']],
            'seo_title' => 'Best Ceramic Mug',
            'meta_description' => 'Dishwasher safe mug.',
            'og_title' => 'Mug OG',
        ])->assertRedirect(route('products.index'));

        $product = Product::firstOrFail();
        $this->assertStringStartsWith('BS', $product->barcode);
        $this->assertSame('ceramic-mug', $product->slug);
        $this->assertSame('Best Ceramic Mug', $product->seoTitle());
        $this->assertSame('Ceramic', $product->variantOptions()->first()['value']);
        $this->assertNull($product->variant_group);
    }

    public function test_attribute_admin_crud_protects_values_in_use(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('attributes.store'), ['name' => 'Fabric', 'type' => 'select', 'values' => 'Cotton, Linen'])
            ->assertSessionHasNoErrors();

        $fabric = $this->attr('fabric');
        $this->assertSame(['Cotton', 'Linen'], $fabric->values()->pluck('value')->all());

        $product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Shirt', 'barcode' => 'X-1',
            'cost_price' => 1, 'selling_price' => 2, 'stock_quantity' => 0,
        ]);
        app(ProductVariantService::class)->syncValues($product, [$fabric->id => 'Cotton']);
        $cotton = ProductAttributeValue::where('product_attribute_id', $fabric->id)->where('slug', 'cotton')->firstOrFail();
        $linen = ProductAttributeValue::where('product_attribute_id', $fabric->id)->where('slug', 'linen')->firstOrFail();

        $this->actingAs($this->admin, 'admin')->delete(route('attributes.values.destroy', $cotton))->assertSessionHas('error');
        $this->actingAs($this->admin, 'admin')->delete(route('attributes.destroy', $fabric))->assertSessionHas('error');
        $this->assertDatabaseHas('product_attribute_values', ['id' => $cotton->id]);

        $this->actingAs($this->admin, 'admin')->delete(route('attributes.values.destroy', $linen))->assertSessionHas('success');
        $this->assertDatabaseMissing('product_attribute_values', ['id' => $linen->id]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('attributes.values.update', $cotton), ['value' => 'Organic Cotton'])
            ->assertSessionHas('success');
        $this->assertSame('Organic Cotton', $product->fresh()->variantOptions()->first()['value']);
    }

    public function test_attribute_routes_enforce_permission_and_shop_scope(): void
    {
        $cashier = $this->staff('Cashier');
        $this->actingAs($cashier, 'admin')->getJson(route('attributes.index'))->assertForbidden();
        $this->actingAs($cashier, 'admin')
            ->post(route('attributes.store'), ['name' => 'Hack', 'type' => 'select'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('product_attributes', ['slug' => 'hack']);

        $otherShop = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $outsider = $this->staff('Admin', $otherShop);
        $this->actingAs($outsider, 'admin')->delete(route('attributes.destroy', $this->attr('weight')))->assertNotFound();
        $this->assertDatabaseHas('product_attributes', ['shop_id' => $this->shop->id, 'slug' => 'weight']);

        $this->actingAs($this->admin, 'admin')->get(route('attributes.index'))->assertOk()->assertSee('Product Attributes');
        $this->actingAs($this->admin, 'admin')->get(route('products.variants'))->assertOk();
    }

    public function test_storefront_slug_page_picker_and_attribute_filters(): void
    {
        $service = app(ProductVariantService::class);
        $color = $this->attr('color');
        $size = $this->attr('size');
        $make = function (string $colorName, string $sizeName, int $stock) use ($service, $color, $size) {
            $p = Product::create([
                'shop_id' => $this->shop->id, 'name' => "Hoodie - {$colorName} / {$sizeName}", 'variant_group' => 'hoodie',
                'barcode' => 'H-'.$colorName.$sizeName, 'cost_price' => 500, 'selling_price' => 1000,
                'stock_quantity' => $stock, 'is_published' => true, 'seo_title' => 'Cosy Hoodie SEO',
            ]);
            $service->syncValues($p, [$color->id => $colorName, $size->id => $sizeName]);

            return $p;
        };
        $redM = $make('Red', 'M', 5);
        $make('Red', 'L', 0);
        $make('Blue', 'M', 4);

        $this->get('/product/'.$redM->slug)
            ->assertOk()
            ->assertSee('<title>Cosy Hoodie SEO', false)
            ->assertSee('og:title', false);
        $this->get('/product/'.$redM->id)->assertOk();

        $picker = $service->storefrontOptions($redM);
        $groups = collect($picker['groups'])->keyBy('slug');
        $this->assertEqualsCanonicalizing(['Red', 'Blue'], collect($groups['color']['options'])->pluck('label')->all());
        $sizeL = collect($groups['size']['options'])->firstWhere('label', 'L');
        $this->assertFalse($sizeL['available']);

        $blue = $this->getJson('/shop?ajax=1&attr[color][]=blue')->assertOk()->json('count_text');
        $this->assertStringContainsString('of 1 products', $blue);
        $none = $this->getJson('/shop?ajax=1&attr[color][]=blue&attr[size][]=l')->assertOk()->json('count_text');
        $this->assertStringContainsString('of 0 products', $none);
    }

    public function test_csv_import_generates_codes_and_maps_attribute_columns(): void
    {
        $csv = "name,cost_price,selling_price,variant_group,color,color_hex,size,attr_fabric,barcode\n"
            ."Linen Shirt - White / M,400,900,linen-shirt,White,#ffffff,M,Linen,\n"
            ."Linen Shirt - White / L,400,950,linen-shirt,White,#ffffff,L,Linen,LS-100\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($this->admin, 'admin')
            ->post(route('products.import.store'), ['csv_file' => $file])
            ->assertSessionHasNoErrors();

        $rows = Product::where('variant_group', 'linen-shirt')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertStringStartsWith('BS', $rows[0]->barcode);
        $this->assertSame('LS-100', $rows[1]->barcode);
        $this->assertSame(['White', 'M', 'Linen'], $rows[0]->variantOptions()->pluck('value')->all());
        $this->assertSame('#ffffff', $rows[0]->color_hex);
        $this->assertDatabaseHas('product_attributes', ['shop_id' => $this->shop->id, 'slug' => 'fabric']);
    }

    public function test_legacy_category_filter_types_run_on_product_attributes(): void
    {
        $category = Category::create([
            'shop_id' => $this->shop->id, 'name' => 'Drives', 'slug' => 'drives',
            'filter_options' => [
                'enabled' => true,
                'price_enabled' => false,
                'groups' => [['key' => 'rom', 'label' => 'Storage', 'type' => 'storage', 'enabled' => true, 'options' => []]],
            ],
        ]);
        $storage = $this->attr('storage');
        $service = app(ProductVariantService::class);
        foreach (['Alpha Drive' => '256GB', 'Beta Drive' => '128GB'] as $name => $size) {
            $p = Product::create([
                'shop_id' => $this->shop->id, 'category_id' => $category->id, 'name' => $name, 'barcode' => Str::slug($name),
                'cost_price' => 1, 'selling_price' => 2, 'stock_quantity' => 3, 'is_published' => true,
            ]);
            $service->syncValues($p, [$storage->id => $size]);
        }

        $groups = collect(CategoryFilterConfig::for($category->fresh())['groups']);
        $this->assertSame('attribute', $groups->firstWhere('key', 'storage')['type'] ?? null);
        $this->assertNull($groups->firstWhere('type', 'storage'));

        $this->get('/category/drives?storage[]=256_gb')->assertOk()->assertSee('Alpha Drive')->assertDontSee('Beta Drive');
        $this->get('/category/drives?storage[]=128gb')->assertOk()->assertSee('Beta Drive')->assertDontSee('Alpha Drive');
        $this->get('/category/drives')->assertOk()->assertSee('Alpha Drive')->assertSee('Beta Drive')->assertSee('256GB');
    }

    public function test_legacy_gadget_row_without_attribute_values_still_renders(): void
    {
        $phone = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Legacy Phone - Black / 128GB', 'variant_group' => 'legacy-phone',
            'barcode' => 'LEGACY-1', 'cost_price' => 100, 'selling_price' => 150, 'stock_quantity' => 2,
            'is_published' => true, 'color' => 'Black', 'storage' => '128GB', 'ram' => '8GB',
        ]);

        $this->assertSame(['Black', '128GB', '8GB'], $phone->variantOptions()->pluck('value')->all());
        $this->get('/product/'.$phone->slug)->assertOk()->assertSee('Legacy Phone');
        $this->get('/product/'.$phone->id)->assertOk();
        $this->actingAs($this->admin, 'admin')->get(route('products.edit', $phone))->assertOk()->assertDontSee('Serial / IMEI tracking');
    }

    public function test_obsolete_gadget_product_mode_is_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('products.store'), [
            'product_mode' => 'gadget', 'name' => 'Old Mode', 'cost_price' => 1, 'selling_price' => 2,
        ])->assertSessionHasErrors('product_mode');
    }

    public function test_csv_template_is_generic(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('products.import.template'));
        $response->assertOk();
        $this->assertStringContainsString('bynnas-social-products-template.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Classic Cotton T-Shirt', $response->getContent());
        $this->assertStringNotContainsStringIgnoringCase('samsung', $response->getContent());
    }
}
