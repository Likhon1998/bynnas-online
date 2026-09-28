<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AccountService;
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
        $categories = Category::where('shop_id', Auth::user()->shop_id)->orderBy('name')->get();
        $brands = Brand::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->orderBy('name')->get();
        $returnTo = $this->productReturnRoute($request->query('from'));

        return view('products.create', compact('categories', 'brands', 'returnTo'));
    }

    public function store(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $isGadget = $request->input('product_mode') === 'gadget';

        if ($isGadget && is_array($request->input('variants'))) {
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

        $rules = [
            'product_mode' => 'required|in:simple,gadget',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'variant_group' => 'nullable|string|max:120',
            'color' => 'nullable|string|max:80',
            'color_hex' => 'nullable|string|max:7',
            'storage' => 'nullable|string|max:40',
            'ram' => 'nullable|string|max:40',
            'requires_imei' => 'nullable|boolean',
            'imei_list' => 'nullable|string|max:10000',
            'availability' => 'nullable|in:in_stock,pre_order,up_coming,out_of_stock',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'pos_discount_type' => 'nullable|in:percent,fixed',
            'pos_discount_value' => 'nullable|numeric|min:0.01|required_with:pos_discount_type',
            'short_description' => 'nullable|string|max:2000',
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
            'brand_id' => [
                'nullable',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('shop_id', $shopId)),
            ],
            'images' => 'nullable|array|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'is_published' => 'nullable|boolean',
            'is_new_arrival' => 'nullable|boolean',
            'is_best_seller' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'alert_quantity' => 'nullable|integer|min:0',
            'return_to' => 'nullable|in:opening-inventory',
        ];

        if ($isGadget) {
            $rules['barcode'] = 'nullable|string|max:100';
            $rules['variants'] = 'required|array|min:1';
            $rules['variants.*.barcode'] = ['required', 'string', 'max:100', Rule::unique('products', 'barcode')];
            $rules['variants.*.color'] = 'nullable|string|max:80';
            $rules['variants.*.color_hex'] = 'nullable|string|max:7';
            $rules['variants.*.ram'] = 'nullable|string|max:40';
            $rules['variants.*.storage'] = 'nullable|string|max:40';
            $rules['variants.*.cost_price'] = 'nullable|numeric|min:0';
            $rules['variants.*.selling_price'] = 'nullable|numeric|min:0';
            $rules['variants.*.stock_quantity'] = 'nullable|integer|min:0';
            $rules['variants.*.imei_list'] = 'nullable|string|max:10000';
            $rules['variants.*.images'] = 'nullable|array|max:20';
            $rules['variants.*.images.*'] = 'image|mimes:jpeg,png,jpg,webp,gif|max:5120';
        } else {
            $rules['barcode'] = ['required', 'string', 'max:100', Rule::unique('products', 'barcode')];
        }

        $validated = $request->validate($rules);

        if ($isGadget) {
            $barcodes = collect($validated['variants'])->pluck('barcode')->map(fn ($b) => trim((string) $b));
            if ($barcodes->filter()->count() !== $barcodes->unique()->count()) {
                return back()->withErrors(['variants' => 'Each variant must have a unique barcode.'])->withInput();
            }
        }

        $openingQty = (int) ($validated['stock_quantity'] ?? 0);
        $uploadedPaths = [];
        $requiresImei = $request->boolean('requires_imei');

        if (! empty($validated['pos_discount_type'])) {
            $list = (float) $validated['selling_price'];
            $offer = $validated['pos_discount_type'] === 'percent'
                ? $list * (1 - (float) $validated['pos_discount_value'] / 100)
                : $list - (float) $validated['pos_discount_value'];
            if ($offer <= 0 || $offer >= $list) {
                return back()->withErrors([
                    'pos_discount_value' => 'Discount must leave an offer price below the selling price.',
                ])->withInput();
            }
        } else {
            $validated['pos_discount_type'] = null;
            $validated['pos_discount_value'] = null;
        }

        try {
            $created = DB::transaction(function () use ($request, $validated, $shopId, $openingQty, $isGadget, $requiresImei, &$uploadedPaths) {
                $shared = [
                    'shop_id' => $shopId,
                    'name' => $validated['name'],
                    'sku' => $validated['sku'] ?? null,
                    'variant_group' => $validated['variant_group'] ?? null,
                    'availability' => $validated['availability'] ?? 'in_stock',
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'pos_discount_type' => $validated['pos_discount_type'] ?? null,
                    'pos_discount_value' => ! empty($validated['pos_discount_type'])
                        ? ($validated['pos_discount_value'] ?? null)
                        : null,
                    'short_description' => $validated['short_description'] ?? null,
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
                $shared = $this->applyBrandData($shared);
                $shared = $this->normalizeVariantFields($shared);

                if (! $isGadget) {
                    $data = array_merge($shared, [
                        'barcode' => trim($validated['barcode']),
                        'color' => $validated['color'] ?? null,
                        'color_hex' => $validated['color_hex'] ?? null,
                        'storage' => $validated['storage'] ?? null,
                        'ram' => $validated['ram'] ?? null,
                    ]);
                    $data = $this->normalizeVariantFields($data);
                    $product = Product::create($data);
                    $uploadedPaths = $this->storeGalleryImages($request, $product);
                    $this->syncPrimaryImageFromGallery($product);

                    $imeiCount = 0;
                    if ($requiresImei) {
                        $imeiCount = $this->applyImeiList($product, (string) ($validated['imei_list'] ?? ''));
                    }

                    $qty = $requiresImei && $imeiCount > 0 ? $imeiCount : $openingQty;
                    if ($qty > 0) {
                        $this->stock->ensureDefaultLocations($product->shop_id);
                        $movement = $this->stock->setOpeningStock($product, $qty);
                        $this->accounts->postOpeningInventory($movement);
                    }

                    return ['count' => 1, 'opening' => $qty, 'product' => $product->fresh(['galleryImages'])];
                }

                // Gadget: one Product per variant — own barcode, optional own price & photos.
                $products = [];
                $totalOpening = 0;
                $first = null;
                $gallerySource = null;
                foreach ($validated['variants'] as $index => $row) {
                    $cost = isset($row['cost_price']) && $row['cost_price'] !== '' && $row['cost_price'] !== null
                        ? (float) $row['cost_price']
                        : (float) $validated['cost_price'];
                    $sell = isset($row['selling_price']) && $row['selling_price'] !== '' && $row['selling_price'] !== null
                        ? (float) $row['selling_price']
                        : (float) $validated['selling_price'];

                    $data = array_merge($shared, [
                        'barcode' => trim($row['barcode']),
                        'color' => $row['color'] ?? null,
                        'color_hex' => $row['color_hex'] ?? null,
                        'storage' => $row['storage'] ?? null,
                        'ram' => $row['ram'] ?? null,
                        'cost_price' => $cost,
                        'selling_price' => $sell,
                    ]);
                    $data = $this->normalizeVariantFields($data);
                    if (empty($data['variant_group'])) {
                        $data['variant_group'] = $this->normalizeVariantFields([
                            'variant_group' => $validated['name'],
                        ])['variant_group'] ?? null;
                    }

                    $product = Product::create($data);
                    if ($index === 0) {
                        $first = $product;
                    }

                    $variantFiles = $request->file("variants.{$index}.images");
                    $hasVariantImages = is_array($variantFiles) && collect($variantFiles)->filter()->isNotEmpty();

                    if ($hasVariantImages) {
                        $paths = $this->storeGalleryFiles($product, $variantFiles);
                        $uploadedPaths = array_merge($uploadedPaths, $paths);
                        $this->syncPrimaryImageFromGallery($product);
                        $gallerySource = $product;
                    } elseif ($index === 0 && $request->hasFile('images')) {
                        $paths = $this->storeGalleryImages($request, $product);
                        $uploadedPaths = array_merge($uploadedPaths, $paths);
                        $this->syncPrimaryImageFromGallery($product);
                        $gallerySource = $product;
                    } elseif ($gallerySource) {
                        $this->cloneGalleryToProduct($gallerySource, $product);
                    }

                    $imeiCount = 0;
                    if ($requiresImei) {
                        $imeiCount = $this->applyImeiList($product, (string) ($row['imei_list'] ?? ''));
                    }
                    $qty = $requiresImei && $imeiCount > 0
                        ? $imeiCount
                        : (int) ($row['stock_quantity'] ?? 0);
                    if ($qty > 0) {
                        $this->stock->ensureDefaultLocations($product->shop_id);
                        $movement = $this->stock->setOpeningStock($product, $qty);
                        $this->accounts->postOpeningInventory($movement);
                    }
                    $totalOpening += $qty;
                    $products[] = $product;
                }

                return [
                    'count' => count($products),
                    'opening' => $totalOpening,
                    'product' => $first?->fresh(['galleryImages']),
                ];
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

        if ($count > 1) {
            $message = "{$count} gadget variants created (each with its own barcode)".($openingQty > 0 ? ", {$openingQty} units stocked." : '.');
        } else {
            $message = $openingQty > 0
                ? "Product added with {$openingQty} units in stock. It can appear in POS and the online store."
                : 'Product added with 0 stock. Set quantity here next time, or use Opening Inventory / Stock Adjustment.';
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    public function edit(Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized access.');
        }

        $categories = Category::where('shop_id', Auth::user()->shop_id)->orderBy('name')->get();
        $brands = Brand::where('shop_id', Auth::user()->shop_id)->orderBy('name')->get();
        $product->load(['galleryImages', 'availableImeis']);

        return view('products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        if ($product->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode,' . $product->id,
            'sku' => 'nullable|string|max:100',
            'variant_group' => 'nullable|string|max:120',
            'color' => 'nullable|string|max:80',
            'color_hex' => 'nullable|string|max:7',
            'storage' => 'nullable|string|max:40',
            'ram' => 'nullable|string|max:40',
            'requires_imei' => 'nullable|boolean',
            'imei_list' => 'nullable|string|max:10000',
            'availability' => 'nullable|in:in_stock,pre_order,up_coming,out_of_stock',
            'cost_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'pos_discount_type' => 'nullable|in:percent,fixed',
            'pos_discount_value' => 'nullable|numeric|min:0.01|required_with:pos_discount_type',
            'short_description' => 'nullable|string|max:2000',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'images' => 'nullable|array|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'is_published' => 'nullable|boolean',
            'is_new_arrival' => 'nullable|boolean',
            'is_best_seller' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        $data = $request->except([
            'image', 'image_2', 'image_3', 'images', 'remove_images', 'stock_quantity',
            'remove_image', 'remove_image_2', 'remove_image_3',
            'is_published', 'is_new_arrival', 'is_best_seller', 'is_featured',
            'product_mode', 'imei_list', 'variants',
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_new_arrival'] = $request->boolean('is_new_arrival');
        $data['is_best_seller'] = $request->boolean('is_best_seller');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['requires_imei'] = $request->boolean('requires_imei');
        $data['availability'] = $request->input('availability', $product->availability ?? 'in_stock');
        if (empty($data['pos_discount_type'])) {
            $data['pos_discount_type'] = null;
            $data['pos_discount_value'] = null;
        }
        $data = $this->applyBrandData($data);
        $data = $this->normalizeVariantFields($data);

        if (! empty($data['pos_discount_type']) && isset($data['pos_discount_value'], $data['selling_price'])) {
            $offer = $data['pos_discount_type'] === 'percent'
                ? (float) $data['selling_price'] * (1 - (float) $data['pos_discount_value'] / 100)
                : (float) $data['selling_price'] - (float) $data['pos_discount_value'];
            if ($offer <= 0 || $offer >= (float) $data['selling_price']) {
                return back()->withErrors([
                    'pos_discount_value' => 'Discount must leave an offer price below the selling price.',
                ])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($request, $product, $data) {
                $product->update($data);
                $this->removeGalleryImages($product, (array) $request->input('remove_images', []));
                $this->storeGalleryImages($request, $product);
                $this->syncPrimaryImageFromGallery($product);

                if ($product->requires_imei) {
                    $this->applyImeiList($product, (string) $request->input('imei_list', ''));
                }
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['imei_list' => $e->getMessage()])->withInput();
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
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
            'name',
            'barcode',
            'sku',
            'category',
            'brand',
            'cost_price',
            'selling_price',
            'stock_quantity',
            'alert_quantity',
            'image_url',
            'color',
            'color_hex',
            'ram',
            'storage',
            'variant_group',
            'short_description',
        ];

        $rows = [
            [
                'Samsung Galaxy S22 — Green / 8GB / 128GB',
                '8801234567001',
                'SKU-S22-G-128',
                'Smartphones',
                'Samsung',
                '24500',
                '28999',
                '10',
                '5',
                '',
                'Green',
                '#16a34a',
                '8GB',
                '128GB',
                'samsung-s22',
                'Demo phone — add photo later or paste image_url.',
            ],
            [
                'Somostel 65W GaN Charger',
                '8801234567002',
                'SKU-CHG-65W',
                'Chargers',
                'Somostel',
                '900',
                '1490',
                '40',
                '8',
                '',
                '',
                '',
                '',
                '',
                '',
                'Fast GaN charger for phones and laptops.',
            ],
            [
                'Oraimo FreePods 4',
                '8801234567003',
                'SKU-EAR-FP4',
                'Earbuds',
                'Oraimo',
                '1600',
                '2290',
                '22',
                '5',
                '',
                'Black',
                '#111827',
                '',
                '',
                '',
                'True wireless earbuds with deep bass.',
            ],
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
            'Content-Disposition' => 'attachment; filename="akhitelecom-products-demo.csv"',
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
            'description' => 'short_description',
            'rom' => 'storage',
        ];
        $header = array_map(fn ($h) => $aliases[$h] ?? $h, $header);

        $required = ['name', 'barcode', 'cost_price', 'selling_price'];
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

            if ($name === '' || $barcode === '') {
                $skipped++;
                $errors[] = "Row {$rowNum}: name and barcode are required.";
                continue;
            }

            if ($cost < 0 || $sell < 0) {
                $skipped++;
                $errors[] = "Row {$rowNum}: prices cannot be negative.";
                continue;
            }

            if (Product::where('barcode', $barcode)->exists()) {
                $skipped++;
                $errors[] = "Row {$rowNum}: barcode {$barcode} already exists — skipped.";
                continue;
            }

            try {
                DB::transaction(function () use (
                    $shopId, $data, $name, $barcode, $cost, $sell, $openingQty, $alertQty, &$imported, &$stockSet
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
                        'color' => filled($data['color'] ?? null) ? trim((string) $data['color']) : null,
                        'color_hex' => filled($data['color_hex'] ?? null) ? trim((string) $data['color_hex']) : null,
                        'ram' => filled($data['ram'] ?? null) ? trim((string) $data['ram']) : null,
                        'storage' => filled($data['storage'] ?? null) ? trim((string) $data['storage']) : null,
                        'short_description' => filled($data['short_description'] ?? null) ? trim((string) $data['short_description']) : null,
                        'cost_price' => $cost,
                        'selling_price' => $sell,
                        'stock_quantity' => 0,
                        'alert_quantity' => max(0, $alertQty),
                        'is_published' => true,
                        'is_new_arrival' => true,
                    ]);

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
        foreach (['variant_group', 'color', 'color_hex', 'storage', 'ram', 'sku', 'short_description'] as $key) {
            if (array_key_exists($key, $data)) {
                $val = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
                $data[$key] = ($val === '' || $val === null) ? null : $val;
            }
        }

        if (!empty($data['variant_group'])) {
            $data['variant_group'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['variant_group']));
            $data['variant_group'] = trim($data['variant_group'], '-');
        }

        if (! empty($data['color_hex']) && ! preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color_hex'])) {
            $data['color_hex'] = null;
        }

        if (array_key_exists('storage', $data) && is_string($data['storage'])) {
            $data['storage'] = normalize_memory_size($data['storage']);
        }
        if (array_key_exists('ram', $data) && is_string($data['ram'])) {
            $data['ram'] = normalize_memory_size($data['ram']);
        }

        unset($data['original_price'], $data['sale_price'], $data['sale_starts_at'], $data['sale_ends_at']);

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
                ->withHeaders(['User-Agent' => 'MaksGadget-ProductImport/1.0'])
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
