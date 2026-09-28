<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'default_shop_id', 'store_name', 'logo_path', 'favicon_path',
        'currency_code', 'currency_symbol', 'special_offer_text',
        'blog_hero_kicker', 'blog_hero_title', 'blog_hero_subtitle', 'blog_hero_image',
        'blog_newsletter_title', 'blog_newsletter_text',
        'blog_articles_title',
        'blog_feature_1_title', 'blog_feature_1_text',
        'blog_feature_2_title', 'blog_feature_2_text',
        'blog_feature_3_title', 'blog_feature_3_text',
        'faq_hero_title', 'faq_hero_subtitle',
        'faq_help_title', 'faq_help_text', 'faq_help_button',
        'contact_hero_kicker', 'contact_hero_title', 'contact_hero_subtitle',
        'contact_chat_title', 'contact_chat_text', 'contact_chat_status',
        'contact_email_card_title', 'contact_email_card_text',
        'contact_phone_card_title', 'contact_phone_card_text',
        'contact_hours_title', 'contact_hours_weekday', 'contact_hours_weekend',
        'contact_form_title', 'contact_form_subtitle',
        'contact_map_embed', 'contact_website_url',
        'contact_newsletter_title', 'contact_newsletter_text',
        'trusted_by_text',
        'footer_tagline',
        'home_copy',
        'deals_kicker', 'deals_title', 'deals_title_accent', 'deals_subtitle',
        'contact_email', 'contact_phone',
        'contact_address', 'social_links',
        'delivery_inside_dhaka', 'delivery_outside_dhaka',
        'delivery_free_enabled', 'delivery_free_min_amount',
        'delivery_cod_enabled', 'delivery_confirmation_enabled',
        'delivery_confirmation_amount',
    ];

    protected $casts = [
        'social_links' => 'array',
        'home_copy' => 'array',
        'delivery_inside_dhaka' => 'decimal:2',
        'delivery_outside_dhaka' => 'decimal:2',
        'delivery_free_enabled' => 'boolean',
        'delivery_free_min_amount' => 'decimal:2',
        'delivery_cod_enabled' => 'boolean',
        'delivery_confirmation_enabled' => 'boolean',
        'delivery_confirmation_amount' => 'decimal:2',
    ];

    public function defaultShop()
    {
        return $this->belongsTo(Shop::class, 'default_shop_id');
    }

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'store_name' => config('app.name', 'Maks Gadget'),
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);
    }
}
