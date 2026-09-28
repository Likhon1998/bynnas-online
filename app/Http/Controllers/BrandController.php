<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('shop_id', Auth::user()->shop_id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands')->where(fn ($q) => $q->where('shop_id', Auth::user()->shop_id)),
            ],
            'logo' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:5120',
            'tagline' => 'nullable|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'shop_id' => Auth::user()->shop_id,
            'name' => $request->name,
            'tagline' => $request->input('tagline'),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = app(\App\Services\BrandLogoService::class)->storeUploaded($request->file('logo'));
        }

        $brand = Brand::create($data);

        if (empty($brand->logo_path)) {
            app(\App\Services\BrandLogoService::class)->ensureStored($brand->fresh());
            $brand->refresh();
        }

        app(\App\Services\WebsiteService::class)->linkOrphanProductsToBrands((int) $brand->shop_id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'logo_url' => $brand->logo_url,
                ],
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand created successfully!');
    }

    public function edit(Brand $brand)
    {
        if ($brand->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        if ($brand->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands')
                    ->where(fn ($q) => $q->where('shop_id', Auth::user()->shop_id))
                    ->ignore($brand->id),
            ],
            'logo' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:5120',
            'tagline' => 'nullable|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'name' => $request->name,
            'tagline' => $request->input('tagline'),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }
            $data['logo_path'] = app(\App\Services\BrandLogoService::class)->storeUploaded($request->file('logo'));
        }

        $brand->update($data);

        if (empty($brand->fresh()->logo_path)) {
            app(\App\Services\BrandLogoService::class)->ensureStored($brand->fresh());
        }

        $brand->products()->update(['brand_name' => $brand->name]);

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully!');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->products()->update(['brand_id' => null, 'brand_name' => null]);
        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand removed.');
    }
}
