<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsBlog;
use App\Models\CmsBlogCategory;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $blogs = CmsBlog::where('shop_id', $this->shopId())
            ->with('category')
            ->latest('published_at')
            ->latest('id')
            ->paginate(15);
        $settings = SiteSetting::current();

        return view('cms.blogs.index', compact('blogs', 'settings'));
    }

    public function create()
    {
        $categories = $this->categories();

        return view('cms.blogs.form', [
            'blog' => new CmsBlog(['is_published' => true, 'author_name' => auth()->user()->name]),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['shop_id'] = $this->shopId();
        if ($request->hasFile('cover')) {
            $data['cover_image'] = $request->file('cover')->store('cms/blogs', 'public');
        }
        CmsBlog::create($data);

        return redirect()->route('cms.blogs.index')->with('success', 'Blog post saved. Live at /blog/'.$data['slug']);
    }

    public function edit(CmsBlog $blog)
    {
        $this->authorizeShop($blog);
        $categories = $this->categories();

        return view('cms.blogs.form', compact('blog', 'categories'));
    }

    public function update(Request $request, CmsBlog $blog)
    {
        $this->authorizeShop($blog);
        $data = $this->validated($request, $blog);
        if ($request->hasFile('cover')) {
            if ($blog->cover_image) {
                Storage::disk('public')->delete($blog->cover_image);
            }
            $data['cover_image'] = $request->file('cover')->store('cms/blogs', 'public');
        }
        $blog->update($data);

        return redirect()->route('cms.blogs.index')->with('success', 'Blog post updated.');
    }

    public function destroy(CmsBlog $blog)
    {
        $this->authorizeShop($blog);
        if ($blog->cover_image) {
            Storage::disk('public')->delete($blog->cover_image);
        }
        $blog->delete();

        return back()->with('success', 'Blog post deleted.');
    }

    public function updateSettings(Request $request)
    {
        // Empty file inputs can break "file" validation on some browsers — ignore when no upload.
        if (!$request->hasFile('blog_hero_image')) {
            $request->request->remove('blog_hero_image');
            $request->files->remove('blog_hero_image');
        }

        $data = $request->validate([
            'blog_hero_kicker' => 'nullable|string|max:80',
            'blog_hero_title' => 'nullable|string|max:160',
            'blog_hero_subtitle' => 'nullable|string|max:500',
            'blog_articles_title' => 'nullable|string|max:120',
            'blog_newsletter_title' => 'nullable|string|max:160',
            'blog_newsletter_text' => 'nullable|string|max:500',
            'blog_feature_1_title' => 'nullable|string|max:80',
            'blog_feature_1_text' => 'nullable|string|max:160',
            'blog_feature_2_title' => 'nullable|string|max:80',
            'blog_feature_2_text' => 'nullable|string|max:160',
            'blog_feature_3_title' => 'nullable|string|max:80',
            'blog_feature_3_text' => 'nullable|string|max:160',
            'blog_hero_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $settings = SiteSetting::query()->first();
        if (!$settings) {
            $settings = new SiteSetting();
            $settings->default_shop_id = $this->shopId();
            $settings->store_name = config('app.name', 'Maks Gadget');
            $settings->currency_code = 'BDT';
            $settings->currency_symbol = 'Tk';
        }

        $settings->default_shop_id = $settings->default_shop_id ?: $this->shopId();

        $textFields = [
            'blog_hero_kicker', 'blog_hero_title', 'blog_hero_subtitle', 'blog_articles_title',
            'blog_newsletter_title', 'blog_newsletter_text',
            'blog_feature_1_title', 'blog_feature_1_text',
            'blog_feature_2_title', 'blog_feature_2_text',
            'blog_feature_3_title', 'blog_feature_3_text',
        ];
        foreach ($textFields as $field) {
            if (array_key_exists($field, $data)) {
                $settings->{$field} = $data[$field] !== '' ? $data[$field] : null;
            }
        }

        if ($request->hasFile('blog_hero_image')) {
            if ($settings->blog_hero_image) {
                Storage::disk('public')->delete($settings->blog_hero_image);
            }
            $settings->blog_hero_image = $request->file('blog_hero_image')->store('cms/blog-hero', 'public');
        }

        $settings->save();

        return redirect()
            ->route('cms.blogs.index')
            ->with('success', 'Blog page settings saved. Open “View on website” to confirm.');
    }

    private function categories()
    {
        if (! CmsBlogCategory::where('shop_id', $this->shopId())->exists()) {
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

        return CmsBlogCategory::where('shop_id', $this->shopId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request, ?CmsBlog $blog = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('cms_blogs')->where(fn ($q) => $q->where('shop_id', $this->shopId()))->ignore($blog?->id),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('cms_blog_categories', 'id')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
            'reading_time' => 'nullable|integer|min:1|max:120',
            'published_at' => 'nullable|date',
            'cover' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['category_id'] = $data['category_id'] ?? null;
        $data['reading_time'] = $data['reading_time'] ?? null;

        if ($data['is_published']) {
            // Blank publish date = go live now (don't leave a “later today” scheduled time).
            $data['published_at'] = filled($request->input('published_at'))
                ? $data['published_at']
                : now();
        } else {
            $data['published_at'] = $data['published_at'] ?? null;
        }

        return $data;
    }
}
