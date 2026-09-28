<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsReview;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $reviews = CmsReview::where('shop_id', $this->shopId())
            ->with('product')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('cms.reviews.index', compact('reviews'));
    }

    public function create()
    {
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get(['id', 'name']);

        return view('cms.reviews.form', [
            'review' => new CmsReview(['rating' => 5, 'is_published' => true, 'is_featured' => true]),
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['shop_id'] = $this->shopId();
        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('cms/reviews', 'public');
        }
        CmsReview::create($data);

        return redirect()->route('cms.reviews.index')->with('success', 'Review saved. Featured reviews appear on the homepage.');
    }

    public function edit(CmsReview $review)
    {
        $this->authorizeShop($review);
        $products = Product::where('shop_id', $this->shopId())->orderBy('name')->get(['id', 'name']);

        return view('cms.reviews.form', compact('review', 'products'));
    }

    public function update(Request $request, CmsReview $review)
    {
        $this->authorizeShop($review);
        $data = $this->validated($request);
        if ($request->hasFile('avatar')) {
            if ($review->avatar_path) {
                Storage::disk('public')->delete($review->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('cms/reviews', 'public');
        }
        $review->update($data);

        return redirect()->route('cms.reviews.index')->with('success', 'Review updated.');
    }

    public function destroy(CmsReview $review)
    {
        $this->authorizeShop($review);
        if ($review->avatar_path) {
            Storage::disk('public')->delete($review->avatar_path);
        }
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_title' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'required|string|max:2000',
            'product_id' => 'nullable|exists:products,id',
            'sort_order' => 'nullable|integer|min:0',
            'avatar' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:2048',
        ]);

        if (!empty($data['product_id'])) {
            $ok = Product::where('shop_id', $this->shopId())->where('id', $data['product_id'])->exists();
            abort_unless($ok, 422);
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
