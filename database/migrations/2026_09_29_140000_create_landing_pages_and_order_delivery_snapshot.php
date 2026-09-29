<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->string('status', 20)->default('draft');
            $table->string('headline', 200);
            $table->string('subheadline', 500)->nullable();
            $table->string('hero_image')->nullable();
            $table->string('offer_badge', 60)->nullable();
            $table->string('offer_text', 500)->nullable();
            $table->dateTime('countdown_ends_at')->nullable();
            $table->json('product_ids')->nullable();
            $table->json('benefits')->nullable();
            $table->json('faqs')->nullable();
            $table->boolean('show_reviews')->default(true);
            $table->string('cta_text', 60)->nullable();
            $table->string('accent_color', 9)->nullable();
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('landing_page_id')->nullable()->after('campaign_id')->constrained('landing_pages')->nullOnDelete();
            $table->string('delivery_name', 120)->nullable()->after('delivery_zone');
            $table->string('delivery_phone', 30)->nullable()->after('delivery_name');
            $table->text('delivery_address')->nullable()->after('delivery_phone');
            $table->string('customer_note', 500)->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landing_page_id');
            $table->dropColumn(['delivery_name', 'delivery_phone', 'delivery_address', 'customer_note']);
        });

        Schema::dropIfExists('landing_pages');
    }
};
