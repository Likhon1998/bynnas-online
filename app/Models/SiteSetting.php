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
        'delivery_confirmation_amount', 'delivery_confirmation_instructions',
    ];

    /** Social profiles editable under CMS → Contact; icon paths use a 24×24 viewBox. */
    public const SOCIAL_NETWORKS = [
        'facebook' => [
            'label' => 'Facebook',
            'placeholder' => 'https://facebook.com/yourpage',
            'path' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'placeholder' => 'https://instagram.com/yourpage',
            'path' => 'M7 3h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7a4 4 0 014-4zm5 4.5A4.5 4.5 0 1016.5 12 4.5 4.5 0 0012 7.5zm5.25-.75a1.125 1.125 0 11-1.125-1.125A1.125 1.125 0 0117.25 6.75z',
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'placeholder' => 'https://tiktok.com/@yourpage',
            'path' => 'M16.6 5.82A4.28 4.28 0 0115.54 3h-3.09v12.4a2.59 2.59 0 01-2.59 2.5 2.6 2.6 0 01-2.6-2.6c0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 004.3 1.38V7.3s-1.88.09-3.24-1.48z',
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'placeholder' => '01XXXXXXXXX or https://wa.me/8801XXXXXXXXX',
            'path' => 'M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21c5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.03c-.24.68-1.42 1.3-1.95 1.34-.5.05-.97.23-3.27-.68-2.76-1.09-4.5-3.92-4.64-4.1-.13-.18-1.1-1.47-1.1-2.8 0-1.33.7-1.99.95-2.26.25-.27.54-.34.72-.34h.52c.17 0 .39-.06.61.47.24.56.79 1.93.86 2.07.07.14.11.3.02.48-.09.18-.14.3-.27.46-.14.16-.29.36-.41.48-.14.14-.28.29-.12.56.16.27.71 1.17 1.52 1.9 1.05.93 1.93 1.22 2.2 1.36.27.14.43.11.59-.07.16-.18.68-.79.86-1.06.18-.27.36-.23.61-.14.25.09 1.59.75 1.86.89.27.14.45.2.52.32.07.11.07.66-.17 1.34z',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'placeholder' => 'https://youtube.com/@yourchannel',
            'path' => 'M22.5 7.2a2.8 2.8 0 00-2-2C18.7 4.8 12 4.8 12 4.8s-6.7 0-8.5.4a2.8 2.8 0 00-2 2A29 29 0 001 12a29 29 0 00.5 4.8 2.8 2.8 0 002 2c1.8.4 8.5.4 8.5.4s6.7 0 8.5-.4a2.8 2.8 0 002-2A29 29 0 0023 12a29 29 0 00-.5-4.8zM9.8 15.2V8.8L15.7 12l-5.9 3.2z',
        ],
        'twitter' => [
            'label' => 'X / Twitter',
            'placeholder' => 'https://x.com/yourpage',
            'path' => 'M18.244 3H21l-6.52 7.45L22 21h-5.98l-4.68-6.12L6.1 21H3.34l6.98-7.97L2 3h6.14l4.23 5.61L18.244 3zm-1.05 16.2h1.66L7.01 4.7H5.23l11.964 14.5z',
        ],
    ];

    /**
     * Filled social profiles in display order.
     *
     * @return list<array{key: string, label: string, url: string, path: string}>
     */
    public static function socialProfiles(?array $saved): array
    {
        $rows = [];
        foreach (self::SOCIAL_NETWORKS as $key => $meta) {
            $url = trim((string) ($saved[$key] ?? ''));
            if ($url !== '') {
                $rows[] = ['key' => $key, 'label' => $meta['label'], 'url' => $url, 'path' => $meta['path']];
            }
        }

        return $rows;
    }

    /** Accepts a WhatsApp link or a phone number (01XXXXXXXXX / +8801…) and returns a wa.me link. */
    public static function whatsappUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('#^https?://#i', $value)) {
            return $value !== '' ? $value : null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $digits = '88'.$digits;
        }

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

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
            'store_name' => config('app.name', 'Bynnas Social'),
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);
    }
}
