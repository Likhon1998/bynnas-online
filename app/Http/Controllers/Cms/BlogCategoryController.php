<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsBlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $this->seedDefaultsIfEmpty();

        $categories = CmsBlogCategory::where('shop_id', $this->shopId())
            ->withCount('blogs')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('cms.blog-categories.index', compact('categories'));
    }

    private function seedDefaultsIfEmpty(): void
    {
        if (CmsBlogCategory::where('shop_id', $this->shopId())->exists()) {
            return;
        }

        foreach ([
            ['name' => 'Product Reviews', 'slug' => 'product-reviews', 'color' => 'blue', 'sort_order' => 1],
            ['name' => 'Tech News', 'slug' => 'tech-news', 'color' => 'emerald', 'sort_order' => 2],
            ['name' => 'Buying Guides', 'slug' => 'buying-guides', 'color' => 'violet', 'sort_order' => 3],
            ['name' => 'How-To', 'slug' => 'how-to', 'color' => 'amber', 'sort_order' => 4],
        ] as $row) {
            CmsBlogCategory::create(array_merge($row, [
                'shop_id' => $this->shopId(),
                'is_active' => true,
            ]));
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => [
                'nullable', 'string', 'max:120',
                Rule::unique('cms_blog_categories')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'color' => 'nullable|in:blue,emerald,amber,rose,violet,slate',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['shop_id'] = $this->shopId();
        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $data['color'] = $data['color'] ?? 'blue';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = true;

        CmsBlogCategory::create($data);

        return back()->with('success', 'Blog category added.');
    }

    public function update(Request $request, CmsBlogCategory $blog_category)
    {
        $this->authorizeShop($blog_category);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => [
                'nullable', 'string', 'max:120',
                Rule::unique('cms_blog_categories')->where(fn ($q) => $q->where('shop_id', $this->shopId()))->ignore($blog_category->id),
            ],
            'color' => 'nullable|in:blue,emerald,amber,rose,violet,slate',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $blog_category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(CmsBlogCategory $blog_category)
    {
        $this->authorizeShop($blog_category);
        $blog_category->blogs()->update(['category_id' => null]);
        $blog_category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
