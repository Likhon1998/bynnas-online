<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <script>
        // Available before Vite loads — prevents CSRF mismatch on fast register/login/checkout.
        (function () {
            window.syncCsrfToken = window.syncCsrfToken || function (tokenFromServer) {
                const meta = document.head.querySelector('meta[name="csrf-token"]');
                const token = tokenFromServer || meta?.content;
                if (!token) return null;
                if (meta) meta.content = token;
                document.querySelectorAll('input[name="_token"]').forEach((input) => { input.value = token; });
                return token;
            };
            window.currentCsrfToken = window.currentCsrfToken || function () {
                return document.head.querySelector('meta[name="csrf-token"]')?.content || '';
            };
            window.refreshCsrfToken = window.refreshCsrfToken || async function () {
                try {
                    const res = await fetch(@json(route('csrf.token')), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                        cache: 'no-store',
                    });
                    if (!res.ok) return window.syncCsrfToken();
                    const data = await res.json();
                    return window.syncCsrfToken(data?.csrf_token);
                } catch (e) {
                    return window.syncCsrfToken();
                }
            };
            window.fetchJsonWithCsrf = window.fetchJsonWithCsrf || async function (url, options = {}, retried = false) {
                const method = (options.method || 'GET').toUpperCase();
                const headers = {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                };
                if (method !== 'GET' && method !== 'HEAD') {
                    if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
                        headers['Content-Type'] = 'application/json';
                    }
                    headers['X-CSRF-TOKEN'] = window.currentCsrfToken();
                }
                const res = await fetch(url, { credentials: 'same-origin', ...options, method, headers });
                if (res.status === 419 && !retried) {
                    let token = null;
                    try { token = (await res.clone().json())?.csrf_token || null; } catch (e) {}
                    if (token) window.syncCsrfToken(token);
                    else await window.refreshCsrfToken();
                    return window.fetchJsonWithCsrf(url, options, true);
                }
                return res;
            };
        })();
    </script>
    <title>@yield('title', $settings->store_name ?? config('app.name', 'Maks Gadget'))</title>
    @include('partials.favicon', ['settings' => $settings ?? null])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @php
        $storefrontUser = auth('web')->check() && auth('web')->user()->isStorefrontCustomer()
            ? [
                'name' => auth('web')->user()->name,
                'email' => auth('web')->user()->email,
                'phone' => auth('web')->user()->customerProfile?->phone ?? '',
                'address' => auth('web')->user()->customerProfile?->address ?? '',
            ]
            : null;
        if (! isset($deliveryConfig) || ! is_array($deliveryConfig)) {
            try {
                $deliveryConfig = app(\App\Services\DeliveryChargeService::class)->publicConfig();
            } catch (\Throwable $e) {
                $deliveryConfig = [
                    'inside_dhaka' => 60,
                    'outside_dhaka' => 120,
                    'free_enabled' => true,
                    'free_min_amount' => 10000,
                    'cod_enabled' => true,
                    'confirmation_enabled' => false,
                    'confirmation_amount' => 0,
                    'currency_symbol' => $settings->currency_symbol ?? '৳',
                ];
            }
        }
    @endphp
    <script>
        function readStorefrontList(key) {
            try {
                const raw = localStorage.getItem(key);
                const parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function headerSearch(suggestUrl, shopBaseUrl, currencySymbol) {
            return {
                q: @json(request('search', '')),
                category: @json(request('category', '')),
                open: false,
                loading: false,
                products: [],
                mode: 'best',
                activeIndex: -1,
                currency: currencySymbol || '৳',
                _req: 0,
                get shopUrl() {
                    const params = new URLSearchParams();
                    if (this.q) params.set('search', this.q);
                    if (this.category) params.set('category', this.category);
                    const qs = params.toString();
                    return qs ? `${shopBaseUrl}?${qs}` : shopBaseUrl;
                },
                onFocus() {
                    this.open = true;
                    this.fetchSuggestions();
                },
                async fetchSuggestions() {
                    this.open = true;
                    this.loading = true;
                    this.activeIndex = -1;
                    const req = ++this._req;
                    try {
                        const params = new URLSearchParams({
                            q: this.q || '',
                            category: this.category || '',
                            limit: '8',
                        });
                        const res = await fetch(`${suggestUrl}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        if (req !== this._req) return;
                        this.products = Array.isArray(data.products) ? data.products : [];
                        this.mode = data.mode || (this.q ? 'search' : 'best');
                    } catch (e) {
                        if (req !== this._req) return;
                        this.products = [];
                    } finally {
                        if (req === this._req) this.loading = false;
                    }
                },
                move(step) {
                    if (!this.products.length) return;
                    this.open = true;
                    const len = this.products.length;
                    this.activeIndex = (this.activeIndex + step + len) % len;
                },
                onEnter(event) {
                    if (this.open && this.activeIndex >= 0 && this.products[this.activeIndex]) {
                        event.preventDefault();
                        window.location.href = this.products[this.activeIndex].url;
                    }
                },
            };
        }
        window.headerSearch = headerSearch;

        function storefrontCart() {
            return {
                cart: readStorefrontList('gaget_cart'),
                wishlist: readStorefrontList('gaget_wishlist'),
                cartOpen: false,
                cartBump: false,
                cartSyncing: false,
                cartSyncUrl: @json(route('website.cart.sync')),
                checkoutOpen: false,
                mobileOpen: false,
                mobileCatsOpen: false,
                mobileBrandsOpen: false,
                checkoutStep: 'auth',
                authPurpose: 'account',
                authTab: 'login',
                authLoading: false,
                authMessage: '',
                authMessageOk: false,
                ordering: false,
                orderMessage: '',
                lastOrderId: null,
                lastOrderInvoice: '',
                redirectSeconds: 0,
                _redirectTimer: null,
                orderSuccess: false,
                toastMessage: '',
                toastVisible: false,
                isLoggedIn: @json((bool) $storefrontUser),
                currency: @json($settings->currency_symbol ?? '৳'),
                deliveryConfig: @json($deliveryConfig),
                checkout: {
                    name: @json(data_get($storefrontUser, 'name', '')),
                    phone: @json(data_get($storefrontUser, 'phone', '')),
                    address: @json(data_get($storefrontUser, 'address', '')),
                    zone: 'inside_dhaka',
                    payment_method: 'cash_on_delivery',
                },
                authLogin: { email: '', password: '' },
                authRegister: { name: '', phone: '', email: '', password: '', address: '' },
                get cartCount() { return this.cart.reduce((s, i) => s + (Number(i.qty) || 0), 0); },
                get cartTotal() { return this.cart.reduce((s, i) => s + (Number(i.price) || 0) * (Number(i.qty) || 0), 0); },
                get deliveryQuote() {
                    const cfg = this.deliveryConfig || {};
                    const subtotal = Number(this.cartTotal) || 0;
                    const zone = this.checkout.zone === 'outside_dhaka' ? 'outside_dhaka' : 'inside_dhaka';
                    const base = zone === 'outside_dhaka'
                        ? Number(cfg.outside_dhaka) || 0
                        : Number(cfg.inside_dhaka) || 0;
                    const freeMin = Number(cfg.free_min_amount) || 0;
                    const isFree = !!cfg.free_enabled && subtotal + 0.009 >= freeMin;
                    const deliveryFee = isFree ? 0 : Math.max(0, base);
                    const grand = Math.round((subtotal + deliveryFee) * 100) / 100;
                    let method = this.checkout.payment_method;
                    const codOk = !!cfg.cod_enabled;
                    const confOk = !!cfg.confirmation_enabled && Number(cfg.confirmation_amount) > 0.009;
                    if (method === 'confirmation_charge' && !confOk) method = codOk ? 'cash_on_delivery' : 'confirmation_charge';
                    if (method === 'cash_on_delivery' && !codOk && confOk) method = 'confirmation_charge';
                    const confirmAmt = method === 'confirmation_charge'
                        ? Math.min(grand, Math.max(0, Number(cfg.confirmation_amount) || 0))
                        : 0;
                    return {
                        zone,
                        zoneLabel: zone === 'outside_dhaka' ? 'Outside Dhaka' : 'Inside Dhaka',
                        subtotal,
                        baseFee: base,
                        deliveryFee,
                        isFree,
                        freeReason: isFree ? ('Free delivery over ' + (cfg.currency_symbol || this.currency) + Number(freeMin).toLocaleString()) : null,
                        grandTotal: grand,
                        paymentMethod: method,
                        confirmationAmount: confirmAmt,
                        amountDueLater: Math.max(0, Math.round((grand - confirmAmt) * 100) / 100),
                        codEnabled: codOk,
                        confirmationEnabled: confOk,
                    };
                },
                get wishlistCount() { return this.wishlist.length; },
                init() {
                    this.syncCart({ silent: true });
                    try {
                        const params = new URLSearchParams(window.location.search);
                        if (params.get('signin') === '1' || params.get('signin') === 'true') {
                            const tab = params.get('tab') === 'register' ? 'register' : 'login';
                            this.$nextTick(() => this.openSignIn(tab));
                            params.delete('signin');
                            params.delete('tab');
                            const qs = params.toString();
                            const next = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
                            window.history.replaceState({}, '', next);
                        }
                    } catch (e) {}
                },
                // Persist ids + qty only — display price/stock always come from /cart/sync.
                save() {
                    const slim = this.cart.map((item) => ({
                        id: Number(item.id),
                        qty: Math.max(1, Number(item.qty) || 1),
                    })).filter((item) => item.id > 0);
                    localStorage.setItem('gaget_cart', JSON.stringify(slim));
                },
                saveWishlist() { localStorage.setItem('gaget_wishlist', JSON.stringify(this.wishlist)); },
                async syncCart({ silent = false } = {}) {
                    if (this.cartSyncing) return;
                    const payload = this.cart
                        .map((item) => ({
                            id: Number(item.id),
                            qty: Math.max(1, Number(item.qty) || 1),
                        }))
                        .filter((item) => item.id > 0);

                    if (!payload.length) {
                        this.cart = [];
                        this.save();
                        return;
                    }

                    this.cartSyncing = true;
                    try {
                        const res = await this.csrfFetch(this.cartSyncUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ items: payload }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            if (!silent) this.flashToast(data.message || 'Could not refresh cart prices.');
                            return;
                        }
                        this.cart = Array.isArray(data.items) ? data.items : [];
                        this.save();
                        if (!silent && Array.isArray(data.warnings) && data.warnings.length) {
                            this.flashToast(data.warnings[0]);
                        }
                    } catch (e) {
                        if (!silent) this.flashToast('Could not refresh cart. Please try again.');
                    } finally {
                        this.cartSyncing = false;
                    }
                },
                addToCart(product, qty = 1, openDrawer = true) {
                    if (!product || product.id === undefined || product.id === null) return;

                    const id = Number(product.id);
                    const amount = Math.max(1, Number(qty) || 1);
                    const stockCap = Number(product.stock);
                    const next = this.cart.map((item) => ({ ...item }));
                    const existing = next.find((item) => Number(item.id) === id);
                    const currentQty = existing ? (Number(existing.qty) || 0) : 0;
                    let desired = currentQty + amount;

                    if (Number.isFinite(stockCap) && stockCap >= 0) {
                        if (stockCap < 1) {
                            this.flashToast('This item is out of stock.');
                            return;
                        }
                        if (desired > stockCap) {
                            desired = stockCap;
                            this.flashToast('Only ' + stockCap + ' available.');
                        }
                    }

                    if (existing) {
                        existing.qty = desired;
                        if (Number.isFinite(stockCap)) existing.stock = stockCap;
                        if (product.name) existing.name = product.name;
                        if (product.image) existing.image = product.image;
                        if (product.price !== undefined) existing.price = Number(product.price) || existing.price;
                    } else {
                        next.push({
                            id,
                            name: product.name || 'Product',
                            price: Number(product.price) || 0,
                            image: product.image || '',
                            qty: desired,
                            stock: Number.isFinite(stockCap) ? stockCap : undefined,
                        });
                    }

                    this.cart = next;
                    this.save();
                    this.bumpCart();
                    if (openDrawer) {
                        this.openCart();
                    } else {
                        this.syncCart({ silent: true });
                    }
                },
                bumpCart() {
                    this.cartBump = false;
                    this.$nextTick(() => {
                        this.cartBump = true;
                        clearTimeout(this._cartBumpTimer);
                        this._cartBumpTimer = setTimeout(() => { this.cartBump = false; }, 700);
                    });
                },
                async openCart() {
                    await this.syncCart({ silent: false });
                    this.cartOpen = true;
                    this.$nextTick(() => {
                        const shell = document.querySelector('.gaget-cart-shell');
                        const panel = document.querySelector('.gaget-cart-panel');
                        if (shell) shell.classList.remove('is-closing');
                        if (!panel) return;
                        panel.classList.remove('is-enter');
                        void panel.offsetWidth;
                        panel.classList.add('is-enter');
                    });
                },
                closeCart() {
                    if (!this.cartOpen) return;
                    const shell = document.querySelector('.gaget-cart-shell');
                    if (shell) {
                        shell.classList.add('is-closing');
                        clearTimeout(this._cartCloseTimer);
                        this._cartCloseTimer = setTimeout(() => {
                            this.cartOpen = false;
                            shell.classList.remove('is-closing');
                        }, 300);
                        return;
                    }
                    this.cartOpen = false;
                },
                updateQty(i, d) {
                    const next = this.cart.map((item) => ({ ...item }));
                    const row = next[i];
                    if (!row) return;
                    const stockCap = Number(row.stock);
                    let qty = (Number(row.qty) || 0) + d;
                    if (qty <= 0) {
                        this.cart = next.filter((_, idx) => idx !== i);
                        this.save();
                        this.syncCart({ silent: true });
                        return;
                    }
                    if (Number.isFinite(stockCap) && stockCap >= 0 && qty > stockCap) {
                        qty = stockCap;
                        this.flashToast('Only ' + stockCap + ' available.');
                    }
                    row.qty = qty;
                    this.cart = next;
                    this.save();
                    clearTimeout(this._qtySyncTimer);
                    this._qtySyncTimer = setTimeout(() => this.syncCart({ silent: true }), 350);
                },
                removeItem(i) {
                    this.cart = this.cart.filter((_, idx) => idx !== i);
                    this.save();
                },
                inWishlist(id) { return this.wishlist.some((i) => Number(i.id) === Number(id)); },
                toggleWishlist(product) {
                    const idx = this.wishlist.findIndex((i) => Number(i.id) === Number(product.id));
                    if (idx >= 0) this.wishlist.splice(idx, 1);
                    else this.wishlist.push(product);
                    this.saveWishlist();
                },
                flashToast(message) {
                    this.toastMessage = message;
                    this.toastVisible = true;
                    clearTimeout(this._toastTimer);
                    this._toastTimer = setTimeout(() => { this.toastVisible = false; }, 2200);
                },
                prefillCheckout(profile) {
                    this.checkout.name = profile?.name || '';
                    this.checkout.phone = profile?.phone || '';
                    this.checkout.address = profile?.address || '';
                },
                startCheckout() {
                    if (this.cart.length === 0) return;
                    this.cartOpen = false;
                    this.checkoutOpen = true;
                    this.authPurpose = 'checkout';
                    this.orderMessage = '';
                    this.orderSuccess = false;
                    this.authMessage = '';
                    this.authMessageOk = false;
                    const cfg = this.deliveryConfig || {};
                    if (!cfg.cod_enabled && cfg.confirmation_enabled && Number(cfg.confirmation_amount) > 0) {
                        this.checkout.payment_method = 'confirmation_charge';
                    } else if (cfg.cod_enabled) {
                        this.checkout.payment_method = 'cash_on_delivery';
                    }
                    this.syncCart({ silent: true });
                    if (this.isLoggedIn) {
                        this.checkoutStep = 'order';
                    } else {
                        this.checkoutStep = 'auth';
                        this.authTab = 'login';
                    }
                },
                openSignIn(tab = 'login') {
                    this.cartOpen = false;
                    this.checkoutOpen = true;
                    this.authPurpose = 'account';
                    this.checkoutStep = 'auth';
                    this.authTab = tab === 'register' ? 'register' : 'login';
                    this.authMessage = '';
                    this.authMessageOk = false;
                    this.orderMessage = '';
                    this.orderSuccess = false;
                },
                afterAuthSuccess(user) {
                    this.isLoggedIn = true;
                    this.authMessageOk = false;
                    this.prefillCheckout(user);
                    if (this.authPurpose === 'checkout') {
                        this.checkoutStep = 'order';
                        if (window.GagetLoader) window.GagetLoader.hide();
                        return;
                    }
                    // Return to the same page they signed in from
                    if (window.GagetLoader) window.GagetLoader.show('Welcome back');
                    window.location.reload();
                },
                async submitLogin() {
                    this.authLoading = true;
                    this.authMessage = '';
                    this.authMessageOk = false;
                    if (window.GagetLoader) window.GagetLoader.show('Signing you in');
                    try {
                        if (typeof window.refreshCsrfToken === 'function') {
                            await window.refreshCsrfToken();
                        }
                        const res = await this.csrfFetch(@json(route('website.account.login')), {
                            method: 'POST',
                            body: JSON.stringify(this.authLogin),
                        });
                        const data = await res.json().catch(() => ({}));
                        this.applyCsrfFromResponse(data);
                        if (!res.ok) {
                            this.authMessage = data.message || data.errors?.email?.[0] || (res.status === 419 ? 'Session expired. Please try signing in again.' : 'Sign in failed.');
                            if (window.GagetLoader) window.GagetLoader.hide();
                            return;
                        }
                        this.afterAuthSuccess(data.user);
                    } catch (e) {
                        this.authMessage = 'Network error. Please try again.';
                        if (window.GagetLoader) window.GagetLoader.hide();
                    } finally {
                        this.authLoading = false;
                    }
                },
                async submitRegister() {
                    if (!this.authRegister.name || !this.authRegister.phone || !this.authRegister.email || !this.authRegister.password) {
                        this.authMessage = 'Please fill name, phone, email, and password.';
                        this.authMessageOk = false;
                        return;
                    }
                    this.authLoading = true;
                    this.authMessage = '';
                    this.authMessageOk = false;
                    if (window.GagetLoader) window.GagetLoader.show('Creating your account');
                    try {
                        if (typeof window.refreshCsrfToken === 'function') {
                            await window.refreshCsrfToken();
                        }
                        const res = await this.csrfFetch(@json(route('website.account.register')), {
                            method: 'POST',
                            body: JSON.stringify(this.authRegister),
                        });
                        const data = await res.json().catch(() => ({}));
                        this.applyCsrfFromResponse(data);
                        if (!res.ok) {
                            const err = data.errors || {};
                            this.authMessage = err.name?.[0] || err.email?.[0] || err.phone?.[0] || err.password?.[0] || data.message || (res.status === 419 ? 'Session expired. Please try again.' : 'Registration failed.');
                            return;
                        }
                        // Registration only creates the account — user must sign in to order.
                        this.isLoggedIn = false;
                        this.authLogin.email = data.email || this.authRegister.email || '';
                        this.authLogin.password = '';
                        this.authRegister = { name: '', phone: '', email: '', password: '', address: '' };
                        this.authTab = 'login';
                        this.authMessageOk = true;
                        this.authMessage = data.message || 'Account created. Please sign in to continue.';
                    } catch (e) {
                        this.authMessage = 'Network error. Please try again.';
                    } finally {
                        this.authLoading = false;
                        if (window.GagetLoader) window.GagetLoader.hide();
                    }
                },
                async placeOrder() {
                    const name = String(this.checkout.name || '').trim();
                    const phone = String(this.checkout.phone || '').trim();
                    const address = String(this.checkout.address || '').trim().replace(/\s+/g, ' ');
                    this.checkout.name = name;
                    this.checkout.phone = phone;
                    this.checkout.address = address;

                    if (!name || !phone) {
                        this.orderMessage = 'Full name and phone number are required.';
                        this.orderSuccess = false;
                        return;
                    }
                    if (!address) {
                        this.orderMessage = 'Delivery address is required.';
                        this.orderSuccess = false;
                        return;
                    }
                    if (!this.isLoggedIn) {
                        this.checkoutStep = 'auth';
                        this.authTab = 'login';
                        return;
                    }
                    this.ordering = true;
                    this.orderMessage = '';
                    if (window.GagetLoader) window.GagetLoader.show('Placing your order');
                    try {
                        if (typeof window.refreshCsrfToken === 'function') {
                            await window.refreshCsrfToken();
                        }
                        const res = await this.csrfFetch(@json(route('website.checkout')), {
                            method: 'POST',
                            body: JSON.stringify({
                                cart: this.cart,
                                customer_name: name,
                                customer_phone: phone,
                                customer_address: address,
                                delivery_zone: this.checkout.zone,
                                payment_method: this.deliveryQuote.paymentMethod,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        this.applyCsrfFromResponse(data);
                        if (res.status === 419) {
                            this.orderSuccess = false;
                            this.orderMessage = 'Session expired. Please try placing the order again.';
                            return;
                        }
                        if (res.status === 401 || data.auth_required) {
                            this.isLoggedIn = false;
                            this.checkoutStep = 'auth';
                            this.authTab = 'login';
                            this.authMessage = 'Please sign in to place your order.';
                            return;
                        }
                        if (res.status === 422) {
                            this.orderSuccess = false;
                            this.orderMessage = data.errors?.customer_address?.[0]
                                || data.errors?.customer_phone?.[0]
                                || data.errors?.customer_name?.[0]
                                || data.message
                                || 'Please fill in a complete delivery address.';
                            return;
                        }
                        if (data.success) {
                            if (!data.invoice && !data.order_id) {
                                this.orderSuccess = false;
                                this.orderMessage = 'Order placed but no Order ID was returned. Please check My Orders or contact support.';
                                return;
                            }
                            this.cart = [];
                            this.save();
                            this.orderSuccess = true;
                            this.lastOrderId = data.order_id || null;
                            this.lastOrderInvoice = data.invoice || '';
                            this.orderMessage = '';
                            this.checkoutStep = 'success';
                            this.startAccountRedirect();
                        } else {
                            this.orderSuccess = false;
                            this.orderMessage = data.message || 'Order failed.';
                        }
                    } catch (e) {
                        this.orderSuccess = false;
                        this.orderMessage = 'Network error.';
                    }
                    this.ordering = false;
                    if (window.GagetLoader) window.GagetLoader.hide();
                },
                startAccountRedirect() {
                    if (this._redirectTimer) clearInterval(this._redirectTimer);
                    this.redirectSeconds = 3;
                    const accountUrl = @json(route('website.account'));
                    const go = () => {
                        if (this._redirectTimer) clearInterval(this._redirectTimer);
                        if (window.GagetLoader) window.GagetLoader.show('Opening your orders');
                        const params = new URLSearchParams({ placed: '1' });
                        if (this.lastOrderInvoice) params.set('order', this.lastOrderInvoice);
                        else if (this.lastOrderId) params.set('oid', String(this.lastOrderId));
                        window.location.href = accountUrl + '?' + params.toString() + '#recent-orders';
                    };
                    this._redirectTimer = setInterval(() => {
                        this.redirectSeconds -= 1;
                        if (this.redirectSeconds <= 0) go();
                    }, 1000);
                },
                goToAccountNow() {
                    if (this._redirectTimer) clearInterval(this._redirectTimer);
                    this.redirectSeconds = 0;
                    if (window.GagetLoader) window.GagetLoader.show('Opening your orders');
                    const accountUrl = @json(route('website.account'));
                    const params = new URLSearchParams({ placed: '1' });
                    if (this.lastOrderInvoice) params.set('order', this.lastOrderInvoice);
                    else if (this.lastOrderId) params.set('oid', String(this.lastOrderId));
                    window.location.href = accountUrl + '?' + params.toString() + '#recent-orders';
                },
                jsonHeaders() {
                    return {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (window.currentCsrfToken && window.currentCsrfToken())
                            || document.querySelector('meta[name=csrf-token]')?.content
                            || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                },
                async csrfFetch(url, options = {}) {
                    if (typeof window.fetchJsonWithCsrf === 'function') {
                        return window.fetchJsonWithCsrf(url, options);
                    }
                    return fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.jsonHeaders(), ...(options.headers || {}) } });
                },
                applyCsrfFromResponse(data) {
                    if (data?.csrf_token && typeof window.syncCsrfToken === 'function') {
                        window.syncCsrfToken(data.csrf_token);
                    }
                },
            };
        }
        window.storefrontCart = storefrontCart;
        window.navDropdown = function navDropdown() {
            return {
                open: false,
                _timer: null,
                show() {
                    clearTimeout(this._timer);
                    this.open = true;
                },
                hide() {
                    clearTimeout(this._timer);
                    this._timer = setTimeout(() => { this.open = false; }, 180);
                },
                close() {
                    clearTimeout(this._timer);
                    this.open = false;
                },
                toggle() {
                    clearTimeout(this._timer);
                    this.open = !this.open;
                },
                onFocusOut(event) {
                    if (!this.$el.contains(event.relatedTarget)) {
                        this.hide();
                    }
                },
            };
        };
    </script>
    @vite(['resources/css/app.css', 'resources/css/website.css', 'resources/css/website-mobile.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        /* Critical first-paint loader (before Vite CSS) */
        .gaget-page-loader{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:#fff;opacity:1;visibility:visible;pointer-events:auto;transition:opacity .32s ease,visibility 0s linear 0s}
        .gaget-page-loader.is-hidden{opacity:0;visibility:hidden;pointer-events:none;transition:opacity .32s ease,visibility 0s linear .32s}
        .gaget-page-loader__inner{display:flex;flex-direction:column;align-items:center;gap:14px}
        .gaget-page-loader__mark{position:relative;width:64px;height:64px;display:grid;place-items:center}
        .gaget-page-loader__ring{position:absolute;inset:0;border-radius:50%;border:2.5px solid #e2e8f0;border-top-color:#2563eb;animation:gaget-spin .75s linear infinite}
        .gaget-page-loader__core{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#2563eb,#1d4ed8);color:#fff;font-weight:800;font-size:18px;letter-spacing:-.02em;box-shadow:0 8px 20px rgba(37,99,235,.28)}
        .gaget-page-loader__text{margin:0;font-size:15px;font-weight:800;letter-spacing:.02em;color:#0f172a}
        .gaget-page-loader__sub{margin:0;font-size:12px;font-weight:500;color:#64748b}
        .gaget-page-loader__bar{position:absolute;top:0;left:0;height:2px;width:0;background:linear-gradient(90deg,#2563eb,#38bdf8)}
        .gaget-page-loader.is-active:not(.is-hidden) .gaget-page-loader__bar{animation:gaget-bar-run 1.35s ease-in-out infinite}
        @keyframes gaget-spin{to{transform:rotate(360deg)}}
        @keyframes gaget-bar-run{0%{width:0;left:0}45%{width:55%;left:0}100%{width:0;left:100%}}

        /* Critical cart fly + drawer animations (always available) */
        .gaget-cart-flyer{position:fixed;z-index:100060;width:64px;height:64px;margin:0;padding:0;border:0;border-radius:18px;overflow:hidden;pointer-events:none;background:#fff;box-shadow:0 16px 36px rgba(15,23,42,.28),0 0 0 3px rgba(37,99,235,.25);opacity:1;will-change:left,top,transform,opacity}
        .gaget-cart-flyer img{width:100%;height:100%;object-fit:cover;display:block}
        .gaget-cart-flyer--plain{display:grid;place-items:center;background:linear-gradient(145deg,#3b82f6,#1d4ed8);color:#fff;font-size:22px;font-weight:800}
        .gaget-cart-flyer.is-flying{animation:gaget-fly-cart .9s cubic-bezier(.22,1,.36,1) forwards}
        @keyframes gaget-fly-cart{
            0%{left:var(--fly-x0);top:var(--fly-y0);transform:scale(1) rotate(-8deg);opacity:1}
            55%{left:var(--fly-x1);top:var(--fly-y1);transform:scale(.9) rotate(14deg);opacity:1}
            100%{left:var(--fly-x2);top:var(--fly-y2);transform:scale(.18) rotate(-22deg);opacity:0}
        }
        .gaget-action-btn.is-cart-swing .gaget-cart-icon{animation:gaget-cart-swing .75s cubic-bezier(.34,1.45,.64,1);transform-origin:50% 10%}
        .gaget-action-btn.is-cart-swing .gaget-cart-badge{animation:gaget-badge-pop .55s cubic-bezier(.34,1.45,.64,1)}
        @keyframes gaget-cart-swing{0%{transform:rotate(0)}20%{transform:rotate(-22deg) scale(1.12)}45%{transform:rotate(16deg) scale(1.08)}70%{transform:rotate(-10deg)}100%{transform:rotate(0) scale(1)}}
        @keyframes gaget-badge-pop{0%{transform:scale(.55)}60%{transform:scale(1.35)}100%{transform:scale(1)}}
        .gaget-cart-shell{position:fixed;inset:0;z-index:120;pointer-events:none}
        .gaget-cart-shell.is-open{pointer-events:auto}
        .gaget-cart-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.42);opacity:0;visibility:hidden;transition:opacity .28s ease,visibility 0s linear .28s}
        .gaget-cart-shell.is-open .gaget-cart-backdrop{opacity:1;visibility:visible;transition:opacity .28s ease,visibility 0s}
        .gaget-cart-panel{position:absolute;top:0;right:0;bottom:0;width:min(100%,400px);background:#fff;display:flex;flex-direction:column;border-radius:0;overflow:hidden;border-left:1px solid #e2e8f0;box-shadow:-18px 0 48px rgba(15,23,42,.12);transform:translate3d(100%,0,0);opacity:1}
        .gaget-cart-shell.is-open .gaget-cart-panel{animation:gaget-cart-sheet-in .38s cubic-bezier(.22,1,.36,1) forwards}
        .gaget-cart-shell.is-closing .gaget-cart-panel{animation:gaget-cart-sheet-out .28s ease forwards}
        .gaget-cart-line{opacity:1!important;transform:none}
        .gaget-cart-panel__foot{opacity:1!important;transform:none}
        .gaget-cart-line__img{width:100%!important;height:100%!important;object-fit:cover!important}
        @keyframes gaget-cart-sheet-in{0%{transform:translate3d(100%,0,0)}100%{transform:translate3d(0,0,0)}}
        @keyframes gaget-cart-sheet-out{0%{transform:translate3d(0,0,0)}100%{transform:translate3d(100%,0,0)}}
        .tn-product-add.is-adding,.gaget-btn-primary.is-adding,[data-add-to-cart].is-adding{animation:gaget-add-press .45s cubic-bezier(.34,1.45,.64,1)}
        @keyframes gaget-add-press{0%{transform:scale(1)}35%{transform:scale(.92)}70%{transform:scale(1.04)}100%{transform:scale(1)}}
        /* Fixed floating pill header — padding creates clearance above hero */
        .gaget-sticky-header{position:fixed!important;top:0;left:0;right:0;width:100%;z-index:80;background:transparent!important;box-shadow:none!important;pointer-events:none;padding:12px 0 18px;box-sizing:border-box;overflow:visible!important}
        .gaget-sticky-header>*{pointer-events:auto}
        .gaget-header-spacer{display:block;width:100%;height:var(--g-header-h,100px);pointer-events:none;background:#f1f5f9}

        /* Safety: brand logos must never render at intrinsic SVG/PNG size */
        .gaget-store .tn-brand-logo{height:44px!important;max-height:44px!important;max-width:120px!important;width:auto!important;object-fit:contain!important}
        .gaget-store .tn-brand-logo-frame{height:52px!important;max-width:140px!important;overflow:hidden!important}
        .gaget-store .pd-brand-logo{height:18px!important;max-height:18px!important;max-width:88px!important;width:auto!important;object-fit:contain!important}
        .tn-footer-credit{border-top:1px solid #e2e8f0;padding:0;text-align:center;background:#eef2f7}
        .tn-footer-credit-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px 16px;padding:14px 0 max(16px,env(safe-area-inset-bottom))}
        .tn-footer-copy{margin:0;font-size:12px;font-weight:500;color:#64748b}
        .powered-by,.powered-by--footer{margin:0;font-size:12px;font-weight:500;letter-spacing:.02em;color:#64748b;text-align:center}
        .powered-by strong,.powered-by--footer strong{color:#0f172a;font-weight:800}
    </style>
</head>
<body class="gaget-store bg-white antialiased" id="storefront-root" x-data="storefrontCart()" :class="{ 'is-nav-open': mobileOpen, 'is-cart-open': cartOpen || checkoutOpen }" @keydown.escape.window="cartOpen && closeCart(); checkoutOpen=false; mobileOpen=false; mobileCatsOpen=false; mobileBrandsOpen=false">

{{-- Simple branded page loader --}}
<div id="gaget-page-loader" class="gaget-page-loader is-active" role="status" aria-live="polite" aria-busy="true" aria-label="Loading">
    <div class="gaget-page-loader__bar" aria-hidden="true"></div>
    <div class="gaget-page-loader__inner">
        <div class="gaget-page-loader__mark" aria-hidden="true">
            <span class="gaget-page-loader__ring"></span>
            <span class="gaget-page-loader__core">M</span>
        </div>
        <p class="gaget-page-loader__text">{{ $settings->store_name ?? config('app.name', 'Maks Gadget') }}</p>
        <p class="gaget-page-loader__sub" id="gaget-loader-msg">Loading</p>
    </div>
</div>

@include('website.partials.header')
<script>
(function () {
    function syncHeaderHeight() {
        var header = document.querySelector('.gaget-sticky-header');
        var spacer = document.getElementById('gaget-header-spacer');
        if (!header) return;
        var h = Math.ceil(header.getBoundingClientRect().height);
        if (h < 40) return;
        document.documentElement.style.setProperty('--g-header-h', h + 'px');
        if (spacer) spacer.style.height = h + 'px';
    }
    syncHeaderHeight();
    window.addEventListener('load', syncHeaderHeight);
    window.addEventListener('resize', syncHeaderHeight);
    if (typeof ResizeObserver !== 'undefined') {
        var header = document.querySelector('.gaget-sticky-header');
        if (header) new ResizeObserver(syncHeaderHeight).observe(header);
    }
})();
</script>

@auth('web')
    <form id="storefront-logout-form" method="POST" action="{{ route('website.account.logout') }}" class="hidden" aria-hidden="true">
        @csrf
    </form>
    <script>
        window.storefrontLogout = function () {
            const form = document.getElementById('storefront-logout-form');
            if (form) form.submit();
        };
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
@endauth

<main>
    @yield('content')
</main>

@include('website.partials.footer')

{{-- Cart drawer --}}
<div class="gaget-cart-shell"
     :class="{ 'is-open': cartOpen }"
     :aria-hidden="cartOpen ? 'false' : 'true'">
    <div class="gaget-cart-backdrop" @click="closeCart()"></div>
    <aside class="gaget-cart-panel" role="dialog" aria-modal="true" aria-label="Shopping cart">
        <div class="gaget-cart-panel__head">
            <div class="gaget-cart-panel__head-text">
                <h3 class="gaget-cart-panel__title">Cart</h3>
                <p class="gaget-cart-panel__count"><span x-text="cartCount"></span> <span x-text="cartCount === 1 ? 'item' : 'items'"></span></p>
            </div>
            <button type="button" class="gaget-cart-panel__close" @click="closeCart()" aria-label="Close cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="gaget-cart-panel__body">
            <template x-if="cart.length === 0">
                <div class="gaget-cart-empty">
                    <div class="gaget-cart-empty__icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="gaget-cart-empty__title">Your cart is empty</p>
                    <p class="gaget-cart-empty__text">Browse the shop and add gadgets you like.</p>
                    <button type="button" class="gaget-btn-primary gaget-cart-empty__cta" @click="closeCart()">Continue shopping</button>
                </div>
            </template>

            <template x-for="(item, i) in cart" :key="item.id">
                <div class="gaget-cart-line" :style="'--line-i:' + i">
                    <div class="gaget-cart-line__media">
                        <img :src="item.image" class="gaget-cart-line__img" alt="" loading="lazy">
                    </div>
                    <div class="gaget-cart-line__meta">
                        <div class="gaget-cart-line__top">
                            <p class="gaget-cart-line__name" x-text="item.name"></p>
                            <button type="button" class="gaget-cart-line__remove" @click="removeItem(i)" aria-label="Remove item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12zM10 11v6M14 11v6"/></svg>
                            </button>
                        </div>
                        <p class="gaget-cart-line__price" x-text="currency + Math.round(Number(item.price) || 0).toLocaleString()"></p>
                        <div class="gaget-cart-line__actions">
                            <div class="gaget-cart-qty">
                                <button type="button" @click="updateQty(i, -1)" aria-label="Decrease quantity">−</button>
                                <span x-text="item.qty"></span>
                                <button type="button"
                                        @click="updateQty(i, 1)"
                                        :disabled="Number(item.stock) > 0 && Number(item.qty) >= Number(item.stock)"
                                        :class="Number(item.stock) > 0 && Number(item.qty) >= Number(item.stock) && 'opacity-40 cursor-not-allowed'"
                                        aria-label="Increase quantity">+</button>
                            </div>
                            <p class="gaget-cart-line__line-total" x-text="currency + Math.round((Number(item.price) || 0) * (Number(item.qty) || 0)).toLocaleString()"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="gaget-cart-panel__foot" x-show="cart.length > 0" x-cloak>
            <div class="gaget-cart-panel__summary">
                <div class="gaget-cart-panel__total">
                    <span>Subtotal</span>
                    <strong x-text="currency + Math.round(Number(cartTotal) || 0).toLocaleString()"></strong>
                </div>
                <p class="gaget-cart-panel__hint">Shipping &amp; taxes calculated at checkout</p>
            </div>
            <button type="button" @click="startCheckout()" class="gaget-cart-panel__checkout">
                Checkout
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </button>
        </div>
    </aside>
</div>

{{-- Checkout: sign in / register / place order (one modal) --}}
<div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/50" @click="checkoutStep !== 'success' && (checkoutOpen=false)"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-md max-h-[92vh] overflow-y-auto p-4 sm:p-6"
         :class="checkoutStep === 'success' && 'overflow-hidden'">
        <button type="button" x-show="checkoutStep !== 'success'" @click="checkoutOpen=false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>

        {{-- Auth step (standalone account OR checkout) --}}
        <div x-show="checkoutStep==='auth'" x-cloak>
            <h3 class="text-xl font-bold text-slate-900 pr-8" x-text="authPurpose === 'checkout' ? 'Sign in to checkout' : (authTab === 'register' ? 'Create your account' : 'Welcome back')"></h3>
            <p class="text-sm text-slate-500 mt-1 mb-5" x-text="authPurpose === 'checkout' ? 'Create an account if needed, then sign in to place your order.' : 'Create an account anytime — then sign in to shop and track orders.'"></p>

            <div class="flex rounded-xl bg-slate-100 p-1 mb-5">
                <button type="button" @click="authTab='login'; authMessage=''; authMessageOk=false" class="flex-1 rounded-lg py-2 text-sm font-semibold transition" :class="authTab==='login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Sign in</button>
                <button type="button" @click="authTab='register'; authMessage=''; authMessageOk=false" class="flex-1 rounded-lg py-2 text-sm font-semibold transition" :class="authTab==='register' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Create account</button>
            </div>

            <div x-show="authTab==='login'" class="space-y-3">
                <input x-model="authLogin.email" type="email" placeholder="Email" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="email">
                <input x-model="authLogin.password" type="password" placeholder="Password" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="current-password" @keydown.enter.prevent="submitLogin()">
                <button type="button" @click="submitLogin()" :disabled="authLoading" class="w-full gaget-btn-primary text-center disabled:opacity-50">
                    <span x-text="authLoading ? 'Signing in...' : (authPurpose === 'checkout' ? 'Sign in & continue' : 'Sign in')"></span>
                </button>
            </div>

            <div x-show="authTab==='register'" class="space-y-3">
                <input x-model="authRegister.name" type="text" placeholder="Full name" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="name">
                <input x-model="authRegister.phone" type="text" placeholder="Phone number" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="tel">
                <input x-model="authRegister.email" type="email" placeholder="Email" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="email">
                <input x-model="authRegister.password" type="password" placeholder="Password (min. 8 characters)" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" autocomplete="new-password">
                <textarea x-model="authRegister.address" placeholder="Address (optional)" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm" rows="2"></textarea>
                <button type="button" @click="submitRegister()" :disabled="authLoading" class="w-full gaget-btn-primary text-center disabled:opacity-50">
                    <span x-text="authLoading ? 'Creating account...' : 'Create account'"></span>
                </button>
            </div>

            <p x-show="authMessage" x-text="authMessage" class="mt-3 text-sm text-center" :class="authMessageOk ? 'text-emerald-600' : 'text-rose-600'"></p>
        </div>

        {{-- Order step --}}
        <div x-show="checkoutStep==='order'" x-cloak>
            <h3 class="text-xl font-bold text-slate-900 pr-8">Place order</h3>
            <p class="text-sm text-slate-500 mt-1 mb-4"><span x-text="cartCount"></span> item(s) · Subtotal <span class="font-semibold text-slate-800" x-text="currency + Math.round(Number(cartTotal) || 0).toLocaleString()"></span></p>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Full name <span class="text-rose-500">*</span></label>
                    <input x-model="checkout.name" type="text" required autocomplete="name" placeholder="Your full name" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Phone number <span class="text-rose-500">*</span></label>
                    <input x-model="checkout.phone" type="tel" required autocomplete="tel" placeholder="01XXXXXXXXX" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Delivery address <span class="text-rose-500">*</span></label>
                    <textarea x-model="checkout.address"
                              required
                              autocomplete="street-address"
                              placeholder="Enter your delivery address"
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm"
                              :class="!String(checkout.address || '').trim() && orderMessage && orderMessage.toLowerCase().includes('address') ? 'border-rose-400 ring-1 ring-rose-200' : ''"
                              rows="3"></textarea>
                    <p class="mt-1 text-[11px] text-slate-500">Required for delivery. Any address format is fine — saved address is used when available.</p>
                </div>

                <div>
                    <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-500">Delivery area</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="checkout.zone='inside_dhaka'"
                                class="rounded-xl border px-3 py-2.5 text-left text-xs font-semibold transition"
                                :class="checkout.zone==='inside_dhaka' ? 'border-orange-500 bg-orange-50 text-slate-900 ring-1 ring-orange-200' : 'border-slate-200 bg-white text-slate-600'">
                            <span class="block">Inside Dhaka</span>
                            <span class="mt-0.5 block text-[11px] font-medium text-slate-500" x-text="currency+Number(deliveryConfig.inside_dhaka||0).toFixed(0)"></span>
                        </button>
                        <button type="button" @click="checkout.zone='outside_dhaka'"
                                class="rounded-xl border px-3 py-2.5 text-left text-xs font-semibold transition"
                                :class="checkout.zone==='outside_dhaka' ? 'border-orange-500 bg-orange-50 text-slate-900 ring-1 ring-orange-200' : 'border-slate-200 bg-white text-slate-600'">
                            <span class="block">Outside Dhaka</span>
                            <span class="mt-0.5 block text-[11px] font-medium text-slate-500" x-text="currency+Number(deliveryConfig.outside_dhaka||0).toFixed(0)"></span>
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-xs space-y-1.5">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-semibold" x-text="currency + Math.round(Number(deliveryQuote.subtotal) || 0).toLocaleString()"></span></div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Delivery (<span x-text="deliveryQuote.zoneLabel"></span>)</span>
                        <span class="font-semibold" x-text="deliveryQuote.isFree ? 'FREE' : (currency + Math.round(Number(deliveryQuote.deliveryFee) || 0).toLocaleString())"></span>
                    </div>
                    <p x-show="deliveryQuote.freeReason" x-text="deliveryQuote.freeReason" class="text-[11px] text-emerald-600 font-medium"></p>
                    <p x-show="!deliveryQuote.isFree && deliveryQuote.deliveryFee > 0" class="text-[11px] text-slate-500">Delivery is paid to the delivery person.</p>
                    <div class="flex justify-between border-t border-slate-200 pt-1.5 text-sm">
                        <span class="font-bold text-slate-800">Total</span>
                        <span class="font-bold text-slate-900" x-text="currency + Math.round(Number(deliveryQuote.grandTotal) || 0).toLocaleString()"></span>
                    </div>
                </div>

                <div x-show="deliveryQuote.codEnabled || deliveryQuote.confirmationEnabled">
                    <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-500">Payment</p>
                    <div class="space-y-2">
                        <label x-show="deliveryQuote.codEnabled" class="flex items-start gap-2.5 rounded-xl border px-3 py-2.5 cursor-pointer"
                               :class="checkout.payment_method==='cash_on_delivery' ? 'border-orange-500 bg-orange-50/70' : 'border-slate-200 bg-white'">
                            <input type="radio" class="mt-0.5" value="cash_on_delivery" x-model="checkout.payment_method">
                            <span>
                                <span class="block text-xs font-bold text-slate-900">Cash on delivery</span>
                                <span class="block text-[11px] text-slate-500">Pay full amount when your order arrives</span>
                            </span>
                        </label>
                        <label x-show="deliveryQuote.confirmationEnabled" class="flex items-start gap-2.5 rounded-xl border px-3 py-2.5 cursor-pointer"
                               :class="checkout.payment_method==='confirmation_charge' ? 'border-orange-500 bg-orange-50/70' : 'border-slate-200 bg-white'">
                            <input type="radio" class="mt-0.5" value="confirmation_charge" x-model="checkout.payment_method">
                            <span>
                                <span class="block text-xs font-bold text-slate-900">Confirmation charge</span>
                                <span class="block text-[11px] text-slate-500">
                                    Pay <span class="font-semibold" x-text="currency+Number(deliveryConfig.confirmation_amount||0).toFixed(0)"></span> now ·
                                    balance <span class="font-semibold" x-text="currency + Math.round(Number(deliveryQuote.amountDueLater) || 0).toLocaleString()"></span> on delivery
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <button type="button" @click="placeOrder()" :disabled="ordering" class="w-full gaget-btn-primary text-center disabled:opacity-50">
                    <span x-text="ordering
                        ? 'Placing order...'
                        : (deliveryQuote.paymentMethod==='confirmation_charge'
                            ? ('Confirm · pay ' + currency + deliveryQuote.confirmationAmount.toFixed(0) + ' now')
                            : 'Confirm order (Cash on delivery)')"></span>
                </button>
            </div>

            <p x-show="orderMessage" x-text="orderMessage" class="mt-3 text-sm text-center" :class="orderSuccess ? 'text-green-600' : 'text-red-600'"></p>
        </div>

        {{-- Success step --}}
        <div x-show="checkoutStep==='success'" x-cloak class="gaget-order-success text-center py-2">
            <div class="gaget-order-success__burst" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span><span></span>
            </div>
            <div class="gaget-order-success__check mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900">Order placed successfully!</h3>
            <p class="mt-2 text-sm text-slate-500">Thank you — your order is confirmed.</p>
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-gradient-to-b from-emerald-50 to-white px-4 py-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">Your Order ID</p>
                <p class="mt-1.5 font-mono text-[17px] font-extrabold tracking-wide text-emerald-950" x-text="lastOrderInvoice || ('#' + lastOrderId)"></p>
                <p class="mt-2 text-[12px] text-emerald-800/80">Keep this ID for tracking and support.</p>
            </div>
            <p class="mt-4 text-[12px] text-slate-500">
                Taking you to <span class="font-semibold text-slate-700">My Orders</span>
                <span x-show="redirectSeconds > 0"> in <span class="font-bold text-blue-600" x-text="redirectSeconds"></span>s…</span>
            </p>
            <div class="mt-5 flex flex-col gap-2">
                <button type="button" @click="goToAccountNow()" class="gaget-btn-primary w-full text-center text-sm py-3">
                    View my order now
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div x-show="toastVisible" x-cloak x-transition.opacity.duration.200ms
     class="fixed bottom-6 left-1/2 z-[90] -translate-x-1/2 rounded-xl bg-slate-900 px-4 py-2.5 text-[13px] font-semibold text-white shadow-lg">
    <span x-text="toastMessage"></span>
</div>

<script>
window.getStorefrontRoot = function () {
    const root = document.getElementById('storefront-root') || document.body;
    if (!window.Alpine || typeof Alpine.$data !== 'function') return null;
    try {
        return Alpine.$data(root);
    } catch (e) {
        return null;
    }
};

window.gagetFlyToCart = function (options) {
    const opts = options || {};
    const product = opts.product || {};
    const sourceEl = opts.sourceEl || null;
    const onDone = typeof opts.onDone === 'function' ? opts.onDone : function () {};
    const cartTarget = document.getElementById('gaget-cart-target')
        || document.querySelector('[data-cart-target]');

    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || !cartTarget) {
        onDone();
        return;
    }

    const fromRect = (sourceEl && sourceEl.getBoundingClientRect)
        ? sourceEl.getBoundingClientRect()
        : { left: window.innerWidth * 0.45, top: window.innerHeight * 0.45, width: 64, height: 64 };
    const toRect = cartTarget.getBoundingClientRect();
    const size = 64;

    const x0 = fromRect.left + (fromRect.width || size) / 2 - size / 2;
    const y0 = fromRect.top + (fromRect.height || size) / 2 - size / 2;
    const x2 = toRect.left + toRect.width / 2 - size / 2;
    const y2 = toRect.top + toRect.height / 2 - size / 2;
    const x1 = x0 + (x2 - x0) * 0.42;
    const y1 = Math.min(y0, y2) - Math.max(100, Math.abs(y2 - y0) * 0.45);

    const flyer = document.createElement('div');
    flyer.className = 'gaget-cart-flyer';
    flyer.setAttribute('aria-hidden', 'true');
    flyer.style.setProperty('--fly-x0', x0 + 'px');
    flyer.style.setProperty('--fly-y0', y0 + 'px');
    flyer.style.setProperty('--fly-x1', x1 + 'px');
    flyer.style.setProperty('--fly-y1', y1 + 'px');
    flyer.style.setProperty('--fly-x2', x2 + 'px');
    flyer.style.setProperty('--fly-y2', y2 + 'px');
    flyer.style.left = x0 + 'px';
    flyer.style.top = y0 + 'px';

    if (product.image) {
        const img = document.createElement('img');
        img.src = product.image;
        img.alt = '';
        flyer.appendChild(img);
    } else {
        flyer.classList.add('gaget-cart-flyer--plain');
        flyer.textContent = '+';
    }

    document.body.appendChild(flyer);

    let done = false;
    const finished = function () {
        if (done) return;
        done = true;
        flyer.remove();
        cartTarget.classList.remove('is-cart-swing');
        void cartTarget.offsetWidth;
        cartTarget.classList.add('is-cart-swing');
        setTimeout(function () {
            cartTarget.classList.remove('is-cart-swing');
        }, 800);
        onDone();
    };

    // Pure CSS keyframe animation (professional arc + swing)
    requestAnimationFrame(function () {
        flyer.classList.add('is-flying');
    });
    flyer.addEventListener('animationend', finished, { once: true });
    setTimeout(finished, 1000); // safety fallback
};

window.addProductToCart = function (product, qty = 1, openDrawer = true, sourceEl = null) {
    const root = window.getStorefrontRoot();
    if (root && typeof root.addToCart === 'function') {
        root.addToCart(product, qty, false);
        if (openDrawer) {
            window.gagetFlyToCart({
                product: product,
                sourceEl: sourceEl,
                onDone: function () {
                    if (typeof root.openCart === 'function') root.openCart();
                    else root.cartOpen = true;
                },
            });
        } else {
            const cartTarget = document.getElementById('gaget-cart-target');
            if (cartTarget) {
                cartTarget.classList.add('is-cart-swing');
                setTimeout(function () { cartTarget.classList.remove('is-cart-swing'); }, 800);
            }
        }
        return true;
    }
    return false;
};

document.addEventListener('click', function (event) {
    const btn = event.target.closest('[data-add-to-cart]');
    if (!btn) return;

    event.preventDefault();
    event.stopPropagation();

    let product;
    try {
        product = JSON.parse(btn.getAttribute('data-add-to-cart'));
    } catch (e) {
        return;
    }

    btn.classList.remove('is-adding');
    void btn.offsetWidth;
    btn.classList.add('is-adding');
    setTimeout(function () { btn.classList.remove('is-adding'); }, 500);

    const qtyAttr = btn.getAttribute('data-qty');
    const qtyInput = document.querySelector('[data-product-qty], #pd-qty, input[name="qty"]');
    const qtyFromInput = qtyInput ? Number(qtyInput.value) : NaN;
    const qty = Math.max(1, Number(qtyAttr || qtyFromInput || 1) || 1);
    const openDrawer = btn.getAttribute('data-open-cart') !== '0';
    const goCheckout = btn.getAttribute('data-checkout') === '1';

    const card = btn.closest('.tn-product-card, .tn-card, .gs-card, .pd-buy, [data-product-live-root], article, .product-card') || btn;
    const img = card.querySelector('.tn-product-card img, .tn-product-media img, .pd-gallery img, .gs-card img, img');
    const sourceEl = img || btn;

    const finishCheckout = function () {
        const root = window.getStorefrontRoot();
        if (root && typeof root.startCheckout === 'function') root.startCheckout();
    };

    if (window.addProductToCart(product, qty, goCheckout ? false : openDrawer, sourceEl)) {
        if (goCheckout) finishCheckout();
        return;
    }

    let tries = 0;
    const timer = setInterval(function () {
        tries += 1;
        if (window.addProductToCart(product, qty, goCheckout ? false : openDrawer, sourceEl)) {
            clearInterval(timer);
            if (goCheckout) finishCheckout();
        } else if (tries > 50) {
            clearInterval(timer);
        }
    }, 40);
});
</script>
<script>
(function () {
    const el = document.getElementById('gaget-page-loader');
    const msgEl = document.getElementById('gaget-loader-msg');
    if (!el) return;

    const messages = [
        'Loading',
        'Just a moment',
        'Almost ready',
    ];
    let shownAt = Date.now();
    let msgTimer = null;
    let msgIndex = 0;
    let hideTimer = null;
    const MIN_MS = 420;

    function setMessage(text) {
        if (msgEl && text) msgEl.textContent = text;
    }

    function startMessages() {
        stopMessages();
        msgIndex = Math.floor(Math.random() * messages.length);
        setMessage(messages[msgIndex]);
        msgTimer = setInterval(function () {
            msgIndex = (msgIndex + 1) % messages.length;
            setMessage(messages[msgIndex]);
        }, 1400);
    }

    function stopMessages() {
        if (msgTimer) {
            clearInterval(msgTimer);
            msgTimer = null;
        }
    }

    function show(message) {
        if (hideTimer) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        shownAt = Date.now();
        el.classList.add('is-active');
        el.classList.remove('is-hidden');
        el.setAttribute('aria-busy', 'true');
        el.removeAttribute('aria-hidden');
        // Force reflow so opacity/visibility transition runs when re-showing
        void el.offsetWidth;
        if (message) setMessage(message);
        else startMessages();
    }

    function hide() {
        const wait = Math.max(0, MIN_MS - (Date.now() - shownAt));
        if (hideTimer) clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            stopMessages();
            el.classList.add('is-hidden');
            el.classList.remove('is-active');
            el.setAttribute('aria-busy', 'false');
            el.setAttribute('aria-hidden', 'true');
            hideTimer = null;
        }, wait);
    }

    window.GagetLoader = { show: show, hide: hide };

    // First paint: hide after page is ready (DOM + assets)
    function onReady() {
        if (document.readyState === 'complete') {
            hide();
            return;
        }
        window.addEventListener('load', hide, { once: true });
        // Safety: never leave the overlay stuck if load is delayed/broken
        setTimeout(function () {
            if (!el.classList.contains('is-hidden')) hide();
        }, 8000);
    }
    onReady();

    // Back/forward cache
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hide();
    });

    function shouldInterceptLink(a, event) {
        if (!a || !a.href) return false;
        if (a.target && a.target !== '_self') return false;
        if (a.hasAttribute('download')) return false;
        if (a.dataset.noLoader !== undefined) return false;
        if (event.defaultPrevented) return false;
        if (event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

        let url;
        try { url = new URL(a.href, window.location.href); }
        catch (e) { return false; }

        if (url.origin !== window.location.origin) return false;
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;
        // Same URL (no navigation)
        if (url.href.split('#')[0] === window.location.href.split('#')[0]) return false;

        return true;
    }

    document.addEventListener('click', function (event) {
        const a = event.target.closest('a[href]');
        if (!shouldInterceptLink(a, event)) return;
        show();
    }, true);

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.noLoader !== undefined) return;
        if (form.target && form.target !== '_self') return;
        // AJAX forms often preventDefault — skip those
        // We show anyway; if submit is cancelled shortly after, pageshow won't fire — use short timeout fallback
        show('Saving your request');
        setTimeout(function () {
            // If still on same page after a short wait (AJAX / validation stay), hide
            if (!el.classList.contains('is-hidden')) hide();
        }, 3500);
    }, true);

    // Soft loader for in-page fetch (checkout / auth) via custom events
    window.addEventListener('gaget:loading', function (e) {
        show((e && e.detail && e.detail.message) || 'Working on it');
    });
    window.addEventListener('gaget:loaded', function () {
        hide();
    });
})();
</script>
@stack('scripts')
</body>
</html>
