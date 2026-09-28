<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\CounterSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerBakiController;
use App\Http\Controllers\CustomerEmiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\OnlineOrderController;
use App\Http\Controllers\OrderCancellationController;
use App\Http\Controllers\OrderInvoiceController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosSettingsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesLedgerController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DamageProductController;
use App\Http\Controllers\OpeningInventoryController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReorderLevelController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockLocationController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StorefrontAuthController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\Cms\LandingPageController;
use App\Http\Controllers\Cms\HomeSlideController;
use App\Http\Controllers\Cms\PageController as CmsPageController;
use App\Http\Controllers\Cms\BlogController as CmsBlogController;
use App\Http\Controllers\Cms\BlogCategoryController as CmsBlogCategoryController;
use App\Http\Controllers\Cms\FaqController as CmsFaqController;
use App\Http\Controllers\Cms\FaqCategoryController as CmsFaqCategoryController;
use App\Http\Controllers\Cms\ContactController as CmsContactController;
use App\Http\Controllers\Cms\DeliverySettingsController;
use App\Http\Controllers\Cms\CourierServiceController;
use App\Http\Controllers\Cms\ReviewController as CmsReviewController;
use App\Http\Controllers\Cms\NavigationController as CmsNavigationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Nexa POS Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [WebsiteController::class, 'home'])->name('home');
Route::get('/favicon.ico', function () {
    return redirect()->to(site_favicon_url(), 302);
})->name('favicon');
/*
| Customer auth entry — storefront uses a modal, so /login opens home + sign-in.
| Staff login lives at /admin/login (route: admin.login). Never collide names.
*/
Route::get('/login', function () {
    return redirect()->route('home', ['signin' => 1]);
})->name('login');

Route::get('/shop', [WebsiteController::class, 'shop'])->name('website.shop');
Route::post('/cart/sync', [WebsiteController::class, 'syncCart'])->name('website.cart.sync');
Route::get('/search/suggest', [WebsiteController::class, 'searchSuggest'])->name('website.search.suggest');
Route::get('/category/{slug}', [WebsiteController::class, 'category'])->name('website.category');
Route::get('/brand/{slug}', [WebsiteController::class, 'brand'])->name('website.brand');
Route::get('/product/{product}', [WebsiteController::class, 'product'])->name('website.product');
Route::get('/track-order', [WebsiteController::class, 'trackOrder'])->name('website.track');
Route::post('/track-order', [WebsiteController::class, 'trackOrderLookup'])->name('website.track.lookup');

/*
| Public CSRF refresh — storefront customers + guests need this after login / tab sleep.
| Staff also have /refresh-session inside the admin group.
*/
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('csrf.token');

Route::post('/account/login', [StorefrontAuthController::class, 'login'])->name('website.account.login');
Route::post('/account/register', [StorefrontAuthController::class, 'register'])->name('website.account.register');
Route::post('/account/logout', [StorefrontAuthController::class, 'logout'])->name('website.account.logout');
Route::middleware('auth:web')->group(function () {
    Route::get('/account', [StorefrontAuthController::class, 'account'])->name('website.account');
    Route::get('/account/profile', [StorefrontAuthController::class, 'editProfile'])->name('website.account.profile.edit');
    Route::put('/account/profile', [StorefrontAuthController::class, 'updateProfile'])->name('website.account.profile.update');
    Route::delete('/account/profile', [StorefrontAuthController::class, 'destroyAccount'])->name('website.account.profile.destroy');
    Route::post('/checkout', [WebsiteController::class, 'checkout'])->name('website.checkout');
});
Route::get('/page/{slug}', [WebsiteController::class, 'page'])->name('website.page');
Route::get('/blog', [WebsiteController::class, 'blogs'])->name('website.blogs');
Route::get('/blog/{slug}', [WebsiteController::class, 'blog'])->name('website.blog');
Route::post('/newsletter/subscribe', [WebsiteController::class, 'subscribeNewsletter'])->name('website.newsletter');
Route::get('/faq', [WebsiteController::class, 'faqs'])->name('website.faqs');
Route::get('/contact', [WebsiteController::class, 'contact'])->name('website.contact');
Route::post('/contact', [WebsiteController::class, 'submitContact'])->name('website.contact.submit');
Route::get('/wishlist', [WebsiteController::class, 'wishlist'])->name('website.wishlist');

Route::middleware([
    'auth:admin',
    'verified',
    \App\Http\Middleware\EnsureNotStorefrontCustomer::class,
    \App\Http\Middleware\CheckIfSuspended::class,
    \App\Http\Middleware\EnsureStaffOpeningBalance::class,
])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/refresh-session', function () {
        return response()->json([
            'status' => 'Nexa POS Active',
            'csrf_token' => csrf_token(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    })->name('session.refresh');

    Route::prefix('cms')->name('cms.')->middleware('can:manage website')->group(function () {
        Route::get('/landing', [LandingPageController::class, 'edit'])->name('landing.edit');
        Route::put('/landing', [LandingPageController::class, 'update'])->name('landing.update');
        Route::get('/delivery', [DeliverySettingsController::class, 'edit'])->name('delivery.edit');
        Route::put('/delivery', [DeliverySettingsController::class, 'update'])->name('delivery.update');
        Route::resource('couriers', CourierServiceController::class)->except(['show', 'create', 'edit']);
        Route::resource('slides', HomeSlideController::class)->except(['show']);
        Route::resource('pages', CmsPageController::class)->except(['show']);
        Route::put('/blogs-settings', [CmsBlogController::class, 'updateSettings'])->name('blogs.settings');
        Route::post('/blogs-settings', [CmsBlogController::class, 'updateSettings']);
        Route::resource('blogs', CmsBlogController::class)->except(['show']);
        Route::resource('blog-categories', CmsBlogCategoryController::class)->except(['show', 'create', 'edit']);
        Route::post('/faqs-settings', [CmsFaqController::class, 'updateSettings'])->name('faqs.settings');
        Route::resource('faqs', CmsFaqController::class)->except(['show']);
        Route::resource('faq-categories', CmsFaqCategoryController::class)->except(['show', 'create', 'edit']);
        Route::get('/contact', [CmsContactController::class, 'index'])->name('contact.index');
        Route::post('/contact-settings', [CmsContactController::class, 'updateSettings'])->name('contact.settings');
        Route::get('/contact/messages/{message}', [CmsContactController::class, 'showMessage'])->name('contact.messages.show');
        Route::post('/contact/messages/{message}/read', [CmsContactController::class, 'markRead'])->name('contact.messages.read');
        Route::delete('/contact/messages/{message}', [CmsContactController::class, 'destroyMessage'])->name('contact.messages.destroy');
        Route::resource('reviews', CmsReviewController::class)->except(['show']);
        Route::resource('navigation', CmsNavigationController::class)->except(['show', 'create', 'edit']);
    });

    Route::get('/global-search', GlobalSearchController::class)->name('global-search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/orders/{order}/invoice', [OrderInvoiceController::class, 'show'])->name('orders.invoice');

    /*
    | Legacy physical-retail module (config/modules.php → retail.enabled).
    | Returns 404 while disabled; route names stay registered so shared views never break.
    */
    Route::middleware('module:retail')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.checkout');
        Route::get('/pos/receipt/{order}', [OrderInvoiceController::class, 'show'])->name('pos.receipt');
        Route::get('/pos/customer-lookup', [PosController::class, 'lookupCustomer'])->name('pos.customer-lookup');
        Route::post('/pos/sync-offline', [PosController::class, 'syncOffline'])->name('pos.sync');
        Route::get('/pos/settings', [PosSettingsController::class, 'edit'])->name('pos.settings.edit');
        Route::put('/pos/settings', [PosSettingsController::class, 'update'])->name('pos.settings.update');

        Route::get('/customers-baki', [CustomerBakiController::class, 'index'])->name('customers.baki.index');
        Route::get('/customers/baki/entries/{entry}/slip', [CustomerBakiController::class, 'slip'])->name('customers.baki.slip');
        Route::get('/customers/{customer}/baki', [CustomerBakiController::class, 'show'])->name('customers.baki.show');
        Route::post('/customers/{customer}/baki/pay', [CustomerBakiController::class, 'pay'])->name('customers.baki.pay');

        Route::get('/customers-emi', [CustomerEmiController::class, 'index'])->name('customers.emi.index');
        Route::get('/customers/emi/entries/{entry}/slip', [CustomerEmiController::class, 'slip'])->name('customers.emi.slip');
        Route::get('/customers/emi/{plan}', [CustomerEmiController::class, 'show'])->name('customers.emi.show');
        Route::post('/customers/emi/{plan}/pay', [CustomerEmiController::class, 'pay'])->name('customers.emi.pay');
        Route::get('/customers/{customer}/emi', [CustomerEmiController::class, 'customer'])->name('customers.emi.customer');

        Route::middleware('can:view sales ledger')->group(function () {
            Route::get('/sales-ledger', [SalesLedgerController::class, 'index'])->name('sales.index');
            Route::post('/sales/{order}/refund', [SalesLedgerController::class, 'refund'])->name('sales.refund');
            Route::post('/sales/{order}/return', [SalesLedgerController::class, 'markReturned'])->name('sales.return');
        });

        Route::get('/reports/staff-performance', [ReportController::class, 'staffPerformance'])->name('reports.staff_performance');
        Route::get('/reports/staff-daily-details', [ReportController::class, 'staffDailyDetails'])->name('reports.staff_daily_details');
        Route::post('/orders/{order}/exchange', [ExchangeController::class, 'processExchange'])->name('orders.exchange');

        Route::resource('counters', CounterController::class)->except(['create', 'show', 'edit']);

        Route::get('/counter-sessions', [CounterSessionController::class, 'index'])->name('counters.sessions.index');
        Route::get('/opening-balance', [CounterSessionController::class, 'openTodayForm'])->name('counters.sessions.open-today');
        Route::post('/opening-balance', [CounterSessionController::class, 'openTodayStore'])->name('counters.sessions.open-today.store');
        Route::post('/counter-sessions/open', [CounterSessionController::class, 'open'])->name('counters.sessions.open');
        Route::post('/counter-sessions/transfer', [CounterSessionController::class, 'transfer'])->name('counters.sessions.transfer');
        Route::get('/counter-sessions/{session}', [CounterSessionController::class, 'show'])->name('counters.sessions.show');
        Route::get('/counter-sessions/{session}/close', [CounterSessionController::class, 'closeForm'])->name('counters.sessions.close-form');
        Route::post('/counter-sessions/{session}/close', [CounterSessionController::class, 'close'])->name('counters.sessions.close');
    });

    Route::resource('customers', CustomerController::class);

    Route::middleware('can:manage inventory')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('brands', BrandController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('/products/{product}/sale', [ProductController::class, 'applySale'])->name('products.sale');
        Route::delete('/products/{product}/sale', [ProductController::class, 'clearSale'])->name('products.sale.clear');
        Route::patch('/products/{product}/homepage-flags', [ProductController::class, 'toggleHomepageFlag'])->name('products.homepage-flags');
        Route::post('/products-brand-sale', [ProductController::class, 'applyBrandSale'])->name('products.brand-sale');
        Route::delete('/products-brand-sale', [ProductController::class, 'clearBrandSale'])->name('products.brand-sale.clear');
        Route::get('/products-import/csv', [ProductController::class, 'importForm'])->name('products.import');
        Route::get('/products-import/csv/template', [ProductController::class, 'importTemplate'])->name('products.import.template');
        Route::post('/products-import/csv', [ProductController::class, 'importStore'])->name('products.import.store');
        Route::get('/products-barcodes', [ProductController::class, 'barcodes'])->name('products.barcodes');
        Route::get('/products-barcodes/print', [ProductController::class, 'barcodesPrint'])->name('products.barcodes.print');
        Route::get('/stock-ledger', fn () => redirect()->route('supply.adjustments.index'))->name('stock.index');
        Route::post('/stock-ledger', [StockAdjustmentController::class, 'store'])->name('stock.store');
    });

    Route::prefix('supply')->name('supply.')->middleware('can:manage inventory')->group(function () {
        Route::get('/opening-inventory', [OpeningInventoryController::class, 'index'])->name('opening-inventory.index');
        Route::post('/opening-inventory', [OpeningInventoryController::class, 'store'])->name('opening-inventory.store');
        Route::get('/reorder-levels', [ReorderLevelController::class, 'index'])->name('reorder-levels.index');
        Route::put('/reorder-levels', [ReorderLevelController::class, 'update'])->name('reorder-levels.update');
        Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::get('/damage-products', [DamageProductController::class, 'index'])->name('damage-products.index');
        Route::post('/damage-products', [DamageProductController::class, 'store'])->name('damage-products.store');
        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::get('/stores', [StockLocationController::class, 'stores'])->name('stores.index');
        Route::get('/stores/create', [StockLocationController::class, 'storeCreate'])->name('stores.create');
        Route::post('/stores', [StockLocationController::class, 'storeSave'])->name('stores.store');
        Route::get('/stores/{location}/edit', [StockLocationController::class, 'storeEdit'])->name('stores.edit');
        Route::put('/stores/{location}', [StockLocationController::class, 'storeUpdate'])->name('stores.update');
        Route::get('/warehouses', [StockLocationController::class, 'warehouses'])->name('warehouses.index');
        Route::get('/warehouses/create', [StockLocationController::class, 'warehouseCreate'])->name('warehouses.create');
        Route::post('/warehouses', [StockLocationController::class, 'warehouseSave'])->name('warehouses.store');
        Route::get('/warehouses/{location}/edit', [StockLocationController::class, 'warehouseEdit'])->name('warehouses.edit');
        Route::put('/warehouses/{location}', [StockLocationController::class, 'warehouseUpdate'])->name('warehouses.update');
        Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::post('/purchase-orders/{purchaseOrder}/pay', [PurchaseOrderController::class, 'pay'])->name('purchase-orders.pay');
        Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
        Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    });

    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/opening-balance', [AccountController::class, 'openingBalance'])->name('opening-balance');
        Route::post('/opening-balance', [AccountController::class, 'updateOpeningBalance'])->name('opening-balance.update');
        Route::get('/chart', [AccountController::class, 'chart'])->name('chart');
        Route::post('/chart', [AccountController::class, 'storeAccount'])->name('chart.store');
        Route::get('/ledger', [AccountController::class, 'ledger'])->name('ledger');
        Route::get('/cash-book', [AccountController::class, 'cashBook'])->name('cash-book');
        Route::get('/daily-summary', [AccountController::class, 'dailySummary'])->name('daily-summary');
        Route::get('/petty-cash', [AccountController::class, 'pettyCashForm'])->name('petty-cash');
        Route::post('/petty-cash', [AccountController::class, 'pettyCashStore'])->name('petty-cash.store');
        Route::get('/transfer', [AccountController::class, 'transferForm'])->name('transfer');
        Route::post('/transfer', [AccountController::class, 'transferStore'])->name('transfer.store');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
        Route::get('/orders', [AnalyticsController::class, 'orders'])->name('orders');
        Route::get('/products', [AnalyticsController::class, 'products'])->name('products');
        Route::get('/customers', [AnalyticsController::class, 'customers'])->name('customers');
        Route::get('/stock', [AnalyticsController::class, 'inventory'])->name('stock');
        Route::get('/tax', [AnalyticsController::class, 'tax'])->name('tax');
        Route::get('/discount', [AnalyticsController::class, 'discount'])->name('discount');
        Route::get('/preview', [AnalyticsController::class, 'preview'])->name('preview');
        Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
        // Legacy aliases
        Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
        Route::get('/expense', [AnalyticsController::class, 'expense'])->name('expense');
        Route::get('/inventory', [AnalyticsController::class, 'inventory'])->name('inventory');
        Route::get('/balance', [AnalyticsController::class, 'balance'])->name('balance');
    });

    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales'])->name('reports.daily');
    Route::get('/reports/daily-sales-by-brand', [ReportController::class, 'dailySalesByBrand'])->name('reports.daily_by_brand');
    Route::get('/reports/best-sellers', [ReportController::class, 'bestSellers'])->name('reports.best_sellers');
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])
        ->middleware('can:manage inventory')
        ->name('reports.low_stock');

    Route::middleware('can:manage roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::middleware('can:manage staff')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{staff}/toggle-suspend', [StaffController::class, 'toggleSuspend'])->name('staff.toggle-suspend');
    });

    Route::get('/online-orders', [OnlineOrderController::class, 'index'])->name('online-orders.index');
    Route::get('/online-orders/notifications', [OnlineOrderController::class, 'notifications'])->name('online-orders.notifications');
    Route::post('/online-orders/notifications/seen', [OnlineOrderController::class, 'markNotificationsSeen'])->name('online-orders.notifications.seen');
    Route::get('/online-orders/{order}', [OnlineOrderController::class, 'show'])->name('online-orders.show');
    Route::post('/online-orders/{order}/status', [OnlineOrderController::class, 'updateStatus'])->name('online-orders.update-status');
    Route::post('/online-orders/{order}/cancel', [OrderCancellationController::class, 'cancel'])->name('online-orders.cancel');
    Route::post('/online-orders/{order}/collect-from-courier', [OnlineOrderController::class, 'collectFromCourier'])->name('online-orders.collect-from-courier');
});

require __DIR__.'/auth.php';
