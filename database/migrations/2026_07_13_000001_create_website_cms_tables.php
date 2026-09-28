<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('default_shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('store_name')->default('GAGET STORE');
            $table->string('logo_path')->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->string('currency_symbol', 10)->default('$');
            $table->string('special_offer_text')->nullable();
            $table->string('trusted_by_text')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('badge_text')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price_from', 10, 2)->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_text')->default('Shop Now');
            $table->string('button_url')->nullable();
            $table->string('learn_more_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('icon')->default('truck');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->decimal('price_from', 10, 2)->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_text')->default('Shop Now');
            $table->string('button_url')->nullable();
            $table->string('theme')->default('dark');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('navigation_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->string('location')->default('main_nav');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('product_count_label')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable();
            $table->text('short_description')->nullable();
            $table->string('brand_name')->nullable();
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->boolean('is_published')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'original_price', 'short_description', 'brand_name',
                'rating', 'review_count', 'is_best_seller', 'is_featured',
                'is_new_arrival', 'is_published',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'description', 'is_featured', 'product_count_label']);
        });

        Schema::dropIfExists('navigation_links');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('promo_banners');
        Schema::dropIfExists('site_features');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('site_settings');
    }
};
