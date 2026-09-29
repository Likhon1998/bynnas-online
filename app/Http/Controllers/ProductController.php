<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Services\AccountService;
use App\Services\ProductVariantService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
        protected ProductVariantService $variants,
    ) {}
    public function index(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $base = Product::where('shop_id', $shopId);

        $stats = [
            'total_products' => (clone $base)->count(),
            'total_stock' => (int) ((clone $base)->sum('stock_quantity') ?? 0),
            'total_value' => (float) ((clone $base)->selectRaw('COALESCE(SUM(cost_price * stock_quantity), 0) as v')->value('v') ?? 0),
            'out_of_stock' => (clone $base)->where('stock_quantity', '<=', 0)->count(),
            'on_sale' => (clone $base)->onSale()->count(),
        ];

        $query = Product::where('shop_id', $shopId)->with(['category', 'brand']);

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $likeOp = Schema::getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like, $likeOp) {
                $q->where('name', $likeOp, $like)
                    ->orWhere('barcode', $likeOp, $like)
                    ->orWhere('sku', $likeOp, $like)
                    ->orWhere('brand_name', $likeOp, $like);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        $status = trim((string) $request->input('status', ''));
        match ($status) {
            'ok' => $query->whereRaw('stock_quantity > COALESCE(alert_quantity, 5)'),
            'low' => $query->where('stock_quantity', '>', 0)
                ->whereRaw('stock_quantity <= COALESCE(alert_quantity, 5)'),
            'out' => $query->where('stock_quantity', '<=', 0),
            'sale' => $query->onSale(),
            'hidden' => $query->where('is_published', false),
            default => null,
        };

        $products = $query->latest('id')->paginate(10)->appends($request->only(['q', 'category_id', 'status']));
        $brands = Brand::where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get();
        $activeBrandSales = $this->activeBrandSaleCampaigns($shopId);

        return view('products.index', compact(
            'products',
            'brands',
            'categories',
            'stats',
            'status',
            'search',
            'activeBrandSales',
        ));
    }

    /**
     * Summarize brands that currently have timed sale products (for admin product list).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function activeBrandSaleCampaigns(int $shopId)
    {
        $onSale = Product::query()
            ->where('shop_id', $shopId)
            ->onSale()
            ->with('brand:id,name')
            ->get(['id', 'brand_id', 'brand_name', 'selling_price', 'sale_price', 'sale_starts_at', 'sale_ends_at']);

        return $onSale
            ->filter(fn (Product $p) => filled($p->brand_id) || filled($p->brand_name) || filled($p->brand?->name))
            ->groupBy(fn (Product $p) => $p->brand_id ?: ('name:'.mb_strtolower((string) ($p->brand?->name ?: $p->brand_name))))
            ->map(function ($items) {
                /** @var \Illuminate\Support\Collection<int, Product> $items */
                $first = $items->first();
                $percents = $items->map(fn (Product $p) => $p->discountPercent())->filter(fn ($p) => $p > 0);
                $commonPercent = $percents->isNotEmpty()
                    ? (int) $percents->countBy()->sortDesc()->keys()->first()
                    : 0;
                $endsAt = $items->max('sale_ends_at');
                $startsAt = $items->min('sale_starts_at');

                return [
                    'brand_id' => $first->brand_id ? (int) $first->brand_id : null,
                    'brand_name' => $first->brand?->name ?: ($first->brand_name ?: 'Brand'),
                    'product_count' => $items->count(),
                    'discount_percent' => $commonPercent,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ];
            })
            ->sortBy('brand_name')
            ->values();
    }

    public function create(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        ProductAttribute::ensureDefaults($shopId);

        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get();
        $brands = Brand::where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get();
        $returnTo = $this->productReturnRoute($request->query('from'));
        $productAttributes = ProductAttribute::forShop($shopId)->with('values')->get();
        $variantOf = $request->filled('variant_of')
            ? Product::where('shop_id', $shopId)->find((int) $request->query('variant_of'))
            : null;

        return view('products.create', compact('categories', 'brands', 'returnTo', 'productAttributes', 'variantOf'));
    }

    public function store(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $isVariable = $request->input('product_mode') === 'variable';

        if ($isVariable && is_array($request->input('variants'))) {
            $variants = $request->input('variants', []);
            foreach ($variants as $i => $row) {
                foreach (['cost_price', 'selling_price', 'stock_quantity'] as $key) {
                    if (array_key_exists($key, $row) && $row[$key] === '') {
                        $variants[$i][$key] = null;
                    }
                }
            }
            $request->merge(['variants' => $variants]);
        }

        $rules = array_merge($this->productRules($shopId), [
            'product_mode' => 'required|in:simple,variable',
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')],
            'stock_quantity' => 'nullable|integer|min:0',
            'variant_of' => 'nullable|integer',
            'return_to' => 'nullable|in:opening-inventory',
        ]);

        if ($isVariable) {
            $rules = array_merge($rules, [
                'variants' => 'required|array|min:1|max:100',
                'variants.*.barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')],
                'variants.*.sku' => 'nullable|string|max:100',
                'variants.*.options' => 'nullable|array',
                'variants.*.options.*.value' => 'nullable|string|max:120',
                'variants.*.options.*.hex' => 'nullable|string|max:7',
                'variants.*.cost_price' => 'nullable|numeric|min:0',
                'variants.*.selling_price' => 'nullable|numeric|min:0',
                'variants.*.stock_quantity' => 'nullable|integer|min:0',
                'variants.*.imei_list' => 'nullable|string|max:10000',
                'variants.*.images' => 'nullable|array|max:20',
                'variants.*.images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);
        }

        $validated = $request->validate($rules);
        $attributes = ProductAttribute::forShop($shopId)->get();

        if ($isVariable) {
            $barcodes = collect($validated['variants'])->pluck('barcode')->map(fn ($b) => trim((string) $b))->filter();
            if ($barcodes->count() !== $barcodes->unique()->count()) {
                return back()->withErrors(['variants' => 'Each variant must have a unique barcode.'])->withInput();
            }

            $combos = collect($validated['variants'])->map(fn ($row) => $this->optionLabels($row['options'] ?? [], $attributes)->map(fn ($v) => mb_strtolower($v))->implode('|'));
            if ($combos->contains('')) {
                return back()->withErrors(['variants' => 'Give every variant at least one option, such as a color or size.'])->withInput();
            }
            if ($combos->count() !== $combos->unique()->count()) {
                return back()->withErrors(['variants' => 'Two variants have the same options. Each combination must be unique.'])->withInput();
            }
        }

        if ($error = $this->discountError($validated)) {
            return back()->withErrors(['pos_discount_value' => $error])->withInput();
        }
        if (empty($validated['pos_discount_type'])) {
            $validated['pos_discount_type'] = null;
            $validated['pos_discount_value'] = null;
        }

        $openingQty = (int) ($validated['stock_quantity'] ?? 0);
        $uploadedPaths = [];
        $requiresImei = retail_enabled() && $request->boolean('requires_imei');
        $variantOf = filled($validated['variant_of'] ?? null)
            ? Product::where('shop_id', $shopId)->find((int) $validated['variant_of'])
            : null;

        try {
            $created = DB::transaction(function () use ($request, $validated, $shopId, $openingQty, $isVariable, $requiresImei, $attributes, $variantOf, &$uploadedPaths) {
                $ogImage = $this->storeOgImage($request);
                if ($ogImage) {
                    $uploadedPaths[] = $ogImage;
                }

                $shared = [
                    'shop_id' => $shopId,
                    'name' => $validated['name'],
                    'sku' => $validated['sku'] ?? null,
                    'variant_group' => $variantOf?->variant_group ?: ($validated['variant_group'] ?? null),
                    'availability' => $validated['availability'] ?? 'in_stock',
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'pos_discount_type' => $validated['pos_discount_type'],
                    'pos_discount_value' => $validated['pos_discount_value'],
                    'short_description' => $validated['short_description'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'seo_title' => $validated['seo_title'] ?? null,
                    'meta_description' => $validated['meta_description'] ?? null,
                    'og_title' => $validated['og_title'] ?? null,
                    'og_description' => $validated['og_description'] ?? null,
                    'og_image' => $ogImage,
                    'category_id' => $validated['category_id'] ?? null,
                    'brand_id' => $validated['brand_id'] ?? null,
                    'stock_quantity' => 0,
                    'alert_quantity' => $validated['alert_quantity'] ?? 5,
                    'requires_imei' => $requiresImei,
                    'is_published' => $request->boolean('is_published', true),
                    'is_new_arrival' => $request->boolean('is_new_arrival'),
                    'is_best_seller' => $request->boolean('is_best_seller'),
                    'is_featured' => $request->boolean('is_featured'),
                ];
                $shared = $this->normalizeVariantFields($this->applyBrandData($shared));

                if (! $isVariable) {
                    $options = $validated['attributes'] ?? [];
                    $name = $shared['name'];
                    if ($variantOf) {
                        $labels = $this->optionLabels($options, $attributes);
                        $name = $validated['name'].($labels->isNotEmpty() ? ' — '.$labels->implode(' / ') : '');
                    }

                    $product = Product::create(array_merge($shared, [
                        'name' => $name,
                        'barcode' => filled($validated['barcode'] ?? null) ? trim($validated['barcode']) : $this->variants->generateCode(),
                    ]));
                    $this->variants->syncValues($product, $options);

                    $uploadedPaths = array_merge($uploadedPaths, $this->storeGalleryImages($request, $product));
                    if ($variantOf && ! $request->hasFile('images')) {
                        $this->cloneGalleryToProduct($variantOf, $product);
                    }
                    $this->syncPrimaryImageFromGallery($product);

                    $imeiCount = $requiresImei ? $this->applyImeiList($product, (string) ($validated['imei_list'] ?? '')) : 0;
                    $qty = $requiresImei && $imeiCount > 0 ? $imeiCount : $openingQty;
                    $this->recordOpeningStock($product, $qty);

                    return ['count' => 1, 'opening' => $qty, 'product' => $product];
                }

                $group = $shared['variant_group'] ?: (Str::slug($validated['name']) ?: null);
                $count = 0;
                $totalOpening = 0;
                $first = null;
                $gallerySource = null;

                foreach ($validated['variants'] as $index => $row) {
                    $labels = $this->optionLabels($row['options'] ?? [], $attributes);
                    $cost = isset($row['cost_price']) && $row['cost_price'] !== null ? (float) $row['cost_price'] : (float) $validated['cost_price'];
                    $sell = isset($row['selling_price']) && $row['selling_price'] !== null ? (float) $row['selling_price'] : (float) $validated['selling_price'];

                    $product = Product::create(array_merge($shared, [
                        'name' => $validated['name'].($labels->isNotEmpty() ? ' — '.$labels->implode(' / ') : ''),
                        'variant_group' => $group,
                        'barcode' => filled($row['barcode'] ?? null) ? trim($row['barcode']) : $this->variants->generateCode(),
                        'sku' => filled($row['sku'] ?? null) ? trim($row['sku']) : $shared['sku'],
                        'cost_price' => $cost,
                        'selling_price' => $sell,
                    ]));
                    $this->variants->syncValues($product, $row['options'] ?? []);
                    $first ??= $product;

                    $variantFiles = $request->file("variants.{$index}.images");
                    if (is_array($variantFiles) && collect($variantFiles)->filter()->isNotEmpty()) {
                        $uploadedPaths = array_merge($uploadedPaths, $this->storeGalleryFiles($product, $variantFiles));
                        $this->syncPrimaryImageFromGallery($product);
                        $gallerySource = $product;
                    } elseif ($index === 0 && $request->hasFile('images')) {
                        $uploadedPaths = array_merge($uploadedPaths, $this->storeGalleryImages($request, $product));
                        $this->syncPrimaryImageFromGallery($product);
                        $gallerySource = $product;
                    } elseif ($gallerySource) {
                        $this->cloneGalleryToProduct($gallerySource, $product);
                    }

                    $imeiCount = $requiresImei ? $this->applyImeiList($product, (string) ($row['imei_list'] ?? '')) : 0;
                    $qty = $requiresImei && $imeiCount > 0 ? $imeiCount : (int) ($row['stock_quantity'] ?? 0);
                    $this->recordOpeningStock($product, $qty);

                    $totalOpening += $qty;
                    $count++;
                }

                return ['count' => $count, 'opening' => $totalOpening, 'product' => $first];
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'form' => $e->getMessage() ?: 'Product could not be saved. Please check the form and try again.',
                ]);
        }

        $openingQty = (int) ($created['opening'] ?? 0);
        $count = (int) ($created['count'] ?? 1);

        if ($request->input('return_to') === 'opening-inventory') {
            return redirect()->route('supply.opening-inventory.index')->with(
                'success',
                $count > 1
                    ? "{$count} variants added".($openingQty > 0 ? " with {$openingQty} total units." : '.')
                    : ($openingQty > 0
                        ? "Product added with {$openingQty} units in stock."
                        : 'Product added. Enter the opening quantity below.')
            );
        }

        $message = $count > 1
            ? "{$count} variants created".($openingQty > 0 ? ", {$openingQty} units stocked." : '.')
            : ($openingQty > 0
                ? "Product added with {$openingQty} units in stock."
                : 'Product added with 0 stock. Add stock via Opening Inventory or Stock Adjustment.');

        return redirect()->route('products.index')->with('success', $message);
    }

    public function edit(Product $product)
    {
        $shopId = Auth::user()->shop_id;
        if ($product->shop_id !== $shopId) {
            abort(403, 'Unauthorized access.');
        }
        ProductAttribute::ensureDefaults($shopId);

        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get();
        $brands = Brand::where('shop_id', $shopId)->orderBy('name')->get();
        $productAttributes = ProductAttribute::forShop($shopId)->with('values')->get();
        $product->load(['galleryImages', 'availableImeis', 'variantValues.attributeValue']);
        $siblings = $product->variant_group
            ? Product::where('shop_id', $shopId)
                ->where('variant_group', $product->variant_group)
                ->where('id', '!=', $product->id)
                ->with('variantValues.attribute', 'variantValues.attributeValue')
                ->orderBy('id')
                ->get()
            : collect();

        return view('products.edit', compact('product', 'categories', 'brands', 'productAttributes', 'siblings'));
    }

    public function update(Request $request, Product $product)
    {
        $shopId = Auth::user()->shop_id;
        if ($product->shop_id !== $shopId) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate(array_merge($this->productRules($shopId), [
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'remove_og_image' => 'nullable|boolean',
        ]));

        if ($error = $this->discountError($validated)) {
            return back()->withErrors(['pos_discount_value' => $error])->withInput();
        }

        $data = [
            'name' => $validated['name'],
            'barcode' => filled($validated['barcode'] ?? null)
                ? trim($validated['barcode'])
                : ($product->barcode ?: $this->variants->generateCode()),
            'sku' => $validated['sku'] ?? null,
            'variant_group' => $validated['variant_group'] ?? $product->variant_group,
            'availability' => $validated['availability'] ?? ($product->availability ?? 'in_stock'),
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'pos_discount_type' => $validated['pos_discount_type'] ?? null,
            'pos_discount_value' => ! empty($validated['pos_discount_type']) ? ($validated['pos_discount_value'] ?? null) : null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'og_title' => $validated['og_title'] ?? null,
            'og_description' => $validated['og_description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'alert_quantity' => $validated['alert_quantity'] ?? ($product->alert_quantity ?? 5),
            'is_published' => $request->boolean('is_published'),
            'is_new_arrival' => $request->boolean('is_new_arrival'),
            'is_best_seller' => $request->boolean('is_best_seller'),
            'is_featured' => $request->boolean('is_featured'),
        ];
        if (retail_enabled()) {
            $data['requires_imei'] = $request->boolean('requires_imei');
        }
        $data = $this->normalizeVariantFields($this->applyBrandData($data));

        try {
            DB::transaction(function () use ($request, $product, $data, $validated) {
                $oldOgImage = $product->og_image;
                $newOgImage = $this->storeOgImage($request);
                if ($newOgImage) {
                    $data['og_image'] = $newOgImage;
                } elseif ($request->boolean('remove_og_image')) {
                    $data['og_image'] = null;
                }

                $product->update($data);
                $this->variants->syncValues($product, $validated['attributes'] ?? []);
                $this->removeGalleryImages($product, (array) $request->input('remove_images', []));
                $this->storeGalleryImages($request, $product);
                $this->syncPrimaryImageFromGallery($product);

                if (array_key_exists('og_image', $data) && $oldOgImage && $oldOgImage !== $data['og_image']
                    && ! Product::where('og_image', $oldOgImage)->exists()) {
                    Storage::disk('public')->delete($oldOgImage);
                }

                if (retail_enabled() && $product->requires_imei) {
                    $this->applyImeiList($product, (string) $request->input('imei_list', ''));
                }
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['imei_list' => $e->getMessage()])->withInput();
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    /** Validation shared by create and edit. */
    private function productRules(int $shopId): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'variant_group' => 'nullable|string|max:120',
            'attributes' => 'nullable|array',
            'attributes.*.value' => 'nullable|string|max:120',
            'attributes.*.hex' => 'nullable|string|max:7',
            'requires_imei' => 'nullable|boolean',
            'imei_list' => 'nullable|string|max:10000',
            'availability' => 'nullable|in:in_stock,pre_order,up_coming,out_of_stock',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'pos_discount_type' => 'nullable|in:percent,fixed',
            'pos_discount_value' => 'nullable|numeric|min:0.01|required_with:pos_discount_type',
            'short_description' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:20000',
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('shop_id', $shopId))],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->where(fn ($q) => $q->where('shop_id', $shopId))],
            'images' => 'nullable|array|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'is_published' => 'nullable|boolean',
            'is_new_arrival' => 'nullable|boolean',
            'is_best_seller' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'alert_quantity' => 'nullable|integer|min:0',
        ];
    }

    private function discountError(array $validated): ?string
    {
        if (empty($validated['pos_discount_type'])) {
            return null;
        }

        $list = (float) $validated['selling_price'];
        $offer = $validated['pos_discount_type'] === 'percent'
            ? $list * (1 - (float) ($validated['pos_discount_value'] ?? 0) / 100)
            : $list - (float) ($validated['pos_discount_value'] ?? 0);

        return $offer <= 0 || $offer >= $list
            ? 'Discount must leave an offer price below the selling price.'
            : null;
    }

    /**
     * Option labels in attribute order, e.g. ["Red", "XL"].
     *
     * @param  array<int|string, mixed>  $options
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function optionLabels(array $options, $attributes)
    {
        return $attributes
            ->map(function (ProductAttribute $attribute) use ($options) {
                $raw = $options[$attribute->id] ?? null;

                return trim((string) (is_array($raw) ? ($raw['value'] ?? '') : $raw));
            })
            ->filter(fn ($label) => $label !== '')
            ->values();
    }

    private function recordOpeningStock(Product $product, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $this->stock->ensureDefaultLocations($product->shop_id);
        $movement = $this->stock->setOpeningStock($product, $qty);
        $this->accounts->postOpeningInventory($movement);
    }

    private function storeOgImage(Request $request): ?string
    {
        $file = $request->file('og_image_file');

        return $file && $file->isValid() ? $file->store('products/og', 'public') : null;
    }

    public function toggleHomepageFlag(Request $request, Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'flag' => 'required|in:is_new_arrival,is_best_seller',
            'value' => 'required|boolean',
        ]);

        $flag = $validated['flag'];
        $product->update([$flag => $request->boolean('value')]);

        return response()->json([
            'ok' => true,
            'flag' => $flag,
            'value' => (bool) $product->{$flag},
            'is_new_arrival' => (bool) $product->is_new_arrival,
            'is_best_seller' => (bool) $product->is_best_seller,
        ]);
    }

    public function destroy(Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized access.');
        }

        $product->load('galleryImages');
        foreach ($product->imagePaths() as $path) {
            Storage::disk('public')->delete($path);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted from inventory!');
    }

    public function importForm()
    {
        return view('products.import');
    }

    /**
     * Downloadable demo CSV (Excel-friendly) so staff can fill and re-upload.
     */
    public function importTemplate()
    {
        $headers = [
            'name', 'barcode', 'sku', 'category', 'brand',
            'cost_price', 'selling_price', 'stock_quantity', 'alert_quantity', 'image_url',
            'variant_group', 'color', 'color_hex', 'size', 'material',
            'short_description', 'description', 'seo_title', 'meta_description',
        ];

        $rows = [
            ['Classic Cotton T-Shirt - Black / M', '', 'TEE-BLK-M', 'Fashion', 'Bynnas Basics', '320', '650', '25', '5', '', 'classic-cotton-tee', 'Black', '#111827', 'M', 'Cotton', 'Soft 180 GSM cotton tee for everyday wear.', '', 'Classic Cotton T-Shirt | Bynnas Social', 'Breathable cotton t-shirt in black. Order online with cash on delivery.'],
            ['Classic Cotton T-Shirt - Black / L', '', 'TEE-BLK-L', 'Fashion', 'Bynnas Basics', '320', '650', '18', '5', '', 'classic-cotton-tee', 'Black', '#111827', 'L', 'Cotton', 'Soft 180 GSM cotton tee for everyday wear.', '', '', ''],
            ['Classic Cotton T-Shirt - White / M', '', 'TEE-WHT-M', 'Fashion', 'Bynnas Basics', '320', '650', '20', '5', '', 'classic-cotton-tee', 'White', '#ffffff', 'M', 'Cotton', 'Soft 180 GSM cotton tee for everyday wear.', '', '', ''],
            ['Matte Lipstick - Ruby Red', '', 'LIP-RUBY', 'Cosmetics', 'Glow Lab', '180', '450', '40', '8', '', 'matte-lipstick', 'Ruby Red', '#9f1239', '', '', 'Long-lasting matte finish.', '', '', ''],
            ['Ceramic Coffee Mug 350ml', '8801234500011', 'MUG-350', 'Home & Kitchen', '', '150', '390', '30', '5', '', '', '', '', '', 'Ceramic', 'Dishwasher-safe mug.', '', '', ''],
        ];

        $escape = function ($value): string {
            $value = (string) $value;
            if (str_contains($value, '"') || str_contains($value, ',') || str_contains($value, "\n")) {
                return '"'.str_replace('"', '""', $value).'"';
            }

            return $value;
        };

        $lines = [];
        $lines[] = implode(',', array_map($escape, $headers));
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map($escape, $row));
        }

        // UTF-8 BOM so Excel opens Bangla / special characters correctly
        $csv = "\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bynnas-social-products-template.csv"',
        ]);
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'max:5120',
                'mimetypes:text/plain,text/csv,text/tsv,application/csv,application/vnd.ms-excel,application/octet-stream',
            ],
        ], [
            'csv_file.mimetypes' => 'Upload a CSV or TXT file (Excel “Save as CSV” is supported).',
        ]);

        $shopId = Auth::user()->shop_id;
        $path = $request->file('csv_file')->getRealPath();
        $raw = file_get_contents($path);
        if ($raw === false) {
            return back()->withErrors(['csv_file' => 'Could not read the uploaded file.']);
        }

        // Strip UTF-8 BOM (common from Windows Excel)
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = array_values(array_filter(explode("\n", $raw), fn ($l) => trim($l) !== ''));
        if (count($lines) < 2) {
            return back()->withErrors(['csv_file' => 'CSV must include a header row and at least one product row.']);
        }

        $delimiter = $this->detectCsvDelimiter($lines[0]);
        $headerCells = str_getcsv($lines[0], $delimiter);
        $header = array_map(function ($h) {
            $h = strtolower(trim((string) $h));
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h;

            return $h;
        }, $headerCells);

        // Aliases so Excel users can use common names
        $aliases = [
            'product' => 'name',
            'product_name' => 'name',
            'item' => 'name',
            'item_name' => 'name',
            'barcode_no' => 'barcode',
            'ean' => 'barcode',
            'upc' => 'barcode',
            'cost' => 'cost_price',
            'buy_price' => 'cost_price',
            'purchase_price' => 'cost_price',
            'price' => 'selling_price',
            'sell_price' => 'selling_price',
            'sale_price' => 'selling_price',
            'mrp' => 'selling_price',
            'qty' => 'stock_quantity',
            'quantity' => 'stock_quantity',
            'stock' => 'stock_quantity',
            'opening_stock' => 'stock_quantity',
            'reorder' => 'alert_quantity',
            'alert' => 'alert_quantity',
            'reorder_level' => 'alert_quantity',
            'category_name' => 'category',
            'brand_name' => 'brand',
            'image' => 'image_url',
            'photo' => 'image_url',
            'photo_url' => 'image_url',
            'picture' => 'image_url',
            'summary' => 'short_description',
            'long_description' => 'description',
            'meta_title' => 'seo_title',
            'rom' => 'storage',
            'colour' => 'color',
        ];
        $header = array_map(fn ($h) => $aliases[$h] ?? $h, $header);

        // Columns named after an attribute (color, size, storage…) become variant values;
        // "attr_fabric" style columns create the attribute when it does not exist yet.
        ProductAttribute::ensureDefaults((int) $shopId);
        $reserved = [
            'name', 'barcode', 'sku', 'category', 'brand', 'cost_price', 'selling_price', 'stock_quantity',
            'alert_quantity', 'image_url', 'variant_group', 'color_hex', 'short_description', 'description',
            'seo_title', 'meta_description', 'og_title', 'og_description',
        ];
        $shopAttributes = ProductAttribute::where('shop_id', $shopId)->get()->keyBy('slug');
        $attributeColumns = [];
        foreach ($header as $column) {
            if (in_array($column, $reserved, true) || $column === '') {
                continue;
            }
            if (str_starts_with($column, 'attr_') || str_starts_with($column, 'attribute:')) {
                $label = trim(preg_replace('/^(attr_|attribute:)/', '', $column) ?? '');
                $slug = Str::slug(str_replace('_', ' ', $label));
                if ($slug === '') {
                    continue;
                }
                $attribute = $shopAttributes->get($slug) ?? ProductAttribute::create([
                    'shop_id' => $shopId,
                    'name' => Str::title(str_replace(['_', '-'], ' ', $label)),
                    'slug' => $slug,
                    'type' => ProductAttribute::TYPE_SELECT,
                    'is_filterable' => true,
                    'sort_order' => (int) $shopAttributes->max('sort_order') + 1,
                ]);
                $shopAttributes->put($slug, $attribute);
                $attributeColumns[$column] = $attribute;
            } elseif ($attribute = $shopAttributes->get(Str::slug(str_replace('_', ' ', $column)))) {
                $attributeColumns[$column] = $attribute;
            }
        }

        $required = ['name', 'cost_price', 'selling_price'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                return back()->withErrors([
                    'csv_file' => "Missing required column: {$col}. Found: ".implode(', ', $header),
                ]);
            }
        }

        $imported = 0;
        $skipped = 0;
        $stockSet = 0;
        $errors = [];

        $this->stock->ensureDefaultLocations($shopId);

        for ($i = 1; $i < count($lines); $i++) {
            $rowNum = $i + 1;
            $cells = str_getcsv($lines[$i], $delimiter);

            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            if (count($cells) < count($header)) {
                $cells = array_pad($cells, count($header), null);
            } elseif (count($cells) > count($header)) {
                $cells = array_slice($cells, 0, count($header));
            }

            $data = array_combine($header, $cells);
            if ($data === false) {
                $skipped++;
                $errors[] = "Row {$rowNum}: could not parse columns.";
                continue;
            }

            $name = trim((string) ($data['name'] ?? ''));
            $barcode = trim((string) ($data['barcode'] ?? ''));
            $cost = $this->parseCsvNumber($data['cost_price'] ?? 0);
            $sell = $this->parseCsvNumber($data['selling_price'] ?? 0);
            $openingQty = (int) round($this->parseCsvNumber($data['stock_quantity'] ?? 0));
            $alertQty = (int) round($this->parseCsvNumber($data['alert_quantity'] ?? 5));

            if ($name === '') {
                $skipped++;
                $errors[] = "Row {$rowNum}: name is required.";
                continue;
            }

            if ($cost < 0 || $sell < 0) {
                $skipped++;
                $errors[] = "Row {$rowNum}: prices cannot be negative.";
                continue;
            }

            if ($barcode !== '' && Product::where('barcode', $barcode)->exists()) {
                $skipped++;
                $errors[] = "Row {$rowNum}: barcode {$barcode} already exists — skipped.";
                continue;
            }
            if ($barcode === '') {
                $barcode = $this->variants->generateCode();
            }

            try {
                DB::transaction(function () use (
                    $shopId, $data, $name, $barcode, $cost, $sell, $openingQty, $alertQty, $attributeColumns, &$imported, &$stockSet
                ) {
                    $categoryId = null;
                    $categoryName = trim((string) ($data['category'] ?? ''));
                    if ($categoryName !== '') {
                        $slug = \Illuminate\Support\Str::slug($categoryName) ?: ('cat-'.substr(md5($categoryName), 0, 8));
                        $category = Category::firstOrCreate(
                            ['shop_id' => $shopId, 'slug' => $slug],
                            ['name' => $categoryName]
                        );
                        $categoryId = $category->id;
                    }

                    $brandId = null;
                    $brandName = null;
                    $brandRaw = trim((string) ($data['brand'] ?? ''));
                    if ($brandRaw !== '') {
                        $brand = Brand::where('shop_id', $shopId)
                            ->whereRaw('LOWER(name) = ?', [mb_strtolower($brandRaw)])
                            ->first();

                        if (! $brand) {
                            $brand = Brand::create([
                                'shop_id' => $shopId,
                                'name' => $brandRaw,
                                'is_active' => true,
                            ]);
                        }

                        $brandId = $brand->id;
                        $brandName = $brand->name;
                    }

                    $product = Product::create([
                        'shop_id' => $shopId,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'brand_name' => $brandName,
                        'name' => $name,
                        'barcode' => $barcode,
                        'sku' => filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null,
                        'variant_group' => filled($data['variant_group'] ?? null) ? Str::slug((string) $data['variant_group']) : null,
                        'short_description' => filled($data['short_description'] ?? null) ? Str::limit(trim((string) $data['short_description']), 500, '') : null,
                        'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                        'seo_title' => filled($data['seo_title'] ?? null) ? Str::limit(trim((string) $data['seo_title']), 255, '') : null,
                        'meta_description' => filled($data['meta_description'] ?? null) ? Str::limit(trim((string) $data['meta_description']), 500, '') : null,
                        'og_title' => filled($data['og_title'] ?? null) ? Str::limit(trim((string) $data['og_title']), 255, '') : null,
                        'og_description' => filled($data['og_description'] ?? null) ? Str::limit(trim((string) $data['og_description']), 500, '') : null,
                        'cost_price' => $cost,
                        'selling_price' => $sell,
                        'stock_quantity' => 0,
                        'alert_quantity' => max(0, $alertQty),
                        'is_published' => true,
                        'is_new_arrival' => true,
                    ]);

                    $options = [];
                    foreach ($attributeColumns as $column => $attribute) {
                        $value = trim((string) ($data[$column] ?? ''));
                        if ($value === '') {
                            continue;
                        }
                        $options[$attribute->id] = [
                            'value' => $value,
                            'hex' => $attribute->isColor() && filled($data['color_hex'] ?? null) ? trim((string) $data['color_hex']) : null,
                        ];
                    }
                    if ($options !== []) {
                        $this->variants->syncValues($product, $options);
                    }

                    $imageUrl = trim((string) ($data['image_url'] ?? ''));
                    if ($imageUrl !== '') {
                        $this->attachImportedImage($product, $imageUrl);
                    }

                    if ($openingQty > 0) {
                        $this->stock->ensureDefaultLocations($shopId);
                        $movement = $this->stock->setOpeningStock($product, $openingQty);
                        $this->accounts->postOpeningInventory($movement);
                        $stockSet++;
                    }

                    $imported++;
                });
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Row {$rowNum}: ".$e->getMessage();
            }
        }

        $message = "CSV import complete: {$imported} added, {$skipped} skipped.";
        if ($stockSet > 0) {
            $message .= " Opening stock recorded for {$stockSet} product(s).";
        }

        return redirect()
            ->route('products.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 30));
    }

    protected function detectCsvDelimiter(string $headerLine): string
    {
        $comma = substr_count($headerLine, ',');
        $semi = substr_count($headerLine, ';');
        $tab = substr_count($headerLine, "\t");

        if ($semi > $comma && $semi >= $tab) {
            return ';';
        }
        if ($tab > $comma && $tab >= $semi) {
            return "\t";
        }

        return ',';
    }

    protected function parseCsvNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $raw = trim((string) $value);
        $raw = str_replace(['৳', 'Tk', 'tk', ' '], '', $raw);

        // European format: 1.234,56 → 1234.56
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $raw) || preg_match('/^\d+,\d+$/', $raw)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        return (float) $raw;
    }

    public function barcodes(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $query = Product::where('shop_id', $shopId)
            ->with(['category', 'brand'])
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('brand_name', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get();

        return view('products.barcodes', compact('products', 'categories'));
    }

    public function barcodesPrint(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $ids = collect(explode(',', (string) $request->get('product_ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404, 'Select at least one product to print.');

        $copies = max(1, min(20, (int) $request->get('copies', 1)));

        $products = Product::where('shop_id', $shopId)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        abort_if($products->isEmpty(), 404, 'No products found.');

        return view('products.barcodes-print', compact('products', 'copies'));
    }

    private function applyBrandData(array $data): array
    {
        if (!empty($data['brand_id'])) {
            $brand = Brand::where('shop_id', Auth::user()->shop_id)->find($data['brand_id']);
            $data['brand_name'] = $brand?->name;
        } else {
            $data['brand_id'] = null;
            $data['brand_name'] = null;
        }

        return $data;
    }

    private function normalizeVariantFields(array $data): array
    {
        foreach (['variant_group', 'sku', 'short_description'] as $key) {
            if (array_key_exists($key, $data)) {
                $val = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
                $data[$key] = ($val === '' || $val === null) ? null : $val;
            }
        }

        if (!empty($data['variant_group'])) {
            $data['variant_group'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['variant_group']));
            $data['variant_group'] = trim($data['variant_group'], '-');
        }

        return $data;
    }

    public function applySale(Request $request, Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized access.');
        }

        $listPrice = (float) $product->selling_price;

        $validated = $request->validate([
            'discount_type' => 'required|in:percent,tk',
            'percent' => 'nullable|numeric|min:0.01|max:99.99|required_if:discount_type,percent',
            'sale_price' => 'nullable|numeric|min:0|required_if:discount_type,tk|lt:'.$listPrice,
            'sale_starts_at' => 'required|date',
            'sale_ends_at' => 'required|date|after:sale_starts_at',
        ], [
            'sale_price.lt' => 'Sale price must be lower than the selling price ('.format_taka($listPrice, 'Tk ').').',
            'sale_price.required_if' => 'Enter the offer price in Tk.',
            'percent.required_if' => 'Enter the discount percentage.',
            'sale_ends_at.after' => 'Sale end time must be after the start time.',
        ]);

        if ($validated['discount_type'] === 'percent') {
            $percent = (float) $validated['percent'];
            $salePrice = (float) round($listPrice * (1 - ($percent / 100)));
            if ($salePrice <= 0 || $salePrice >= $listPrice) {
                return back()->withErrors([
                    'percent' => 'Discount must leave an offer price below '.format_taka($listPrice, 'Tk ').'.',
                ])->withInput();
            }
            $label = rtrim(rtrim(number_format($percent, 2), '0'), '.').'% off ('.format_taka($salePrice, 'Tk ').')';
        } else {
            $salePrice = (float) round((float) $validated['sale_price']);
            $label = format_taka($salePrice, 'Tk ');
        }

        $product->applySale(
            $salePrice,
            \Illuminate\Support\Carbon::parse($validated['sale_starts_at']),
            \Illuminate\Support\Carbon::parse($validated['sale_ends_at']),
        );

        return redirect()->route('products.index')->with(
            'success',
            "Sale set on {$product->name}: {$label} until ".$product->fresh()->sale_ends_at->format('d M Y, h:i A').'.'
        );
    }

    public function clearSale(Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized access.');
        }

        $product->clearSale();

        return redirect()->route('products.index')->with('success', "Sale removed from {$product->name}.");
    }

    public function applyBrandSale(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $validated = $request->validate([
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
            'discount_type' => 'required|in:percent,tk',
            'percent' => 'nullable|numeric|min:0.01|max:99.99|required_if:discount_type,percent',
            'amount' => 'nullable|numeric|min:0.01|required_if:discount_type,tk',
            'sale_starts_at' => 'required|date',
            'sale_ends_at' => 'required|date|after:sale_starts_at',
        ], [
            'percent.required_if' => 'Enter the discount percentage.',
            'amount.required_if' => 'Enter the discount amount in Tk.',
            'sale_ends_at.after' => 'Sale end time must be after the start time.',
        ]);

        $brand = Brand::where('shop_id', $shopId)->findOrFail($validated['brand_id']);
        $starts = \Illuminate\Support\Carbon::parse($validated['sale_starts_at']);
        $ends = \Illuminate\Support\Carbon::parse($validated['sale_ends_at']);
        $isPercent = $validated['discount_type'] === 'percent';
        $percent = $isPercent ? (float) $validated['percent'] : 0;
        $amount = ! $isPercent ? (float) $validated['amount'] : 0;

        $products = $this->productsForBrand($shopId, $brand)->get();

        $updated = 0;
        foreach ($products as $product) {
            $list = (float) $product->selling_price;
            if ($list <= 0) {
                continue;
            }

            $salePrice = $isPercent
                ? round($list * (1 - ($percent / 100)), 2)
                : round($list - $amount, 2);

            if ($salePrice <= 0 || $salePrice >= $list) {
                continue;
            }

            $product->applySale($salePrice, $starts, $ends);
            $updated++;
        }

        if ($updated === 0) {
            return redirect()->route('products.index')->with(
                'success',
                "No products found for brand {$brand->name} that can take this discount."
            );
        }

        $label = $isPercent
            ? rtrim(rtrim(number_format($percent, 2), '0'), '.').'%'
            : format_taka($amount, 'Tk ').' off';

        return redirect()->route('products.index')->with(
            'success',
            "{$label} sale applied to {$updated} {$brand->name} product(s) until ".$ends->format('d M Y, h:i A').'.'
        );
    }

    public function clearBrandSale(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $validated = $request->validate([
            'brand_id' => [
                'required',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
        ]);

        $brand = Brand::where('shop_id', $shopId)->findOrFail($validated['brand_id']);

        $products = $this->productsForBrand($shopId, $brand)
            ->where(function ($q) {
                $q->whereNotNull('sale_price')
                    ->orWhereNotNull('sale_starts_at')
                    ->orWhereNotNull('sale_ends_at');
            })
            ->get();

        $cleared = 0;
        foreach ($products as $product) {
            $product->clearSale();
            $cleared++;
        }

        if ($cleared === 0) {
            return redirect()->route('products.index')->with(
                'success',
                "No active or scheduled sales found for brand {$brand->name}."
            );
        }

        return redirect()->route('products.index')->with(
            'success',
            "Sale ended on {$cleared} {$brand->name} product(s)."
        );
    }

    private function productsForBrand(int $shopId, Brand $brand)
    {
        return Product::where('shop_id', $shopId)
            ->where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)
                    ->orWhere(function ($qq) use ($brand) {
                        $qq->whereNull('brand_id')
                            ->where('brand_name', $brand->name);
                    });
            });
    }

    /**
     * Download a remote product image during CSV import (optional image_url column).
     */
    private function attachImportedImage(Product $product, string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'BynnasSocial-ProductImport/1.0'])
                ->get($url);

            if (! $response->successful()) {
                return;
            }

            $bytes = $response->body();
            if ($bytes === '' || strlen($bytes) > 5 * 1024 * 1024) {
                return;
            }

            $mime = (string) ($response->header('Content-Type') ?? '');
            $ext = match (true) {
                str_contains($mime, 'png') => 'png',
                str_contains($mime, 'webp') => 'webp',
                str_contains($mime, 'gif') => 'gif',
                str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
                default => 'jpg',
            };

            $path = 'products/import-'.$product->id.'-'.Str::lower(Str::random(10)).'.'.$ext;
            Storage::disk('public')->put($path, $bytes);

            $product->galleryImages()->create([
                'path' => $path,
                'sort_order' => 0,
            ]);
            $product->forceFill(['image' => $path])->save();
        } catch (\Throwable) {
            // Image is optional — product row still imports without it.
        }
    }

    private function applyImeiList(Product $product, string $rawList): int
    {
        $lines = preg_split('/[\r\n,;]+/', $rawList) ?: [];

        return $product->syncAvailableImeis($lines);
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile|null>  $files
     * @return list<string>
     */
    private function storeGalleryFiles(Product $product, array $files): array
    {
        $existingCount = $product->galleryImages()->count();
        $remaining = max(0, 20 - $existingCount);
        if ($remaining === 0) {
            return [];
        }

        $sort = (int) ($product->galleryImages()->max('sort_order') ?? -1);
        $paths = [];
        $count = 0;

        foreach ($files as $file) {
            if ($count >= $remaining) {
                break;
            }
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $path = $file->store('products', 'public');
            $paths[] = $path;
            $product->galleryImages()->create([
                'path' => $path,
                'sort_order' => ++$sort,
            ]);
            $count++;
        }

        return $paths;
    }

    private function cloneGalleryToProduct(Product $from, Product $to): void
    {
        foreach ($from->galleryImages()->orderBy('sort_order')->orderBy('id')->get() as $image) {
            $to->galleryImages()->create([
                'path' => $image->path,
                'sort_order' => $image->sort_order,
            ]);
        }
        $this->syncPrimaryImageFromGallery($to);
    }

    /**
     * Store unlimited gallery images (capped at 20 per request). First image becomes primary thumbnail.
     *
     * @return list<string> newly stored paths
     */
    private function storeGalleryImages(Request $request, Product $product): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $existingCount = $product->galleryImages()->count();
        $remaining = max(0, 20 - $existingCount);
        if ($remaining === 0) {
            return [];
        }

        $files = array_slice($request->file('images'), 0, $remaining);
        $sort = (int) ($product->galleryImages()->max('sort_order') ?? -1);
        $paths = [];

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $path = $file->store('products', 'public');
            $paths[] = $path;
            $product->galleryImages()->create([
                'path' => $path,
                'sort_order' => ++$sort,
            ]);
        }

        return $paths;
    }

    private function removeGalleryImages(Product $product, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $product->galleryImages()->whereIn('id', $imageIds)->get();
        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }

    private function syncPrimaryImageFromGallery(Product $product): void
    {
        $first = $product->galleryImages()->orderBy('sort_order')->orderBy('id')->value('path');
        $product->forceFill([
            'image' => $first,
            'image_2' => null,
            'image_3' => null,
        ])->saveQuietly();
    }

    private function productReturnRoute(?string $from): array
    {
        if ($from === 'opening-inventory') {
            return [
                'url' => route('supply.opening-inventory.index'),
                'key' => 'opening-inventory',
                'label' => 'Back to Opening Inventory',
            ];
        }

        return [
            'url' => route('products.index'),
            'key' => null,
            'label' => 'Back to Inventory',
        ];
    }
}
