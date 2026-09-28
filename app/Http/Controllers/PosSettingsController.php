<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosSettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        if (! $user->isAdminUser()) {
            abort(403);
        }

        $shop = Shop::query()->findOrFail($user->shop_id);

        return view('pos.settings', compact('shop'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdminUser()) {
            abort(403);
        }

        $shop = Shop::query()->findOrFail($user->shop_id);

        $shop->update([
            'pos_emi_enabled' => $request->boolean('pos_emi_enabled'),
            'pos_baki_enabled' => $request->boolean('pos_baki_enabled'),
            'pos_sale_enabled' => $request->boolean('pos_sale_enabled'),
        ]);

        return redirect()
            ->route('pos.settings.edit')
            ->with('success', 'POS modes updated. EMI, Baki, and product discounts follow these switches.');
    }
}
