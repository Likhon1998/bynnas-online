<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_faq_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon', 40)->nullable()->default('help');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'slug']);
        });

        Schema::table('cms_faqs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('shop_id')->constrained('cms_faq_categories')->nullOnDelete();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('faq_hero_title')->nullable()->after('blog_newsletter_text');
            $table->string('faq_hero_subtitle', 500)->nullable()->after('faq_hero_title');
            $table->string('faq_help_title')->nullable()->after('faq_hero_subtitle');
            $table->string('faq_help_text', 500)->nullable()->after('faq_help_title');
            $table->string('faq_help_button')->nullable()->after('faq_help_text');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'faq_hero_title', 'faq_hero_subtitle',
                'faq_help_title', 'faq_help_text', 'faq_help_button',
            ]);
        });

        Schema::table('cms_faqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('cms_faq_categories');
    }
};
