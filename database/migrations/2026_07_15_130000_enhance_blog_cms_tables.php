<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_blog_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 30)->nullable()->default('blue');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'slug']);
        });

        Schema::table('cms_blogs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('shop_id')->constrained('cms_blog_categories')->nullOnDelete();
            $table->boolean('is_featured')->default(false)->after('is_published');
            $table->unsignedInteger('views_count')->default(0)->after('is_featured');
        });

        Schema::create('cms_newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->timestamps();
            $table->unique(['shop_id', 'email']);
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('blog_hero_kicker')->nullable()->after('special_offer_text');
            $table->string('blog_hero_title')->nullable()->after('blog_hero_kicker');
            $table->string('blog_hero_subtitle', 500)->nullable()->after('blog_hero_title');
            $table->string('blog_hero_image')->nullable()->after('blog_hero_subtitle');
            $table->string('blog_newsletter_title')->nullable()->after('blog_hero_image');
            $table->string('blog_newsletter_text', 500)->nullable()->after('blog_newsletter_title');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'blog_hero_kicker', 'blog_hero_title', 'blog_hero_subtitle', 'blog_hero_image',
                'blog_newsletter_title', 'blog_newsletter_text',
            ]);
        });

        Schema::dropIfExists('cms_newsletter_subscribers');

        Schema::table('cms_blogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['is_featured', 'views_count']);
        });

        Schema::dropIfExists('cms_blog_categories');
    }
};
