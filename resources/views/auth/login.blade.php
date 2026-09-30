<x-login-layout>
    @php
        $brandName = data_get($settings ?? null, 'store_name') ?: config('app.name', 'Bynnas Social');
        $brandLogo = !empty(data_get($settings ?? null, 'logo_path'))
            ? public_storage_url($settings->logo_path)
            : null;
        $brandIcon = !empty(data_get($settings ?? null, 'favicon_path'))
            ? public_storage_url($settings->favicon_path)
            : ($brandLogo ?: null);
        $iconVer = !empty(data_get($settings ?? null, 'favicon_path'))
            ? (@filemtime(public_storage_path($settings->favicon_path)) ?: time())
            : time();
    @endphp

    <div class="bl-stage">
        <span class="bl-blob bl-blob--a" aria-hidden="true"></span>
        <span class="bl-blob bl-blob--b" aria-hidden="true"></span>

        <section class="bl-card" aria-label="Admin sign in">
            <aside class="bl-side">
                <div class="bl-brand">
                    <div class="bl-brand__mark">
                        @if($brandIcon)
                            <img src="{{ $brandIcon }}?v={{ $iconVer }}" alt="">
                        @else
                            {{ mb_strtoupper(mb_substr($brandName, 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        <p class="bl-brand__name">{{ $brandName }}</p>
                        <p class="bl-brand__sub">Admin panel</p>
                    </div>
                </div>

                <div class="bl-hello">
                    <div class="bl-balls" aria-hidden="true"><i></i><i></i><i></i></div>
                    <h2 class="bl-hello__title">Hello again!</h2>
                    <p class="bl-hello__text">Orders, products and happy little customers are waiting for you.</p>
                </div>

                <ul class="bl-perks">
                    <li>
                        <span><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></span>
                        Track and confirm online orders
                    </li>
                    <li>
                        <span><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                        Manage products, combos and stock
                    </li>
                    <li>
                        <span><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg></span>
                        Keep every family coming back
                    </li>
                </ul>
            </aside>

            <div class="bl-main">
                <h1 class="bl-title">Welcome back</h1>
                <p class="bl-lead">Sign in with your admin account to continue.</p>

                @if ($errors->has('email') || $errors->has('password'))
                    <div class="bl-alert" role="alert">
                        <strong>Unable to sign in</strong>
                        {{ $errors->first('email') ?: $errors->first('password') }}
                    </div>
                @elseif (session('error'))
                    <div class="bl-alert" role="alert">
                        <strong>Session expired</strong>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="bl-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="bl-form">
                    @csrf

                    <div>
                        <label for="email" class="bl-label">Email</label>
                        <div class="bl-field">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@company.com">
                            <svg class="bl-field__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="bl-label-row">
                            <label for="password" class="bl-label">Password</label>
                            @if (Route::has('password.request'))
                                <a class="bl-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                            @endif
                        </div>
                        <div class="bl-field">
                            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                            <svg class="bl-field__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>

                    <label class="bl-check" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        Keep me signed in
                    </label>

                    <button type="submit" class="bl-submit">
                        Sign in
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </button>
                </form>

                <p class="bl-foot">
                    &copy; {{ date('Y') }} {{ $brandName }} · Powered by
                    <a href="{{ config('app.powered_by_url', 'https://bynnas.com') }}" target="_blank" rel="noopener noreferrer"><strong>{{ config('app.powered_by', 'Bynnas') }}</strong></a>
                </p>
            </div>
        </section>
    </div>
</x-login-layout>
