<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\SiteSetting;
use App\Services\DeliveryChargeService;
use App\Services\PaymentMethodRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliverySettingsController extends Controller
{
    public function __construct(
        protected DeliveryChargeService $delivery,
        protected PaymentMethodRegistry $payments,
    ) {}

    protected function currentSettings(): SiteSetting
    {
        $settings = SiteSetting::current();
        if (! $settings->exists) {
            $settings->save();
            $settings->refresh();
        }

        return $settings;
    }

    public function edit()
    {
        $settings = $this->currentSettings();
        $shopId = (int) Auth::user()->shop_id;

        $zones = DeliveryZone::forShop($shopId)->ordered()->get();
        if ($zones->isEmpty()) {
            foreach ($this->delivery->zones($shopId, $settings) as $i => $zone) {
                DeliveryZone::create([
                    'shop_id' => $shopId,
                    'name' => $zone['name'],
                    'code' => $zone['code'],
                    'fee' => $zone['fee'],
                    'is_active' => true,
                    'is_default' => $zone['is_default'],
                    'sort_order' => $i + 1,
                ]);
            }
            $zones = DeliveryZone::forShop($shopId)->ordered()->get();
        }

        $paymentMethods = $this->payments->all($settings);

        return view('cms.delivery.edit', compact('settings', 'zones', 'paymentMethods'));
    }

    public function update(Request $request)
    {
        $shopId = (int) Auth::user()->shop_id;

        $data = $request->validate([
            'zones' => 'required|array|min:1|max:50',
            'zones.*.id' => 'nullable|integer',
            'zones.*.name' => 'required|string|max:80',
            'zones.*.code' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]*$/'],
            'zones.*.fee' => 'required|numeric|min:0|max:999999',
            'zones.*.note' => 'nullable|string|max:160',
            'zones.*.is_active' => 'nullable|boolean',
            'zones.*.delete' => 'nullable|boolean',
            'default_zone' => 'nullable|string|max:40',
            'delivery_free_min_amount' => 'required|numeric|min:0|max:99999999',
            'delivery_confirmation_amount' => 'nullable|numeric|min:0|max:999999',
            'delivery_confirmation_instructions' => 'nullable|string|max:1000',
        ], [
            'zones.*.name.required' => 'Every delivery zone needs a name.',
            'zones.*.code.regex' => 'Zone codes may only contain lowercase letters, numbers and underscores.',
        ]);

        if ($request->boolean('delivery_confirmation_enabled') && blank($data['delivery_confirmation_instructions'] ?? null)) {
            throw ValidationException::withMessages([
                'delivery_confirmation_instructions' => 'Add payment instructions (e.g. your bKash/Nagad number) so customers know where to send the confirmation charge.',
            ]);
        }
        if ($request->boolean('delivery_confirmation_enabled') && (float) ($data['delivery_confirmation_amount'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'delivery_confirmation_amount' => 'Set a confirmation charge above 0, or turn the confirmation charge off.',
            ]);
        }
        if (! $request->boolean('delivery_cod_enabled') && ! $request->boolean('delivery_confirmation_enabled')) {
            throw ValidationException::withMessages([
                'delivery_cod_enabled' => 'Keep at least one payment method on (cash on delivery or confirmation charge).',
            ]);
        }

        $rows = collect($data['zones'])
            ->reject(fn ($row) => ! empty($row['delete']))
            ->values()
            ->map(function ($row, $i) {
                $code = $row['code'] ?? '';
                if ($code === '') {
                    $code = Str::of($row['name'])->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(40, '')->value() ?: 'zone';
                }

                return [
                    'id' => isset($row['id']) ? (int) $row['id'] : null,
                    'name' => trim($row['name']),
                    'code' => $code,
                    'fee' => round((float) $row['fee'], 2),
                    'note' => filled($row['note'] ?? null) ? trim($row['note']) : null,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                    'sort_order' => $i + 1,
                ];
            });

        if ($rows->where('is_active', true)->isEmpty()) {
            throw ValidationException::withMessages(['zones' => 'Keep at least one active delivery zone.']);
        }
        if ($rows->pluck('code')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['zones' => 'Each zone needs a unique code.']);
        }

        $defaultCode = $data['default_zone'] ?? null;
        $activeCodes = $rows->where('is_active', true)->pluck('code');
        if (! $defaultCode || ! $activeCodes->contains($defaultCode)) {
            $defaultCode = $activeCodes->first();
        }

        DB::transaction(function () use ($rows, $shopId, $defaultCode, $request, $data) {
            $keepIds = [];
            foreach ($rows as $row) {
                $attributes = [
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'fee' => $row['fee'],
                    'note' => $row['note'],
                    'is_active' => $row['is_active'],
                    'is_default' => $row['code'] === $defaultCode,
                    'sort_order' => $row['sort_order'],
                ];

                // Only zones of this shop can be edited.
                $zone = $row['id'] ? DeliveryZone::forShop($shopId)->find($row['id']) : null;
                $zone ??= DeliveryZone::forShop($shopId)->where('code', $row['code'])->first();

                if ($zone) {
                    $zone->update($attributes);
                } else {
                    $zone = DeliveryZone::create(['shop_id' => $shopId] + $attributes);
                }
                $keepIds[] = $zone->id;
            }

            // Orders keep the zone code they were placed with, so removing a zone is safe.
            DeliveryZone::forShop($shopId)->whereNotIn('id', $keepIds)->delete();

            $settings = $this->currentSettings();
            $fees = $rows->pluck('fee', 'code');
            $settings->fill([
                'delivery_inside_dhaka' => $fees[DeliveryChargeService::ZONE_INSIDE] ?? $settings->delivery_inside_dhaka,
                'delivery_outside_dhaka' => $fees[DeliveryChargeService::ZONE_OUTSIDE] ?? $settings->delivery_outside_dhaka,
                'delivery_free_enabled' => $request->boolean('delivery_free_enabled'),
                'delivery_free_min_amount' => round((float) $data['delivery_free_min_amount'], 2),
                'delivery_cod_enabled' => $request->boolean('delivery_cod_enabled'),
                'delivery_confirmation_enabled' => $request->boolean('delivery_confirmation_enabled'),
                'delivery_confirmation_amount' => round((float) ($data['delivery_confirmation_amount'] ?? 0), 2),
                'delivery_confirmation_instructions' => filled($data['delivery_confirmation_instructions'] ?? null)
                    ? trim($data['delivery_confirmation_instructions'])
                    : null,
            ]);
            $settings->save();
        });

        return redirect()
            ->route('cms.delivery.edit')
            ->with('success', 'Delivery settings saved. Website checkout will use these zones and rates immediately.');
    }
}
