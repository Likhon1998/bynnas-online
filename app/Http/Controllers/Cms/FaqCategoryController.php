<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsFaqCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FaqCategoryController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $this->seedDefaultsIfEmpty();

        $categories = CmsFaqCategory::where('shop_id', $this->shopId())
            ->withCount('faqs')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('cms.faq-categories.index', compact('categories'));
    }

    private function seedDefaultsIfEmpty(): void
    {
        if (CmsFaqCategory::where('shop_id', $this->shopId())->exists()) {
            return;
        }

        $created = [];
        foreach ([
            ['name' => 'Orders & Payments', 'slug' => 'orders-payments', 'icon' => 'cart', 'sort_order' => 1],
            ['name' => 'Shipping & Delivery', 'slug' => 'shipping-delivery', 'icon' => 'truck', 'sort_order' => 2],
            ['name' => 'Returns & Refunds', 'slug' => 'returns-refunds', 'icon' => 'refresh', 'sort_order' => 3],
            ['name' => 'Products & Warranty', 'slug' => 'products-warranty', 'icon' => 'shield', 'sort_order' => 4],
            ['name' => 'Account & Security', 'slug' => 'account-security', 'icon' => 'lock', 'sort_order' => 5],
            ['name' => 'Promotions & Discounts', 'slug' => 'promotions-discounts', 'icon' => 'tag', 'sort_order' => 6],
            ['name' => 'Others', 'slug' => 'others', 'icon' => 'help', 'sort_order' => 7],
        ] as $row) {
            $created[$row['slug']] = CmsFaqCategory::create(array_merge($row, [
                'shop_id' => $this->shopId(),
                'is_active' => true,
            ]));
        }

        if (\App\Models\CmsFaq::where('shop_id', $this->shopId())->exists()) {
            return;
        }

        $samples = [
            ['orders-payments', 'How do I place an order?', "Browse our store, add items to your cart, then sign in to checkout. Enter your delivery details and confirm — we currently accept cash on delivery (COD)."],
            ['orders-payments', 'What payment methods do you accept?', 'Online orders are cash on delivery (COD) only. Pay the delivery agent when your package arrives.'],
            ['orders-payments', 'Can I change or cancel my order after placing it?', 'Contact support as soon as possible with your order number. We can change or cancel orders that have not yet been prepared for shipping.'],
            ['shipping-delivery', 'How can I track my order?', 'Use Track Order with your Order ID and phone number, or sign in and open My Orders for status updates.'],
            ['shipping-delivery', 'Do you offer international shipping?', 'We currently deliver within our service area. Contact support if you need help with your location.'],
            ['returns-refunds', 'What is your return policy?', 'Most products can be returned within 30 days if unused and in original packaging. Some items may be excluded — see the product page or contact support.'],
            ['returns-refunds', 'How do I request a return or refund?', 'Go to Help Center or contact support with your order number and reason. Once approved, follow the return shipping instructions we send you.'],
            ['products-warranty', 'Are your products covered by warranty?', 'Yes. Eligible gadgets include manufacturer or store warranty as shown on each product page. Keep your invoice for warranty claims.'],
        ];

        foreach ($samples as $i => [$slug, $question, $answer]) {
            $cat = $created[$slug] ?? null;
            \App\Models\CmsFaq::create([
                'shop_id' => $this->shopId(),
                'category_id' => $cat?->id,
                'category' => $cat?->name,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $i + 1,
                'is_published' => true,
            ]);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => [
                'nullable', 'string', 'max:120',
                Rule::unique('cms_faq_categories')->where(fn ($q) => $q->where('shop_id', $this->shopId())),
            ],
            'icon' => 'nullable|in:cart,truck,refresh,shield,lock,tag,help,headset',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['shop_id'] = $this->shopId();
        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $data['icon'] = $data['icon'] ?? 'help';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = true;

        CmsFaqCategory::create($data);

        return back()->with('success', 'FAQ category added.');
    }

    public function update(Request $request, CmsFaqCategory $faq_category)
    {
        $this->authorizeShop($faq_category);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => [
                'nullable', 'string', 'max:120',
                Rule::unique('cms_faq_categories')->where(fn ($q) => $q->where('shop_id', $this->shopId()))->ignore($faq_category->id),
            ],
            'icon' => 'nullable|in:cart,truck,refresh,shield,lock,tag,help,headset',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['icon'] = $data['icon'] ?? 'help';
        $data['is_active'] = $request->boolean('is_active', true);
        $faq_category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(CmsFaqCategory $faq_category)
    {
        $this->authorizeShop($faq_category);
        $faq_category->faqs()->update(['category_id' => null]);
        $faq_category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
