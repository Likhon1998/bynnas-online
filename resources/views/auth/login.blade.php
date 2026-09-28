<x-login-layout>
    @php
        $brandName = data_get($settings ?? null, 'store_name') ?: config('app.name', 'Maks Gadget');
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

    <div class="neon-stage">
        <div class="neon-bg" aria-hidden="true">
            <div class="neon-bg__grid"></div>
        </div>

        <div class="neon-panel-wrap">
            <section class="neon-panel" aria-label="Admin sign in">
                <div class="neon-panel__inner">
                    <div class="neon-brand">
                        <div class="neon-brand__mark">
                            @if($brandIcon)
                                <img src="{{ $brandIcon }}?v={{ $iconVer }}" alt="">
                            @else
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="neon-brand__meta">
                            <p class="neon-brand__name">{{ $brandName }}</p>
                            <p class="neon-brand__sub">Administrator portal</p>
                        </div>
                    </div>

                    <h1 class="neon-title">Welcome back</h1>
                    <p class="neon-lead">Sign in with your admin credentials to continue.</p>

                    @if ($errors->has('email') || $errors->has('password'))
                        <div class="neon-alert" role="alert">
                            <strong>Unable to sign in</strong>
                            {{ $errors->first('email') ?: $errors->first('password') }}
                        </div>
                    @elseif (session('error'))
                        <div class="neon-alert" role="alert">
                            <strong>Session expired</strong>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="neon-status">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.store') }}" class="neon-form">
                        @csrf

                        <div>
                            <label for="email" class="neon-label">Email</label>
                            <div class="neon-field">
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="name@company.com"
                                >
                                <svg class="neon-field__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>

                        <div>
                            <div class="neon-label-row">
                                <label for="password" class="neon-label">Password</label>
                                @if (Route::has('password.request'))
                                    <a class="neon-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                                @endif
                            </div>
                            <div class="neon-field">
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                >
                                <svg class="neon-field__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                        </div>

                        <label class="neon-check" for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember">
                            Keep me signed in
                        </label>

                        <button type="submit" class="neon-submit">
                            Sign in
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </button>
                    </form>

                    <p class="neon-foot">
                        &copy; {{ date('Y') }} {{ $brandName }} · Powered by
                        <a href="{{ config('app.powered_by_url', 'https://bynnas.com') }}" target="_blank" rel="noopener noreferrer" class="neon-foot__link">
                            <strong>{{ config('app.powered_by', 'Bynnas') }}</strong>
                        </a>
                    </p>
                </div>
            </section>
        </div>
    </div>
</x-login-layout>
