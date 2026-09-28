@extends('website.layout')

@section('title', 'Edit Profile — ' . ($settings->store_name ?? 'Store'))

@section('content')
@php
    $avatarUrl = $user->avatarUrl();
    $avatarInitials = $user->avatarInitials();
    $countryCode = old('phone_country_code', $customer?->phone_country_code ?? '+880');
    $phoneNumber = old('phone', $customer?->phone ?? '');
    $gender = old('gender', $customer?->gender ?? 'male');
    $dob = old('date_of_birth', $customer?->date_of_birth?->format('Y-m-d') ?? '');
    $previewName = trim(old('first_name', $firstName).' '.old('last_name', $lastName)) ?: ($customer?->name ?? $user->name);
    $previewEmail = old('email', $user->email);
    $previewPhone = trim($countryCode.' '.$phoneNumber);
@endphp

<div class="ap-page"
     x-data="{
        preview: @js($avatarUrl),
        initials: @js($avatarInitials),
        previewName: @js($previewName),
        previewEmail: @js($previewEmail),
        previewPhone: @js($previewPhone),
        showCurrent: false,
        showNew: false,
        showConfirm: false,
        deleteOpen: @json($errors->has('password')),
        scrollToPassword() {
            document.getElementById('change-password')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
     }">
    <nav class="ap-breadcrumb">
        <a href="{{ route('home') }}">Home</a>
        <span>›</span>
        <a href="{{ route('website.account') }}">My Account</a>
        <span>›</span>
        <span class="ap-breadcrumb-current">Edit Profile</span>
    </nav>

    <div class="ap-head">
        <h1>Edit Profile</h1>
        <p>Update your personal information and account details.</p>
    </div>

    @if(session('profile_success'))
        <div class="ap-alert ap-alert--ok">
            {{ session('profile_success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="ap-alert ap-alert--err">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="ap-layout">
        <div class="ap-sidebar-col">
            @include('website.partials.account-sidebar', ['activeMenu' => 'edit-profile', 'customer' => $customer, 'activeOrder' => $activeOrder])
        </div>

        <div class="ap-main">
            {{-- Mobile / tablet identity card (always visible near top) --}}
            <div class="ap-card ap-identity ap-identity--compact xl:hidden">
                <div class="ap-identity-row">
                    <div class="ap-avatar ap-avatar--sm">
                        <template x-if="preview">
                            <img :src="preview" alt="Profile preview" class="ap-avatar-img">
                        </template>
                        <template x-if="!preview">
                            <div class="ap-avatar-fallback" x-text="initials"></div>
                        </template>
                        <button type="button" class="ap-avatar-cam" @click="document.getElementById('avatar').click()" aria-label="Change photo">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    </div>
                    <div class="ap-identity-meta min-w-0">
                        <h3 x-text="previewName"></h3>
                        <p x-text="previewEmail"></p>
                        <p x-text="previewPhone"></p>
                    </div>
                </div>
                <button type="button" class="ap-btn-secondary w-full" @click="document.getElementById('avatar').click()">
                    Change Profile Picture
                </button>
            </div>

            <form id="profile-form" method="POST" action="{{ route('website.account.profile.update') }}" enctype="multipart/form-data" class="ap-card ap-form">
                @csrf
                @method('PUT')
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden"
                    @change="const file = $event.target.files[0]; if (file) preview = URL.createObjectURL(file);">

                <div class="ap-form-section">
                    <h2>Personal Information</h2>
                    <div class="ap-fields-2">
                        <div>
                            <label for="first_name">First Name</label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $firstName) }}" required
                                @input="previewName = ($event.target.value + ' ' + (document.getElementById('last_name')?.value || '')).trim()">
                        </div>
                        <div>
                            <label for="last_name">Last Name</label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $lastName) }}"
                                @input="previewName = ((document.getElementById('first_name')?.value || '') + ' ' + $event.target.value).trim()">
                        </div>
                    </div>

                    <div>
                        <label for="email">Email Address</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"
                            @input="previewEmail = $event.target.value">
                    </div>

                    <div>
                        <label for="phone">Phone Number</label>
                        <div class="ap-phone">
                            <select name="phone_country_code" id="phone_country_code"
                                @change="previewPhone = ($event.target.value + ' ' + (document.getElementById('phone')?.value || '')).trim()">
                                <option value="+880" @selected($countryCode === '+880')>+880 BD</option>
                                <option value="+1" @selected($countryCode === '+1')>+1 US</option>
                                <option value="+44" @selected($countryCode === '+44')>+44 UK</option>
                                <option value="+91" @selected($countryCode === '+91')>+91 IN</option>
                                <option value="+971" @selected($countryCode === '+971')>+971 AE</option>
                            </select>
                            <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ $phoneNumber }}" required placeholder="Phone number" autocomplete="tel-national"
                                @input="previewPhone = ((document.getElementById('phone_country_code')?.value || '') + ' ' + $event.target.value).trim()">
                        </div>
                        @error('phone')<p class="ap-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" value="{{ $dob }}">
                    </div>

                    <div>
                        <p class="ap-label">Gender</p>
                        <div class="ap-gender">
                            <label><input type="radio" name="gender" value="male" @checked($gender === 'male')> Male</label>
                            <label><input type="radio" name="gender" value="female" @checked($gender === 'female')> Female</label>
                            <label><input type="radio" name="gender" value="prefer_not_to_say" @checked($gender === 'prefer_not_to_say')> Prefer not to say</label>
                        </div>
                    </div>
                </div>

                <div id="change-password" class="ap-form-section">
                    <h2>Change Password <span>(Optional)</span></h2>
                    <div>
                        <label for="current_password">Current Password</label>
                        <div class="ap-pass">
                            <input id="current_password" name="current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password">
                            <button type="button" @click="showCurrent = !showCurrent" aria-label="Toggle password">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542 7z"/></svg>
                            </button>
                        </div>
                        @error('current_password')<p class="ap-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ap-fields-2">
                        <div>
                            <label for="password">New Password</label>
                            <div class="ap-pass">
                                <input id="password" name="password" :type="showNew ? 'text' : 'password'" autocomplete="new-password">
                                <button type="button" @click="showNew = !showNew" aria-label="Toggle password">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542 7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="password_confirmation">Confirm New Password</label>
                            <div class="ap-pass">
                                <input id="password_confirmation" name="password_confirmation" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password">
                                <button type="button" @click="showConfirm = !showConfirm" aria-label="Toggle password">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542 7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="ap-hint">Password must be at least 8 characters long</p>
                </div>

                <div class="ap-actions">
                    <button type="submit" class="gaget-btn-primary ap-btn">Save Changes</button>
                    <a href="{{ route('website.account') }}" class="gaget-btn-outline ap-btn">Cancel</a>
                </div>
            </form>

            {{-- Security + delete on mobile/tablet (visible below form) --}}
            <div class="ap-stack xl:hidden">
                @include('website.partials.account-profile-side', ['compact' => true, 'avatarUrl' => $avatarUrl, 'memberSince' => $memberSince, 'user' => $user])
            </div>
        </div>

        <div class="ap-rail hidden xl:block space-y-4">
            @include('website.partials.account-profile-side', ['compact' => false, 'avatarUrl' => $avatarUrl, 'memberSince' => $memberSince, 'user' => $user])
        </div>
    </div>

    <div x-show="deleteOpen" x-cloak class="ap-modal" @keydown.escape.window="deleteOpen = false">
        <div class="ap-modal-card" @click.outside="deleteOpen = false">
            <h3>Delete Account</h3>
            <p>Enter your password to permanently delete your account.</p>
            <form method="POST" action="{{ route('website.account.profile.destroy') }}" class="ap-modal-form">
                @csrf
                @method('DELETE')
                <input type="password" name="password" required placeholder="Current password" autocomplete="current-password">
                @error('password')<p class="ap-field-error">{{ $message }}</p>@enderror
                <div class="ap-modal-actions">
                    <button type="submit" class="ap-btn-danger">Delete Account</button>
                    <button type="button" class="ap-btn-secondary" @click="deleteOpen = false">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
