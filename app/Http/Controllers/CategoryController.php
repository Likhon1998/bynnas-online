<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\CategoryFilterConfig;
use App\Support\CategoryIcons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('shop_id', Auth::user()->shop_id)
            ->latest()
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $filterDefaults = CategoryFilterConfig::defaults();

        return view('categories.create', compact('filterDefaults'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn ($query) => $query->where('shop_id', Auth::user()->shop_id)),
            ],
            'icon' => ['nullable', 'string', 'max:40', Rule::in(CategoryIcons::keys())],
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $filterOptions = $request->has('filter_enabled') || $request->has('filter_groups')
            ? CategoryFilterConfig::fromRequest($request)
            : CategoryFilterConfig::defaults();

        $payload = [
            'shop_id' => Auth::user()->shop_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => CategoryIcons::resolve($request->input('icon') ?: CategoryIcons::suggest($request->name)),
            'description' => $request->input('description'),
            'filter_options' => $filterOptions,
        ];

        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($payload);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Category created successfully!');
    }

    public function edit(Category $category)
    {
        if ($category->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        $filterConfig = CategoryFilterConfig::for($category);

        return view('categories.edit', compact('category', 'filterConfig'));
    }

    public function update(Request $request, Category $category)
    {
        if ($category->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(fn ($query) => $query->where('shop_id', Auth::user()->shop_id))
                    ->ignore($category->id),
            ],
            'icon' => ['nullable', 'string', 'max:40', Rule::in(CategoryIcons::keys())],
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $payload = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => CategoryIcons::resolve($request->input('icon') ?: CategoryIcons::suggest($request->name)),
            'description' => $request->input('description'),
            'filter_options' => CategoryFilterConfig::fromRequest($request),
        ];

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $payload['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($payload);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        if ($category->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        if ($category->products()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category containing products.');
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category removed.');
    }
}
