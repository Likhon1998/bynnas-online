@php
    $activeMenu = $activeMenu ?? 'dashboard';
    $avatarUrl = auth()->user()->avatarUrl();
    $avatarText = auth()->user()->avatarInitials();
    $displayName = $customer?->name ?? auth()->user()->name;
    $menuClass = fn (string $key) => $activeMenu === $key
        ? 'acct-nav-link is-active'
        : 'acct-nav-link';
@endphp
<aside class="acct-sidebar">
    <div class="acct-sidebar-card">
        <div class="acct-sidebar-user">
            <div class="acct-sidebar-avatar">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
                @else
                    {{ $avatarText }}
                @endif
            </div>
            <div class="acct-sidebar-meta min-w-0">
                <p class="acct-sidebar-name">{{ $displayName }}</p>
                <p class="acct-sidebar-email">{{ auth()->user()->email }}</p>
                @if($customer?->phone)
                    <p class="acct-sidebar-phone">{{ $customer->phone_country_code ? $customer->phone_country_code.' ' : '' }}{{ $customer->phone }}</p>
                @endif
            </div>
        </div>
        @if($activeMenu !== 'edit-profile')
            <a href="{{ route('website.account.profile.edit') }}" class="acct-sidebar-edit">Edit Profile</a>
        @endif

        <div class="acct-nav-wrap">
            <p class="acct-nav-label">Account Menu</p>
            <nav class="acct-nav" aria-label="Account">
                <a href="{{ route('website.account') }}" class="{{ $menuClass('dashboard') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('website.account') }}#recent-orders" class="{{ $menuClass('orders') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>My Orders</span>
                </a>
                <a href="{{ route('website.wishlist') }}" class="{{ $menuClass('wishlist') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span>Wishlist</span>
                </a>
                <a href="{{ route('website.account.profile.edit') }}" class="{{ $menuClass('edit-profile') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Edit Profile</span>
                </a>
                <form method="POST" action="{{ route('website.account.logout') }}" class="acct-nav-logout" onsubmit="if (window.GagetLoader) window.GagetLoader.show('Signing out');">
                    @csrf
                    <button type="submit" class="acct-nav-link acct-nav-link--danger">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </nav>
        </div>
    </div>

    <div class="acct-help-card">
        <div class="acct-help-top">
            <div class="acct-help-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="acct-help-title">Need Help?</p>
                <p class="acct-help-text">We’re here to help you.</p>
            </div>
        </div>
        <a href="{{ route('website.contact') }}" class="acct-help-btn">Contact Support</a>
    </div>
</aside>
