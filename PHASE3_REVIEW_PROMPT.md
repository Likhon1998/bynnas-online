# Bynnas Social — Phase 3 Review Pack

Hand this whole file to the reviewing AI. Part A is the prompt, Part B is the implementation report it must verify.

---

## PART A — PROMPT FOR THE REVIEWING AI

You are a senior Laravel reviewer. You have read access to the repository at `C:\xampp\htdocs\Bynnas Social` (Laravel 12, PHP 8.2, MySQL, Blade + Alpine.js + Tailwind, Vite, spatie/laravel-permission).

The project is **Bynnas Social**, an online-first social-commerce platform being converted from an older retail/POS system in phases. Phases 1–2 (audit, isolating retail/POS behind `RETAIL_MODULE_ENABLED=false`) are done. **Phase 3 — Generic Product Architecture** has just been implemented. Part B below is the developer's report.

### Your job

1. Verify that every Phase 3 requirement (section B2) is actually implemented in the code. Do not trust the report — open the files and confirm.
2. Verify that the project constraints (section B3) were respected.
3. Run the verification commands (section B6) and report results.
4. Look for bugs, security gaps (missing permission or shop-scope checks), N+1 queries, broken legacy behaviour, and anything in the report that is not true.

### Rules

- **Do not modify code, do not commit, do not push.** This is a review only.
- Do not invent files, routes or features. If something is claimed but you cannot find it, say so.
- Ignore the 11 known pre-existing Breeze test failures listed in B6 unless your analysis shows Phase 3 caused them.

### Output format

1. **Verdict:** PASS / PASS WITH ISSUES / FAIL, with a one-paragraph summary.
2. **Requirement checklist:** for each item in B2, one of PASS / PARTIAL / FAIL, with file path + line evidence.
3. **Constraint checklist:** same, for each item in B3.
4. **Issues found:** ordered by severity (Critical / High / Medium / Low). For each: what, where (file:line), why it matters, suggested fix.
5. **Test results:** what you ran and the outcome.
6. **Recommendations before Phase 4** (Campaigns and social attribution): anything in the product layer that should change first.

---

## PART B — PHASE 3 IMPLEMENTATION REPORT

### B1. Context

- **Stack:** Laravel 12, PHP 8.2, MySQL (XAMPP), Blade, Alpine.js, Tailwind, Vite, spatie/laravel-permission.
- **Auth:**
  - Staff log in on the `admin` guard; permissions live on the `web` guard (`User::getDefaultGuardName`).
  - Admin route middleware: `auth:admin`, `verified`, `EnsureNotStorefrontCustomer`, `CheckIfSuspended`, `EnsureStaffOpeningBalance`.
- **Single store:** `Shop` throws "Only one store is allowed" on a second create. Data is still scoped by `shop_id`.
- **Retail module:** POS, counters, BAKI, EMI and IMEI are hidden unless `RETAIL_MODULE_ENABLED=true` (helper `retail_enabled()`).
- **Permission failures:** browser requests get a redirect back with an `error` flash; JSON requests get 403. This is set in `bootstrap/app.php`.
- **Product model design (deliberate):**
  - Each `products` row is one sellable variant (own barcode, stock, price).
  - A "product family" is the set of rows sharing `variant_group`.
  - This was kept rather than adding a separate parent/variant table, because orders, stock movements, warehouse stock, purchase orders and IMEIs all reference `product_id`. Splitting would have created a second inventory/order path, which the constraints forbid.

### B2. Phase 3 requirements and how each was implemented

| # | Requirement | Implementation (verify these) |
|---|-------------|-------------------------------|
| R1 | IMEI, RAM, Storage, Colour and Barcode must be optional, not phone-specific | `products.barcode` made nullable (migration). A blank barcode gets an auto code `BS…` (`ProductVariantService::generateCode`). IMEI inputs only show and apply when `retail_enabled()`. RAM, Storage and Colour are now ordinary attributes. |
| R2 | Product + Variant + Attribute architecture | New tables `product_attributes` (per shop; name, slug, type, is_filterable, sort), `product_attribute_values` (value, slug, color_hex, sort) and `product_variant_values` (product ↔ attribute ↔ value; unique per product + attribute). Models: `ProductAttribute`, `ProductAttributeValue`, `ProductVariantValue`. `ProductAttribute::ensureDefaults()` seeds Color, Size, Storage, RAM, Material and Weight for a shop that has none. |
| R3 | Admin can create variable products (e.g. T-shirt in Colour × Size) | `ProductController::store`: `product_mode=variable` with `variants[i][options][attributeId][value|hex]`. Creates one `products` row per variant sharing `variant_group` (slug of the name). Rejects duplicate option combinations and duplicate barcodes. Per-variant price, stock, barcode, SKU and images. `update` syncs attribute values for a single row. Form: `resources/views/products/partials/form-fields.blade.php`. |
| R4 | Attribute management UI | `ProductAttributeController` + `resources/views/attributes/index.blade.php`: create, rename and delete attributes; add, rename and delete values; colour hex. Deleting a value or attribute that products use is **blocked**. Renaming a value re-mirrors legacy columns on affected products. Other shop's records → 404. |
| R5 | Variant overview | `ProductFamilyController@index` + `resources/views/products/variants.blade.php`: families with variant count, stock, reserved, price range and variant table. |
| R6 | Legacy compatibility | Legacy `products.color`, `color_hex`, `storage` and `ram` columns are **kept** and mirrored from attribute values (`ProductVariantService::mirrorLegacyColumns`), so POS, receipts, IMEI and reports keep working. The migration backfills attribute values from these columns. Storage/RAM use a compact canonical form (`256GB`). Old storefront links `storage[]=` / `ram[]=` still filter. |
| R7 | SEO / Open Graph fields | Products gained `slug`, `description`, `seo_title`, `meta_description`, `og_title`, `og_description` and `og_image`. Slug is auto-generated and unique per shop (`Product::booted`, `uniqueSlug`). `Product::seoTitle()` and `seoDescription()` have fallbacks. The product page uses them in `<title>`, meta and `og:` tags. `/product/{slug}` works and numeric `/product/{id}` still works (`resolveRouteBinding`). |
| R8 | Storefront shows generic variants | `ProductVariantService::storefrontOptions()` builds a picker grouped by attribute, with availability (sold-out options marked). Generic attribute filters on `/shop` and category pages: `?attr[color][]=black&attr[size][]=m` (OR within an attribute, AND across). Code: `WebsiteService::selectedAttributeFilters`, `applyAttributeFilters`, `attributeFacets`; `WebsiteController`; `resources/views/website/partials/shop-sidebar.blade.php`. Category filter config supports type `attribute` (`CategoryFilterConfig`, `categories/partials/filter-options.blade.php`). |
| R9 | CSV import is generic | `ProductController::importStore` requires only `name`, `cost_price` and `selling_price`. A blank barcode gets an auto code. Supports `color`, `color_hex`, `size`, `storage`, `ram`, `material`, `weight`, `attr_<name>` / `attribute:<name>` (auto-creates the attribute), description, SEO and OG columns. Template renamed `bynnas-social-products-template.csv` with generic examples (T-shirt, lipstick, mug). View: `resources/views/products/import.blade.php`. |
| R10 | Replace phone demo data with general catalog | New `GeneralCatalogSeeder`: 7 categories (Fashion, Cosmetics & Beauty, Toys & Kids, Baby Care, Home & Kitchen, Electronics, Accessories), 8 brands, 20 families / 45 variants. `DemoSeeder` now calls it instead of `GadgetCatalogSeeder` (old seeder kept, not deleted). Hero slides, mid-promo banners, deal promos, brands, reviews, FAQ and footer links rewritten to be generic. |
| R11 | Permissions enforced server-side | New admin routes sit inside the existing `can:manage inventory` route group in `routes/web.php`. Controllers additionally check `shop_id` ownership. |

**New routes** (all in the `can:manage inventory` group):

```
GET    /products-variants                     products.variants          ProductFamilyController@index
GET    /attributes                            attributes.index           ProductAttributeController@index
POST   /attributes                            attributes.store
PATCH  /attributes/{productAttribute}         attributes.update
DELETE /attributes/{productAttribute}         attributes.destroy
POST   /attributes/{productAttribute}/values  attributes.values.store
PATCH  /attribute-values/{attributeValue}     attributes.values.update
DELETE /attribute-values/{attributeValue}     attributes.values.destroy
```

The navigation (`resources/views/layouts/navigation.blade.php`) adds Variants and Attributes under Catalog.

### B3. Project constraints that must hold

- C1: Do not rebuild the project; extend what exists.
- C2: No duplicate systems — never a second order, inventory, customer or checkout system.
- C3: The app must stay runnable after every major change.
- C4: Do not delete tables or classes unless confirmed unused. Legacy retail code stays isolated, not deleted.
- C5: No destructive migrations without verification. (The Phase 3 migration only adds tables and columns, and makes `barcode` nullable. `down()` reverses it.)
- C6: Server-side permission checks (Spatie) on every sensitive action.
- C7: No commits or pushes to Git.
- C8: Do not hallucinate files, routes, models or features.

### B4. Files

**New:**
- `app/Http/Controllers/ProductAttributeController.php`
- `app/Http/Controllers/ProductFamilyController.php`
- `app/Models/ProductAttribute.php`, `ProductAttributeValue.php`, `ProductVariantValue.php`
- `app/Services/ProductVariantService.php`
- `database/migrations/2026_09_29_100000_create_product_attributes_and_seo.php`
- `database/seeders/GeneralCatalogSeeder.php`
- `resources/views/attributes/index.blade.php`
- `resources/views/products/variants.blade.php`
- `tests/Feature/ProductVariantCatalogTest.php`

**Modified:**
- Controllers: `app/Http/Controllers/ProductController.php`, `app/Http/Controllers/WebsiteController.php`
- Services and models: `app/Services/WebsiteService.php`, `app/Models/Product.php`
- Support classes: `app/Support/CategoryFilterConfig.php`, `app/Support/CategoryIcons.php`
- Seeders: `database/seeders/DemoSeeder.php`, `DemoStorefrontSeeder.php`, `HeroSlideSeeder.php`, `MidPromoBannerSeeder.php`, `WebsiteSeeder.php`
- Admin views:
  - `resources/views/products/partials/form-fields.blade.php`, `products/index.blade.php`, `products/import.blade.php`
  - `resources/views/categories/partials/filter-options.blade.php`
  - `resources/views/layouts/navigation.blade.php`
- Storefront views:
  - `resources/views/website/partials/shop-sidebar.blade.php`, `category-filters.blade.php`, `category-icon-svg.blade.php`
  - `resources/views/website/home.blade.php`, `layout.blade.php`, `product.blade.php`
- Assets: `resources/css/website.css` (swatch/chip filter styles; `npm run build` done)
- Routes: `routes/web.php`

Note: the git working tree also contains earlier uncommitted work (rebrand to "Bynnas Social", retail isolation), so not every modified file belongs to Phase 3.

### B5. Tests added (`tests/Feature/ProductVariantCatalogTest.php`, 11 tests, 81 assertions, all passing)

1. Variable product creates one row per variant, generated `BS…` codes, correct attribute values, legacy colour mirror, per-variant price and stock, clean display name, slug.
2. Duplicate variant combinations are rejected (case-insensitive); nothing saved.
3. Simple product without barcode gets a code; SEO fields and attribute saved.
4. Attribute CRUD; deleting an in-use value or attribute is blocked; renaming a value updates the product.
5. Cashier (no `manage inventory`) is refused; an admin of another shop gets 404; attributes and variants pages render.
6. Storefront `/product/{slug}` and `/product/{id}` return 200 with the SEO title and og tags; the picker marks sold-out options; `attr[...]` filters return correct counts (OR/AND).
7. CSV import: blank barcode gets a code, explicit barcode kept, `color`/`size`/`attr_fabric` mapped, attribute auto-created.
8. CSV template is generic (no phone brands).
9. Legacy category filter types (`storage`/`ram`/`color`) are read as attribute filters; old `?storage[]=256_gb` links still filter.
10. A legacy phone row with only `color`/`storage`/`ram` columns still renders (storefront by slug/ID, admin edit), without IMEI UI when retail is off.
11. The obsolete `product_mode=gadget` is rejected.

Obsolete-architecture cleanup (after the first review): removed the `gadget` product-mode alias, dead legacy-column code in `normalizeVariantFields`, `Product::variantSiblings()`, `Product::displayColor()`, `ProductAttribute::LEGACY_COLUMNS`, the `unique_memory_sizes` / `memory_size_sort_key` helpers, the legacy-column category filter branches, 3 unreferenced phone seeders (`DemoProductsSeeder`, `VariantFamilyCatalogSeeder`, `ProductImageRepairSeeder`) and 6 orphaned phone banner images in `database/seeders/assets/slides`. No database changes.

### B6. Verification commands (Windows PowerShell, from the project root)

```powershell
# Phase 3 tests
php vendor/bin/phpunit tests/Feature/ProductVariantCatalogTest.php

# Full feature suite
# Note: `php artisan test` fails because phpunit.xml references a missing tests/Unit folder
php vendor/bin/phpunit tests/Feature

# Blade compile + routes
php artisan view:cache
php artisan route:list --name=attributes
php artisan route:list --name=products.variants
```

Expected:
- Phase 3 file: 11/11 pass.
- Full suite: 37 tests, **11 known pre-existing failures**, all in Breeze scaffolding: `Auth\EmailVerificationTest` (2), `Auth\PasswordConfirmationTest` (2), `Auth\PasswordUpdateTest` (2), `ProfileTest` (5). They failed before Phase 3 because the app replaced Breeze's default routes/pages.
- On every PHP run you will see a harmless "Module mysqli already loaded" warning from the local XAMPP config.

Manual smoke results (logged-in admin, retail OFF and ON). All returned 200:
- `/dashboard`
- `/products`, `/products/create`, `/products/{id}/edit`
- `/products-variants`, `/attributes`
- `/products-import/csv`, `/products-import/csv/template`
- `/categories`, `/brands`
- `/`, `/shop`, `/shop?attr[color][]=black`
- `/product/{slug}`, `/product/{id}`

### B7. Known limitations / decisions to scrutinise

- Variant = product row (see B1). The reviewer should confirm this does not break orders, stock or IMEI flows, and that it is acceptable for later phases.
- Legacy columns (`color`, `color_hex`, `storage`, `ram`) are still present and mirrored. There are two sources of truth, with attribute values as the master.
- `product_attributes.type` is free-form within `ProductAttribute::TYPES`. There is no per-category attribute assignment yet.
- The admin sidebar has **not** yet been restructured into the final sections (DASHBOARD, SALES, CATALOG, INVENTORY, SOCIAL COMMERCE, MARKETING, DELIVERY, WEBSITE, REPORTS, FINANCE, SETTINGS). That is planned for a later phase.
- Local environment only:
  - The local database's demo catalog was replaced with the general catalog.
  - `public/storage` was re-created as a proper link via `php artisan storage:link` (it had been an empty folder, which made images return 403).

### B8. What comes next (for context only; not part of this review)

- **Phase 4 — Campaigns:** CRUD, social attribution (source, medium, campaign, landing page, product), trackable links, no fake analytics.
- **Phase 5 — Landing pages:** `/campaign/{slug}` with a guest order form through a shared `OrderCreationService`.
- **Phase 6 — Leads and CRM:** statuses and segments.
- **Phase 7 — Abandoned carts:** build on `/cart/sync`.
- **Phase 8 — Marketing:** coupons, promotions, flash sales, bundles, discount rules.
- **Phase 9 — Order and delivery workflow:**
  - statuses NEW → CONFIRMED → PROCESSING → PACKED → SHIPPED → DELIVERED → COMPLETED, plus CANCELLED / RETURN REQUESTED / RETURNED / REFUNDED;
  - stock committed at PACKED;
  - delivery zones, couriers, tracking;
  - COD kept, with extensible payment methods;
  - an order verification step.
- **Then:** internal notifications, the final admin sidebar, and a security and UI pass.
