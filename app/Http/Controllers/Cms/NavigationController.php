<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\NavigationLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NavigationController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $this->seedDefaultsIfEmpty();

        $mainNav = NavigationLink::where('shop_id', $this->shopId())
            ->where('location', 'main_nav')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $topBarNav = NavigationLink::where('shop_id', $this->shopId())
            ->where('location', 'top_bar')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('cms.navigation.index', compact('mainNav', 'topBarNav'));
    }

    private function seedDefaultsIfEmpty(): void
    {
        if (NavigationLink::where('shop_id', $this->shopId())->exists()) {
            return;
        }

        $defaults = [
            ['label' => 'Home', 'url' => '/', 'location' => 'main_nav', 'sort_order' => 1],
            ['label' => 'Shop', 'url' => '/shop', 'location' => 'main_nav', 'sort_order' => 2],
            ['label' => 'Categories', 'url' => '/shop', 'location' => 'main_nav', 'sort_order' => 3],
            ['label' => 'Brands', 'url' => '/#brands', 'location' => 'main_nav', 'sort_order' => 4],
            ['label' => 'Deals', 'url' => '/shop?filter=deals', 'location' => 'main_nav', 'sort_order' => 5],
            ['label' => 'Blog', 'url' => '/blog', 'location' => 'main_nav', 'sort_order' => 6],
            ['label' => 'Contact', 'url' => '/contact', 'location' => 'main_nav', 'sort_order' => 7],
        ];

        foreach ($defaults as $row) {
            NavigationLink::create(array_merge($row, [
                'shop_id' => $this->shopId(),
                'is_active' => true,
            ]));
        }
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['shop_id'] = $this->shopId();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        NavigationLink::create($data);

        return back()->with('success', 'Navigation link added. It appears on the storefront immediately.');
    }

    public function update(Request $request, NavigationLink $navigation)
    {
        $this->authorizeShop($navigation);
        $data = $this->validated($request, $navigation->id);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $navigation->update($data);

        return back()->with('success', 'Navigation link updated.');
    }

    public function destroy(NavigationLink $navigation)
    {
        $this->authorizeShop($navigation);
        $navigation->delete();

        return back()->with('success', 'Navigation link removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'label' => 'required|string|max:80',
            'url' => 'required|string|max:255',
            'location' => ['required', Rule::in(['main_nav', 'top_bar'])],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
