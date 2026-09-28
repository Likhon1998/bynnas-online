<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('show_in_footer')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
            $table->index(['shop_id', 'is_published']);
        });

        Schema::create('cms_blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('author_name')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
            $table->index(['shop_id', 'is_published', 'published_at']);
        });

        Schema::create('cms_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->string('category')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'is_published', 'sort_order']);
        });

        Schema::create('cms_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_title')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->string('avatar_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['shop_id', 'is_published', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_reviews');
        Schema::dropIfExists('cms_faqs');
        Schema::dropIfExists('cms_blogs');
        Schema::dropIfExists('cms_pages');
    }
};
