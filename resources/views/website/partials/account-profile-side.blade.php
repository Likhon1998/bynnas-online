@php
    $compact = !empty($compact);
@endphp

@if(! $compact)
<div class="ap-card ap-identity text-center">
    <div class="ap-avatar mx-auto">
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
    <h3 class="ap-identity-name break-words" x-text="previewName"></h3>
    <p class="ap-identity-line break-all" x-text="previewEmail"></p>
    <p class="ap-identity-line break-all" x-text="previewPhone"></p>
    <p class="ap-identity-meta">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Member since {{ $memberSince }}
    </p>
    <button type="button" class="ap-btn-secondary w-full mt-4" @click="document.getElementById('avatar').click()">
        Change Profile Picture
    </button>
    @if($avatarUrl)
        <label class="ap-remove-photo">
            <input type="checkbox" form="profile-form" name="remove_avatar" value="1" @change="preview = $event.target.checked ? null : @js($avatarUrl)">
            Remove photo
        </label>
    @endif
    @error('avatar')<p class="ap-field-error">{{ $message }}</p>@enderror
</div>
@elseif($avatarUrl)
    <div class="ap-card">
        <label class="ap-remove-photo ap-remove-photo--block">
            <input type="checkbox" form="profile-form" name="remove_avatar" value="1" @change="preview = $event.target.checked ? null : @js($avatarUrl)">
            Remove current profile photo
        </label>
        @error('avatar')<p class="ap-field-error">{{ $message }}</p>@enderror
    </div>
@endif

<div class="ap-card">
    <h2 class="ap-card-title">Account Security</h2>
    <p class="ap-card-sub">Manage your account security settings</p>
    <div class="ap-security-list">
        <button type="button" class="ap-security-item" @click="scrollToPassword()">
            <span>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Change Password
            </span>
            <span class="ap-chevron">›</span>
        </button>
        <div class="ap-security-item ap-security-item--muted">
            <span>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Two-Factor Authentication
            </span>
            <span class="ap-soon">Soon</span>
        </div>
        <div class="ap-security-activity">
            <p>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Login Activity
            </p>
            <p class="ap-activity-line">Last profile update: {{ $user->updated_at->format('M j, Y g:i A') }}</p>
            <p class="ap-activity-line">Account created: {{ $memberSince }}</p>
        </div>
    </div>
</div>

<div class="ap-card ap-danger">
    <p>Once you delete your account, there is no going back. Your profile will be removed and you will lose access to order history in your dashboard.</p>
    <button type="button" class="ap-btn-danger-outline" @click="deleteOpen = true">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Delete My Account
    </button>
</div>
