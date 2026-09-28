<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Concerns\ShopScoped;
use App\Http\Controllers\Controller;
use App\Models\CmsContactMessage;
use App\Models\CmsNewsletterSubscriber;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use ShopScoped;

    public function index()
    {
        $settings = SiteSetting::current();
        $messages = CmsContactMessage::where('shop_id', $this->shopId())
            ->latest()
            ->paginate(20);
        $unread = CmsContactMessage::where('shop_id', $this->shopId())->where('is_read', false)->count();
        $subscribers = CmsNewsletterSubscriber::where('shop_id', $this->shopId())
            ->latest()
            ->paginate(20, ['*'], 'subs_page');

        return view('cms.contact.index', compact('settings', 'messages', 'unread', 'subscribers'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_address' => 'nullable|string|max:500',
            'contact_website_url' => 'nullable|string|max:255',
            'contact_hero_kicker' => 'nullable|string|max:80',
            'contact_hero_title' => 'nullable|string|max:160',
            'contact_hero_subtitle' => 'nullable|string|max:500',
            'contact_chat_title' => 'nullable|string|max:120',
            'contact_chat_text' => 'nullable|string|max:255',
            'contact_chat_status' => 'nullable|string|max:80',
            'contact_email_card_title' => 'nullable|string|max:120',
            'contact_email_card_text' => 'nullable|string|max:255',
            'contact_phone_card_title' => 'nullable|string|max:120',
            'contact_phone_card_text' => 'nullable|string|max:255',
            'contact_hours_title' => 'nullable|string|max:120',
            'contact_hours_weekday' => 'nullable|string|max:255',
            'contact_hours_weekend' => 'nullable|string|max:255',
            'contact_form_title' => 'nullable|string|max:160',
            'contact_form_subtitle' => 'nullable|string|max:255',
            'contact_map_embed' => 'nullable|string|max:8000',
            'contact_newsletter_title' => 'nullable|string|max:160',
            'contact_newsletter_text' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|string|max:255',
            'social_twitter' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',
        ]);

        $settings = SiteSetting::query()->first();
        if (!$settings) {
            $settings = new SiteSetting();
            $settings->default_shop_id = $this->shopId();
            $settings->store_name = config('app.name', 'Maks Gadget');
            $settings->currency_code = 'BDT';
            $settings->currency_symbol = 'Tk';
        }

        $settings->default_shop_id = $settings->default_shop_id ?: $this->shopId();

        $fields = [
            'contact_email', 'contact_phone', 'contact_address', 'contact_website_url',
            'contact_hero_kicker', 'contact_hero_title', 'contact_hero_subtitle',
            'contact_chat_title', 'contact_chat_text', 'contact_chat_status',
            'contact_email_card_title', 'contact_email_card_text',
            'contact_phone_card_title', 'contact_phone_card_text',
            'contact_hours_title', 'contact_hours_weekday', 'contact_hours_weekend',
            'contact_form_title', 'contact_form_subtitle',
            'contact_newsletter_title', 'contact_newsletter_text',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $settings->{$field} = $data[$field] !== '' ? $data[$field] : null;
            }
        }

        if (array_key_exists('contact_map_embed', $data)) {
            $raw = $data['contact_map_embed'] !== '' ? $data['contact_map_embed'] : null;
            $settings->contact_map_embed = $raw ? (normalize_map_embed_url($raw) ?: $raw) : null;
        }

        $settings->social_links = array_filter([
            'facebook' => $data['social_facebook'] ?? null,
            'twitter' => $data['social_twitter'] ?? null,
            'instagram' => $data['social_instagram'] ?? null,
            'youtube' => $data['social_youtube'] ?? null,
        ]);

        $settings->save();

        return back()->with('success', 'Contact page settings saved.');
    }

    public function showMessage(CmsContactMessage $message)
    {
        $this->authorizeShop($message);
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('cms.contact.show', compact('message'));
    }

    public function markRead(CmsContactMessage $message)
    {
        $this->authorizeShop($message);
        $message->update(['is_read' => true]);

        return back()->with('success', 'Message marked as read.');
    }

    public function destroyMessage(CmsContactMessage $message)
    {
        $this->authorizeShop($message);
        $message->delete();

        return redirect()->route('cms.contact.index')->with('success', 'Message deleted.');
    }
}
