<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $pages = CmsPage::where('shop_id', $this->shopId())->orderBy('sort_order')->orderBy('title')->get();

        return view('cms.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('cms.pages.form', ['page' => new CmsPage(['is_published' => true, 'show_in_footer' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['shop_id'] = $this->shopId();
        CmsPage::create($data);

        return redirect()->route('cms.pages.index')->with('success', 'Page published to website at /page/'.$data['slug']);
    }

    public function edit(CmsPage $page)
    {
        $this->authorizeShop($page);

        return view('cms.pages.form', compact('page'));
    }

    public function update(Request $request, CmsPage $page)
    {
        $this->authorizeShop($page);
        $page->update($this->validated($request, $page));

        return redirect()->route('cms.pages.index')->with('success', 'Page updated on the website.');
    }

    public function destroy(CmsPage $page)
    {
        $this->authorizeShop($page);
        $page->delete();

        return back()->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('cms_pages')->where(fn ($q) => $q->where('shop_id', $this->shopId()))->ignore($page?->id),
            ],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);
        $data['is_published'] = $request->boolean('is_published');
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
