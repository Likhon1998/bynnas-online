<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsFaq;
use App\Models\CmsFaqCategory;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $this->seedCategoriesIfEmpty();

        $faqs = CmsFaq::where('shop_id', $this->shopId())
            ->with('faqCategory')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $settings = SiteSetting::current();

        return view('cms.faqs.index', compact('faqs', 'settings'));
    }

    public function create()
    {
        return view('cms.faqs.form', [
            'faq' => new CmsFaq(['is_published' => true, 'sort_order' => 0]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['shop_id'] = $this->shopId();
        CmsFaq::create($data);

        return redirect()->route('cms.faqs.index')->with('success', 'FAQ added to the website Help / FAQ page.');
    }

    public function edit(CmsFaq $faq)
    {
        $this->authorizeShop($faq);

        return view('cms.faqs.form', [
            'faq' => $faq,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, CmsFaq $faq)
    {
        $this->authorizeShop($faq);
        $faq->update($this->validated($request));

        return redirect()->route('cms.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(CmsFaq $faq)
    {
        $this->authorizeShop($faq);
        $faq->delete();

        return back()->with('success', 'FAQ deleted.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'faq_hero_title' => 'nullable|string|max:160',
            'faq_hero_subtitle' => 'nullable|string|max:500',
            'faq_help_title' => 'nullable|string|max:160',
            'faq_help_text' => 'nullable|string|max:500',
            'faq_help_button' => 'nullable|string|max:80',
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

        foreach (['faq_hero_title', 'faq_hero_subtitle', 'faq_help_title', 'faq_help_text', 'faq_help_button'] as $field) {
            if (array_key_exists($field, $data)) {
                $settings->{$field} = $data[$field] !== '' ? $data[$field] : null;
            }
        }

        $settings->save();

        return back()->with('success', 'FAQ page settings saved.');
    }

    private function categories()
    {
        $this->seedCategoriesIfEmpty();

        return CmsFaqCategory::where('shop_id', $this->shopId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function seedCategoriesIfEmpty(): void
    {
        if (CmsFaqCategory::where('shop_id', $this->shopId())->exists()) {
            return;
        }

        foreach ([
            ['name' => 'Orders & Payments', 'slug' => 'orders-payments', 'icon' => 'cart', 'sort_order' => 1],
            ['name' => 'Shipping & Delivery', 'slug' => 'shipping-delivery', 'icon' => 'truck', 'sort_order' => 2],
            ['name' => 'Returns & Refunds', 'slug' => 'returns-refunds', 'icon' => 'refresh', 'sort_order' => 3],
            ['name' => 'Products & Warranty', 'slug' => 'products-warranty', 'icon' => 'shield', 'sort_order' => 4],
            ['name' => 'Account & Security', 'slug' => 'account-security', 'icon' => 'lock', 'sort_order' => 5],
            ['name' => 'Promotions & Discounts', 'slug' => 'promotions-discounts', 'icon' => 'tag', 'sort_order' => 6],
            ['name' => 'Others', 'slug' => 'others', 'icon' => 'help', 'sort_order' => 7],
        ] as $row) {
            CmsFaqCategory::create(array_merge($row, [
                'shop_id' => $this->shopId(),
                'is_active' => true,
            ]));
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category_id' => 'nullable|exists:cms_faq_categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['category'] = optional(
            CmsFaqCategory::where('shop_id', $this->shopId())->find($data['category_id'] ?? null)
        )->name;

        return $data;
    }
}
