<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_blogs', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_blogs', 'reading_time')) {
                $table->unsignedSmallInteger('reading_time')->nullable()->after('views_count');
            }
        });

        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'blog_articles_title')) {
                $table->string('blog_articles_title')->nullable()->after('blog_newsletter_text');
            }
            if (! Schema::hasColumn('site_settings', 'blog_feature_1_title')) {
                $table->string('blog_feature_1_title')->nullable()->after('blog_articles_title');
                $table->string('blog_feature_1_text')->nullable()->after('blog_feature_1_title');
                $table->string('blog_feature_2_title')->nullable()->after('blog_feature_1_text');
                $table->string('blog_feature_2_text')->nullable()->after('blog_feature_2_title');
                $table->string('blog_feature_3_title')->nullable()->after('blog_feature_2_text');
                $table->string('blog_feature_3_text')->nullable()->after('blog_feature_3_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_blogs', function (Blueprint $table) {
            if (Schema::hasColumn('cms_blogs', 'reading_time')) {
                $table->dropColumn('reading_time');
            }
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $cols = [
                'blog_articles_title',
                'blog_feature_1_title', 'blog_feature_1_text',
                'blog_feature_2_title', 'blog_feature_2_text',
                'blog_feature_3_title', 'blog_feature_3_text',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('site_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
