@php
    $supplier = $supplier ?? null;
    $isEdit = $supplier !== null;
    $v = function (string $key, $default = '') use ($isEdit, $supplier) {
        if (old($key) !== null) {
            return old($key);
        }
        if ($isEdit) {
            $value = $supplier->{$key} ?? null;
            if ($key === 'address_line_1' && blank($value) && filled($supplier->address)) {
                return $supplier->address;
            }
            return $value ?? $default;
        }
        return $default;
    };
@endphp

<x-app-layout>
    <div class="sup-form-page max-w-[1100px] mx-auto pt-1 pb-14 px-1 sm:px-0 min-w-0">
        <style>
            .sup-form-page {
                --sup-blue: #1d68ff;
                --sup-blue-soft: #eef4ff;
                --sup-blue-border: #c7daff;
                --sup-ink: #0f172a;
                --sup-muted: #64748b;
            }
            .sup-form-page .sup-card {
                background: #fff;
                border: 1px solid #e8edf5;
                border-radius: 14px;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            }
            .sup-form-page .sup-section-title {
                display: flex; align-items: center; gap: 8px;
                color: var(--sup-blue);
                font-size: 13px; font-weight: 800;
                margin-bottom: 14px;
            }
            .sup-form-page .sup-section-title svg { width: 16px; height: 16px; flex-shrink: 0; }
            .sup-form-page .sup-label {
                display: block;
                font-size: 11px; font-weight: 700;
                color: #475569;
                margin-bottom: 5px;
            }
            .sup-form-page .sup-label .req { color: #ef4444; }
            .sup-form-page .sup-hint {
                margin-top: 4px;
                font-size: 10.5px; color: #94a3b8; font-weight: 500;
            }
            .sup-form-page .sup-input,
            .sup-form-page .sup-select,
            .sup-form-page .sup-textarea {
                width: 100%;
                border: 1px solid #e2e8f0;
                border-radius: 9px;
                background: #fff;
                color: var(--sup-ink);
                font-size: 12.5px;
                padding: 8px 11px;
                line-height: 1.35;
            }
            .sup-form-page .sup-textarea { min-height: 96px; resize: vertical; }
            .sup-form-page .sup-input:focus,
            .sup-form-page .sup-select:focus,
            .sup-form-page .sup-textarea:focus {
                outline: none;
                border-color: #93c5fd;
                box-shadow: 0 0 0 3px rgba(29, 104, 255, 0.12);
            }
            .sup-form-page .sup-input.is-invalid,
            .sup-form-page .sup-select.is-invalid,
            .sup-form-page .sup-textarea.is-invalid { border-color: #f87171; }
            .sup-form-page .sup-error { margin-top: 4px; font-size: 10.5px; color: #dc2626; font-weight: 600; }
            .sup-form-page .sup-phone {
                display: flex; gap: 8px; align-items: stretch;
            }
            .sup-form-page .sup-dial {
                position: relative;
                flex-shrink: 0;
                width: 112px;
            }
            .sup-form-page .sup-dial-btn {
                width: 100%; height: 100%; min-height: 36px;
                display: inline-flex; align-items: center; justify-content: space-between; gap: 4px;
                border: 1px solid #e2e8f0; border-radius: 9px; background: #fff;
                padding: 6px 8px; cursor: pointer;
                font-size: 12px; font-weight: 700; color: var(--sup-ink);
            }
            .sup-form-page .sup-dial-btn:hover { border-color: #cbd5e1; background: #f8fafc; }
            .sup-form-page .sup-dial-btn:focus {
                outline: none; border-color: #93c5fd;
                box-shadow: 0 0 0 3px rgba(29, 104, 255, 0.12);
            }
            .sup-form-page .sup-dial-flag {
                width: 20px; height: 14px; object-fit: cover;
                border-radius: 2px; flex-shrink: 0;
                box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
                display: block;
            }
            .sup-form-page .sup-dial-code { font-variant-numeric: tabular-nums; }
            .sup-form-page .sup-dial-caret {
                width: 12px; height: 12px; color: #94a3b8; flex-shrink: 0;
            }
            .sup-form-page .sup-dial-menu {
                position: absolute; z-index: 40; top: calc(100% + 4px); left: 0;
                min-width: 220px; max-height: 240px; overflow-y: auto;
                background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
                padding: 4px;
            }
            .sup-form-page .sup-dial-option {
                width: 100%; display: flex; align-items: center; gap: 8px;
                padding: 7px 8px; border: 0; border-radius: 7px; background: transparent;
                cursor: pointer; text-align: left;
                font-size: 12px; color: var(--sup-ink);
            }
            .sup-form-page .sup-dial-option:hover,
            .sup-form-page .sup-dial-option.is-active { background: var(--sup-blue-soft); }
            .sup-form-page .sup-country {
                position: relative;
                width: 100%;
            }
            .sup-form-page .sup-country-btn {
                width: 100%; min-height: 36px;
                display: inline-flex; align-items: center; justify-content: space-between; gap: 8px;
                border: 1px solid #e2e8f0; border-radius: 9px; background: #fff;
                padding: 7px 11px; cursor: pointer;
                font-size: 12.5px; font-weight: 600; color: var(--sup-ink);
                text-align: left;
            }
            .sup-form-page .sup-country-btn:hover { border-color: #cbd5e1; background: #f8fafc; }
            .sup-form-page .sup-country-btn:focus {
                outline: none; border-color: #93c5fd;
                box-shadow: 0 0 0 3px rgba(29, 104, 255, 0.12);
            }
            .sup-form-page .sup-country-btn.is-invalid { border-color: #f87171; }
            .sup-form-page .sup-country-menu {
                position: absolute; z-index: 40; top: calc(100% + 4px); left: 0; right: 0;
                max-height: 260px; overflow-y: auto;
                background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
                padding: 4px;
            }
            .sup-form-page .sup-country-option {
                width: 100%; display: flex; align-items: center; gap: 8px;
                padding: 8px 10px; border: 0; border-radius: 7px; background: transparent;
                cursor: pointer; text-align: left;
                font-size: 12.5px; color: var(--sup-ink); font-weight: 600;
            }
            .sup-form-page .sup-country-option:hover,
            .sup-form-page .sup-country-option.is-active { background: var(--sup-blue-soft); }
            .sup-form-page .sup-affix {
                position: relative;
            }
            .sup-form-page .sup-affix .sup-input { padding-right: 44px; }
            .sup-form-page .sup-affix .sup-input.has-prefix { padding-left: 36px; padding-right: 11px; }
            .sup-form-page .sup-affix-suffix {
                position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                font-size: 11px; font-weight: 800; color: #64748b; pointer-events: none;
            }
            .sup-form-page .sup-affix-prefix {
                position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
                color: #94a3b8; pointer-events: none;
            }
            .sup-form-page .sup-affix-prefix svg { width: 14px; height: 14px; }
            .sup-form-page .sup-btn-primary {
                display: inline-flex; align-items: center; justify-content: center; gap: 7px;
                background: var(--sup-blue); color: #fff;
                font-size: 12.5px; font-weight: 700;
                padding: 9px 16px; border-radius: 9px; border: 0;
            }
            .sup-form-page .sup-btn-primary:hover { background: #1557e0; }
            .sup-form-page .sup-btn-ghost {
                display: inline-flex; align-items: center; justify-content: center;
                background: #fff; color: #334155;
                font-size: 12.5px; font-weight: 700;
                padding: 9px 16px; border-radius: 9px;
                border: 1px solid #e2e8f0; text-decoration: none;
            }
            .sup-form-page .sup-btn-ghost:hover { background: #f8fafc; }
            .sup-form-page .sup-note {
                background: var(--sup-blue-soft);
                border: 1px solid var(--sup-blue-border);
                border-radius: 12px;
                padding: 12px 14px;
                display: flex; gap: 10px; align-items: flex-start;
                font-size: 11.5px; color: #1e3a8a; line-height: 1.45; font-weight: 500;
            }
            .sup-form-page .sup-note-icon {
                width: 18px; height: 18px; border-radius: 999px;
                background: var(--sup-blue); color: #fff;
                display: inline-flex; align-items: center; justify-content: center;
                font-size: 11px; font-weight: 800; flex-shrink: 0; margin-top: 1px;
            }
            .sup-form-page .sup-crumb { font-size: 11.5px; color: #94a3b8; font-weight: 600; }
            .sup-form-page .sup-crumb a { color: #64748b; text-decoration: none; }
            .sup-form-page .sup-crumb a:hover { color: var(--sup-blue); }
            .sup-form-page .sup-crumb span { color: var(--sup-ink); }
        </style>

        {{-- Header --}}
        <div class="mb-5 flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[20px] sm:text-[22px] font-extrabold tracking-tight text-slate-900">
                    {{ $isEdit ? 'Edit Supplier' : 'Add New Supplier' }}
                </h1>
            </div>
            <nav class="sup-crumb shrink-0">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="mx-1.5">›</span>
                <a href="{{ route('supply.suppliers.index') }}">Suppliers</a>
                <span class="mx-1.5">›</span>
                <span>{{ $isEdit ? 'Edit Supplier' : 'Add New Supplier' }}</span>
            </nav>
        </div>

        @include('supply.partials.alerts')

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-[12px] font-semibold text-rose-700">
                Please fix the highlighted fields and try again.
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('supply.suppliers.update', $supplier) : route('supply.suppliers.store') }}"
              class="space-y-4"
              x-data="{ addAnother: false }"
              @submit="if (addAnother) { $el.querySelector('[name=add_another]').value = '1' }">
            @csrf
            @if($isEdit) @method('PUT') @endif
            <input type="hidden" name="add_another" value="0">

            <div class="grid lg:grid-cols-12 gap-4 items-start">
                {{-- Left column --}}
                <div class="lg:col-span-7 space-y-4">
                    {{-- Supplier Information --}}
                    <section class="sup-card p-4 sm:p-5">
                        <h2 class="sup-section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Supplier Information
                        </h2>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="sup-label">Supplier Name <span class="req">*</span></label>
                                <input type="text" name="name" value="{{ $v('name') }}" placeholder="Enter supplier name" required
                                       class="sup-input {{ $errors->has('name') ? 'is-invalid' : '' }}">
                                @error('name') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Contact Person</label>
                                <input type="text" name="contact_person" value="{{ $v('contact_person') }}" placeholder="Enter contact person name"
                                       class="sup-input {{ $errors->has('contact_person') ? 'is-invalid' : '' }}">
                                @error('contact_person') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Email</label>
                                <input type="email" name="email" value="{{ $v('email') }}" placeholder="Enter email address"
                                       class="sup-input {{ $errors->has('email') ? 'is-invalid' : '' }}">
                                @error('email') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Phone <span class="req">*</span></label>
                                <div class="sup-phone">
                                    @include('supply.suppliers.partials.dial-picker', [
                                        'name' => 'phone_dial_code',
                                        'value' => $v('phone_dial_code', '+880'),
                                    ])
                                    <input type="text" name="phone" value="{{ $v('phone') }}" placeholder="1XXXXXXXXX" required
                                           class="sup-input {{ $errors->has('phone') ? 'is-invalid' : '' }}">
                                </div>
                                @error('phone') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Alternative Phone</label>
                                <div class="sup-phone">
                                    @include('supply.suppliers.partials.dial-picker', [
                                        'name' => 'alt_phone_dial_code',
                                        'value' => $v('alt_phone_dial_code', '+880'),
                                    ])
                                    <input type="text" name="alt_phone" value="{{ $v('alt_phone') }}" placeholder="Optional"
                                           class="sup-input {{ $errors->has('alt_phone') ? 'is-invalid' : '' }}">
                                </div>
                                @error('alt_phone') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Website</label>
                                <div class="sup-affix">
                                    <span class="sup-affix-prefix">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    </span>
                                    <input type="text" name="website" value="{{ $v('website') }}" placeholder="Enter website (optional)"
                                           class="sup-input has-prefix {{ $errors->has('website') ? 'is-invalid' : '' }}">
                                </div>
                                @error('website') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Tax Number / VAT <span class="font-medium text-slate-400">(Optional)</span></label>
                                <input type="text" name="tax_number" value="{{ $v('tax_number') }}" placeholder="Enter tax number or VAT"
                                       class="sup-input {{ $errors->has('tax_number') ? 'is-invalid' : '' }}">
                                @error('tax_number') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Business Type</label>
                                <select name="business_type" class="sup-select {{ $errors->has('business_type') ? 'is-invalid' : '' }}">
                                    <option value="">Select business type</option>
                                    @foreach(\App\Models\Supplier::BUSINESS_TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($v('business_type') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('business_type') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Address Information --}}
                    <section class="sup-card p-4 sm:p-5"
                             x-data="{
                                country: @js($v('country', 'Bangladesh') ?: 'Bangladesh'),
                                city: @js($v('city')),
                                countries: @js(\App\Models\Supplier::COUNTRIES),
                                citiesByCountry: @js(\App\Models\Supplier::CITIES_BY_COUNTRY),
                                countryOpen: false,
                                get cities() {
                                    return this.citiesByCountry[this.country] || [];
                                },
                                get currentCountry() {
                                    return this.countries[this.country] || this.countries['Bangladesh'];
                                },
                                get isOther() {
                                    return this.country === 'Other';
                                },
                                flagUrl(iso) {
                                    return 'https://flagcdn.com/w40/' + String(iso || 'bd').toLowerCase() + '.png';
                                },
                                selectCountry(key) {
                                    this.country = key;
                                    this.countryOpen = false;
                                    if (!(this.citiesByCountry[key] || []).includes(this.city)) {
                                        this.city = '';
                                    }
                                    this.$nextTick(() => this.rebuildCityOptions());
                                },
                                rebuildCityOptions() {
                                    const sel = this.$refs.citySelect;
                                    if (!sel || this.isOther) return;
                                    const keep = this.city;
                                    sel.innerHTML = '';
                                    const blank = document.createElement('option');
                                    blank.value = '';
                                    blank.textContent = 'Select city';
                                    sel.appendChild(blank);
                                    (this.citiesByCountry[this.country] || []).forEach((c) => {
                                        const opt = document.createElement('option');
                                        opt.value = c;
                                        opt.textContent = c;
                                        if (c === keep) opt.selected = true;
                                        sel.appendChild(opt);
                                    });
                                    if (keep && (this.citiesByCountry[this.country] || []).includes(keep)) {
                                        sel.value = keep;
                                    }
                                }
                             }"
                             @keydown.escape.window="countryOpen = false">
                        <h2 class="sup-section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Address Information
                        </h2>

                        <div class="space-y-3">
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="sup-label">Country <span class="req">*</span></label>
                                    <div class="sup-country" @click.outside="countryOpen = false">
                                        <input type="hidden" name="country" :value="country">
                                        <button type="button"
                                                class="sup-country-btn {{ $errors->has('country') ? 'is-invalid' : '' }}"
                                                @click="countryOpen = !countryOpen"
                                                :aria-expanded="countryOpen.toString()">
                                            <span class="inline-flex items-center gap-2 min-w-0">
                                                <img class="sup-dial-flag" :src="flagUrl(currentCountry.iso)" :alt="currentCountry.label" width="20" height="14" loading="lazy">
                                                <span x-text="currentCountry.label"></span>
                                            </span>
                                            <svg class="sup-dial-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div class="sup-country-menu" x-show="countryOpen" x-cloak x-transition.opacity.duration.100ms>
                                            <template x-for="(meta, key) in countries" :key="key">
                                                <button type="button"
                                                        class="sup-country-option"
                                                        :class="key === country && 'is-active'"
                                                        @click="selectCountry(key)">
                                                    <img class="sup-dial-flag" :src="flagUrl(meta.iso)" :alt="meta.label" width="20" height="14" loading="lazy">
                                                    <span x-text="meta.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    @error('country') <p class="sup-error">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="sup-label">City <span class="req">*</span></label>
                                    <select name="city" x-show="!isOther" x-cloak x-ref="citySelect" x-model="city" required
                                            :disabled="isOther"
                                            class="sup-select {{ $errors->has('city') ? 'is-invalid' : '' }}"
                                            x-effect="rebuildCityOptions()">
                                        <option value="">Select city</option>
                                    </select>
                                    <input type="text" name="city" x-show="isOther" x-cloak x-model="city" required
                                           :disabled="!isOther"
                                           placeholder="Enter city name"
                                           class="sup-input {{ $errors->has('city') ? 'is-invalid' : '' }}">
                                    @error('city') <p class="sup-error">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="sup-label">Address Line 1 <span class="req">*</span></label>
                                <input type="text" name="address_line_1" value="{{ $v('address_line_1') }}" placeholder="Street address, building, floor" required
                                       class="sup-input {{ $errors->has('address_line_1') ? 'is-invalid' : '' }}">
                                @error('address_line_1') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="sup-label">Address Line 2</label>
                                <input type="text" name="address_line_2" value="{{ $v('address_line_2') }}" placeholder="Apartment, suite, landmark (optional)"
                                       class="sup-input {{ $errors->has('address_line_2') ? 'is-invalid' : '' }}">
                                @error('address_line_2') <p class="sup-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="sup-label">State / Province</label>
                                    <input type="text" name="state" value="{{ $v('state') }}" placeholder="Write state / province"
                                           class="sup-input {{ $errors->has('state') ? 'is-invalid' : '' }}">
                                    @error('state') <p class="sup-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="sup-label">Postal Code</label>
                                    <input type="text" name="postal_code" value="{{ $v('postal_code') }}" placeholder="Write postal code"
                                           class="sup-input {{ $errors->has('postal_code') ? 'is-invalid' : '' }}">
                                    @error('postal_code') <p class="sup-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Right column --}}
                <div class="lg:col-span-5 space-y-4">
                    {{-- Additional Information --}}
                    <section class="sup-card p-4 sm:p-5">
                        <h2 class="sup-section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            Additional Information
                        </h2>
                        <div>
                            <label class="sup-label">Notes <span class="font-medium text-slate-400">(Optional)</span></label>
                            <textarea name="notes" rows="4" placeholder="Enter any additional notes..."
                                      class="sup-textarea {{ $errors->has('notes') ? 'is-invalid' : '' }}">{{ $v('notes') }}</textarea>
                            @error('notes') <p class="sup-error">{{ $message }}</p> @enderror
                        </div>

                        @if($isEdit)
                            <div class="mt-3">
                                <input type="hidden" name="is_active" value="0">
                                <label class="inline-flex items-center gap-2 text-[12px] font-semibold text-slate-700">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))
                                           class="rounded border-slate-300 text-[#1d68ff] focus:ring-[#1d68ff]">
                                    Active supplier
                                </label>
                            </div>
                        @endif
                    </section>

                    <div class="sup-note">
                        <span class="sup-note-icon">i</span>
                        <p>You can manage supplier details, view purchase history and track payments after creating the supplier.</p>
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <a href="{{ route('supply.suppliers.index') }}" class="sup-btn-ghost self-start">Cancel</a>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    @unless($isEdit)
                        <button type="submit" class="sup-btn-ghost" @click="addAnother = true">Save &amp; Add Another</button>
                    @endunless
                    <button type="submit" class="sup-btn-primary" @click="addAnother = false">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        {{ $isEdit ? 'Update Supplier' : 'Save Supplier' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
