<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\PromoBanner;
use App\Services\CmsImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromoPopupController extends Controller
{
    use ShopScoped;

    private const PLACEMENT = 'popup';

    public function index()
    {
        $banners = $this->popupQuery()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('cms.promo-popups.index', compact('banners'));
    }

    public function create()
    {
        return view('cms.promo-popups.form', [
            'banner' => new PromoBanner([
                'is_active' => true,
                'button_text' => 'Shop Now',
                'sort_order' => 0,
                'placement' => self::PLACEMENT,
            ]),
        ]);
    }

    public function store(Request $request, CmsImageStore $images)
    {
        $data = $this->validated($request, requireImage: true);
        $data['shop_id'] = $this->shopId();
        $data['placement'] = self::PLACEMENT;
        $data['image_path'] = $images->store($request->file('image'), 'cms/promo-popups', 1400, 84);

        $banner = PromoBanner::create($data);

        if ($banner->is_active) {
            $this->deactivateOthers($banner->id);
        }

        return redirect()->route('cms.promo-popups.index')
            ->with('success', 'Offer popup saved. It will appear on the storefront when active.');
    }

    public function edit(PromoBanner $banner)
    {
        $this->authorizePopup($banner);

        return view('cms.promo-popups.form', compact('banner'));
    }

    public function update(Request $request, PromoBanner $banner, CmsImageStore $images)
    {
        $this->authorizePopup($banner);
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $data['image_path'] = $images->store($request->file('image'), 'cms/promo-popups', 1400, 84);
        }

        $data['placement'] = self::PLACEMENT;
        $banner->update($data);

        if ($banner->is_active) {
            $this->deactivateOthers($banner->id);
        }

        return redirect()->route('cms.promo-popups.index')->with('success', 'Offer popup updated.');
    }

    public function destroy(PromoBanner $banner)
    {
        $this->authorizePopup($banner);

        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }
        $banner->delete();

        return back()->with('success', 'Offer popup removed.');
    }

    public function toggle(PromoBanner $banner)
    {
        $this->authorizePopup($banner);

        $banner->is_active = ! $banner->is_active;
        $banner->save();

        if ($banner->is_active) {
            $this->deactivateOthers($banner->id);
        }

        return back()->with('success', $banner->is_active
            ? 'Offer popup is now active on the storefront.'
            : 'Offer popup deactivated.');
    }

    private function popupQuery()
    {
        return PromoBanner::where('shop_id', $this->shopId())
            ->where('placement', self::PLACEMENT);
    }

    private function authorizePopup(PromoBanner $banner): void
    {
        $this->authorizeShop($banner);

        if (($banner->placement ?? '') !== self::PLACEMENT) {
            abort(404);
        }
    }

    private function deactivateOthers(int $exceptId): void
    {
        $this->popupQuery()
            ->where('id', '!=', $exceptId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function validated(Request $request, bool $requireImage = false): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'image' => ($requireImage ? 'required' : 'nullable').'|file|mimes:jpeg,jpg,png,webp,gif|max:8192',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['button_text'] = $data['button_text'] ?: 'Shop Now';
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['theme'] = 'popup';
        $data['subtitle'] = null;

        return $data;
    }
}
