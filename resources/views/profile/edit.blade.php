<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ __('My Profile') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">Manage your account details for {{ config('app.name', 'Maks Gadget') }}</p>
        </div>
    </x-slot>

    @php
        $roleLabel = $user->getRoleNames()->first()
            ?? (is_string($user->role) ? str_replace('_', ' ', ucfirst($user->role)) : 'Staff');
        $shopName = $user->shop?->name ?? 'Your store';
        $avatarUrl = $user->avatarUrl();
        $initials = $user->avatarInitials();
    @endphp

    <div class="py-2"
         x-data="{
            preview: @js($avatarUrl),
            initials: @js($initials),
            previewName: @js($user->name),
            previewEmail: @js($user->email),
            removeAvatar: false,
            active: @js($errors->updatePassword->isNotEmpty() ? 'password' : ($errors->userDeletion->isNotEmpty() ? 'danger' : 'profile')),
            showCurrent: false,
            showNew: false,
            showConfirm: false,
         }">

        @if (session('status') === 'profile-updated')
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] font-semibold text-emerald-700"
                 x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)">
                Profile saved successfully.
            </div>
        @endif

        @if (session('status') === 'password-updated')
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] font-semibold text-emerald-700"
                 x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)">
                Password updated successfully.
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-[13px] text-rose-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-[280px_minmax(0,1fr)]">
            {{-- Left: identity card --}}
            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-center">
                    <div class="relative mx-auto h-28 w-28">
                        <div class="h-28 w-28 overflow-hidden rounded-full border-4 border-white bg-blue-100 shadow-md ring-1 ring-slate-100">
                            <template x-if="preview && !removeAvatar">
                                <img :src="preview" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!preview || removeAvatar">
                                <div class="flex h-full w-full items-center justify-center text-2xl font-extrabold text-blue-700" x-text="initials"></div>
                            </template>
                        </div>
                        <button type="button" @click="document.getElementById('avatar').click()"
                                class="absolute bottom-1 right-1 flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white shadow hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    </div>

                    <h3 class="mt-4 text-[16px] font-bold text-slate-900" x-text="previewName"></h3>
                    <p class="mt-1 text-[12px] text-slate-500" x-text="previewEmail"></p>
                    <span class="mt-3 inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">{{ $roleLabel }}</span>

                    <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-left">
                        <div class="flex items-start gap-2.5 text-[12px] text-slate-600">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Store</p>
                                <p class="font-semibold text-slate-800">{{ $shopName }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 text-[12px] text-slate-600">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Member since</p>
                                <p class="font-semibold text-slate-800">{{ $user->created_at?->format('M Y') ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="document.getElementById('avatar').click()"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">
                        Change photo
                    </button>
                    <button type="button" x-show="preview && !removeAvatar" x-cloak
                            @click="removeAvatar = true; preview = null; document.getElementById('remove_avatar').value = '1'"
                            class="mt-2 inline-flex w-full items-center justify-center rounded-xl px-4 py-2 text-[12px] font-semibold text-rose-600 hover:bg-rose-50">
                        Remove photo
                    </button>
                </div>

                <nav class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                    <button type="button" @click="active = 'profile'"
                            :class="active === 'profile' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[13px] font-semibold transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Profile information
                    </button>
                    <button type="button" @click="active = 'password'"
                            :class="active === 'password' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[13px] font-semibold transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Password & security
                    </button>
                    <button type="button" @click="active = 'danger'"
                            :class="active === 'danger' ? 'bg-rose-50 text-rose-700' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[13px] font-semibold transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        Danger zone
                    </button>
                </nav>
            </aside>

            {{-- Right: forms --}}
            <div class="min-w-0 space-y-5">
                <div x-show="active === 'profile'">
                    @include('profile.partials.update-profile-information-form')
                </div>
                <div x-show="active === 'password'" x-cloak>
                    @include('profile.partials.update-password-form')
                </div>
                <div x-show="active === 'danger'" x-cloak>
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
