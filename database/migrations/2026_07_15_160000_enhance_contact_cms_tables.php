<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_hero_kicker')->nullable()->after('faq_help_button');
            $table->string('contact_hero_title')->nullable()->after('contact_hero_kicker');
            $table->string('contact_hero_subtitle', 500)->nullable()->after('contact_hero_title');
            $table->string('contact_chat_title')->nullable()->after('contact_hero_subtitle');
            $table->string('contact_chat_text', 255)->nullable()->after('contact_chat_title');
            $table->string('contact_chat_status')->nullable()->after('contact_chat_text');
            $table->string('contact_email_card_title')->nullable()->after('contact_chat_status');
            $table->string('contact_email_card_text', 255)->nullable()->after('contact_email_card_title');
            $table->string('contact_phone_card_title')->nullable()->after('contact_email_card_text');
            $table->string('contact_phone_card_text', 255)->nullable()->after('contact_phone_card_title');
            $table->string('contact_hours_title')->nullable()->after('contact_phone_card_text');
            $table->string('contact_hours_weekday', 255)->nullable()->after('contact_hours_title');
            $table->string('contact_hours_weekend', 255)->nullable()->after('contact_hours_weekday');
            $table->string('contact_form_title')->nullable()->after('contact_hours_weekend');
            $table->string('contact_form_subtitle', 255)->nullable()->after('contact_form_title');
            $table->text('contact_map_embed')->nullable()->after('contact_form_subtitle');
            $table->string('contact_website_url')->nullable()->after('contact_map_embed');
            $table->string('contact_newsletter_title')->nullable()->after('contact_website_url');
            $table->string('contact_newsletter_text', 255)->nullable()->after('contact_newsletter_title');
        });

        Schema::create('cms_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->string('order_number')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_contact_messages');

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_hero_kicker', 'contact_hero_title', 'contact_hero_subtitle',
                'contact_chat_title', 'contact_chat_text', 'contact_chat_status',
                'contact_email_card_title', 'contact_email_card_text',
                'contact_phone_card_title', 'contact_phone_card_text',
                'contact_hours_title', 'contact_hours_weekday', 'contact_hours_weekend',
                'contact_form_title', 'contact_form_subtitle',
                'contact_map_embed', 'contact_website_url',
                'contact_newsletter_title', 'contact_newsletter_text',
            ]);
        });
    }
};
