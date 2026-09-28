<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class DeliverySettingsController extends Controller
{
    public function edit()
    {
        $settings = SiteSetting::current();
        if (! $settings->exists) {
            $settings->save();
            $settings->refresh();
        }

        return view('cms.delivery.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'delivery_inside_dhaka' => 'required|numeric|min:0|max:999999',
            'delivery_outside_dhaka' => 'required|numeric|min:0|max:999999',
            'delivery_free_min_amount' => 'required|numeric|min:0|max:99999999',
            'delivery_confirmation_amount' => 'nullable|numeric|min:0|max:999999',
        ]);

        $settings = SiteSetting::current();
        if (! $settings->exists) {
            $settings->save();
            $settings->refresh();
        }

        $settings->fill([
            'delivery_inside_dhaka' => round((float) $data['delivery_inside_dhaka'], 2),
            'delivery_outside_dhaka' => round((float) $data['delivery_outside_dhaka'], 2),
            'delivery_free_enabled' => $request->boolean('delivery_free_enabled'),
            'delivery_free_min_amount' => round((float) $data['delivery_free_min_amount'], 2),
            'delivery_cod_enabled' => $request->boolean('delivery_cod_enabled'),
            'delivery_confirmation_enabled' => $request->boolean('delivery_confirmation_enabled'),
            'delivery_confirmation_amount' => round((float) ($data['delivery_confirmation_amount'] ?? 0), 2),
        ]);
        $settings->save();

        return redirect()
            ->route('cms.delivery.edit')
            ->with('success', 'Delivery settings saved. Website checkout will use these rates immediately.');
    }
}
