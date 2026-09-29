@php $cls = $class ?? ''; @endphp
@switch($name)
    @case('truck')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 6h13v10H1zM14 9h4.5L22 12.5V16h-8z"/><circle cx="5.5" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/></svg>
        @break
    @case('heart')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 5.6a5 5 0 00-7.1 0L12 7.3l-1.7-1.7a5 5 0 10-7.1 7.1L12 21.5l8.8-8.8a5 5 0 000-7.1z"/></svg>
        @break
    @case('heart-solid')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.8 5.6a5 5 0 00-7.1 0L12 7.3l-1.7-1.7a5 5 0 10-7.1 7.1L12 21.5l8.8-8.8a5 5 0 000-7.1z"/></svg>
        @break
    @case('cash')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v5M18 9.5v5"/></svg>
        @break
    @case('leaf')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 19c0-8 5-13 15-14-1 10-6 15-14 15"/><path d="M5 19c3-4 6-6.5 9.5-8.5"/></svg>
        @break
    @case('shield')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v5.5c0 4.8-3.4 8.3-8 9.5-4.6-1.2-8-4.7-8-9.5V6z"/><path d="M8.8 12.2l2.2 2.2 4.4-4.6"/></svg>
        @break
    @case('arrow-right')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        @break
    @case('chevron-left')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
        @break
    @case('chevron-right')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
        @break
    @case('mail')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/></svg>
        @break
    @case('phone')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/></svg>
        @break
    @case('pin')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 1114 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
        @break
    @case('quote')
        <svg class="{{ $cls }}" viewBox="0 0 32 24" fill="currentColor" aria-hidden="true"><path d="M0 24V14.4C0 6.6 4.2 1.8 12.6 0l1.5 3.2C9.6 4.6 7.4 7.3 7.2 11H13v13zm18 0V14.4C18 6.6 22.2 1.8 30.6 0l1.4 3.2c-4.5 1.4-6.7 4.1-6.9 7.8H31v13z"/></svg>
        @break
    @case('bear')
        <svg class="{{ $cls }}" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8.5" r="3.6"/><circle cx="24" cy="8.5" r="3.6"/><circle cx="16" cy="17.5" r="10"/><circle cx="12.4" cy="15.6" r=".9" fill="currentColor" stroke="none"/><circle cx="19.6" cy="15.6" r=".9" fill="currentColor" stroke="none"/><path d="M13.2 20.6c1.6 1.4 4 1.4 5.6 0"/></svg>
        @break
    @case('sparkle')
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8z"/></svg>
        @break
    @case('cloud')
        <svg class="{{ $cls }}" viewBox="0 0 64 40" fill="currentColor" aria-hidden="true"><path d="M50 40H14A14 14 0 0112.6 12 18 18 0 0146 10a15 15 0 014 30z"/></svg>
        @break
    @case('sun')
        <svg class="{{ $cls }}" viewBox="0 0 64 64" aria-hidden="true"><g stroke="#f4b740" stroke-width="3" stroke-linecap="round"><path d="M32 4v8M32 52v8M4 32h8M52 32h8M12.2 12.2l5.6 5.6M46.2 46.2l5.6 5.6M12.2 51.8l5.6-5.6M46.2 17.8l5.6-5.6"/></g><circle cx="32" cy="32" r="14" fill="#f7c85c"/><circle cx="27" cy="30" r="1.6" fill="#8a5a1c"/><circle cx="37" cy="30" r="1.6" fill="#8a5a1c"/><path d="M27.5 36c2.6 2.2 6.4 2.2 9 0" stroke="#8a5a1c" stroke-width="1.8" fill="none" stroke-linecap="round"/></svg>
        @break
    @default
        <svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8"/></svg>
@endswitch
