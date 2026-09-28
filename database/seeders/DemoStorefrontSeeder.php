<?php

namespace Database\Seeders;

use App\Models\CmsFaq;
use App\Models\CmsFaqCategory;
use App\Models\CmsPage;
use App\Models\CmsReview;
use App\Models\Product;
use App\Models\Shop;
use App\Services\WebsiteService;
use Illuminate\Database\Seeder;

class DemoStorefrontSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $this->seedFlashSales($shop->id);
        $this->seedReviews($shop->id);
        $this->seedPages($shop->id);
        app(WebsiteService::class)->faqCategories();
        $this->seedFaqs($shop->id);

        $this->command?->info('Demo storefront extras ready (sales, reviews, pages, FAQs).');
    }

    private function seedFlashSales(int $shopId): void
    {
        $targets = [
            'Sony WH-1000XM5' => 0.82,
            'Apple AirPods Pro 2 USB-C' => 0.85,
            'Samsung Galaxy A56 256GB' => 0.88,
            'JBL Charge 5 Portable' => 0.80,
            'GoPro HERO13 Black' => 0.86,
            'Nothing Phone (2a) 128GB' => 0.90,
            'Anker PowerCore 20000mAh' => 0.78,
            'Apple Watch SE 2nd Gen 44mm' => 0.87,
        ];

        $starts = now()->subDay();
        $ends = now()->addDays(7);

        foreach ($targets as $name => $ratio) {
            $product = Product::query()
                ->where('shop_id', $shopId)
                ->where('name', $name)
                ->first();

            if (! $product) {
                continue;
            }

            $salePrice = round((float) $product->selling_price * $ratio, -1);
            if ($salePrice >= (float) $product->selling_price) {
                $salePrice = max(1, (float) $product->selling_price - 500);
            }

            $product->applySale($salePrice, $starts, $ends);
        }

        // Fallback: put a few bestsellers on permanent POS discount if timed sales missed.
        if (Product::where('shop_id', $shopId)->onSale()->count() < 4) {
            Product::where('shop_id', $shopId)
                ->where('is_best_seller', true)
                ->where('stock_quantity', '>', 0)
                ->orderBy('id')
                ->take(6)
                ->get()
                ->each(function (Product $product) {
                    $product->forceFill([
                        'pos_discount_type' => 'percent',
                        'pos_discount_value' => 15,
                    ])->save();
                });
        }
    }

    private function seedReviews(int $shopId): void
    {
        $reviews = [
            [
                'customer_name' => 'Rafi Ahmed',
                'customer_title' => 'Verified Buyer · Dhaka',
                'rating' => 5,
                'body' => 'Ordered an iPhone from Maks Gadget and delivery was next-day. Packaging was sealed and the price was better than the local market.',
                'sort_order' => 1,
            ],
            [
                'customer_name' => 'Nusrat Jahan',
                'customer_title' => 'Content Creator',
                'rating' => 5,
                'body' => 'Bought Sony XM5 headphones here. Genuine product, clear invoice, and support helped me pick the right model for travel.',
                'sort_order' => 2,
            ],
            [
                'customer_name' => 'Imran Hossain',
                'customer_title' => 'University Student',
                'rating' => 4,
                'body' => 'Great laptop selection for students. The MacBook Air I got runs cool and battery lasts through long class days.',
                'sort_order' => 3,
            ],
            [
                'customer_name' => 'Sadia Rahman',
                'customer_title' => 'Office Professional',
                'rating' => 5,
                'body' => 'Flash sale on AirPods was real — checked serial and warranty with no issues. Will shop accessories here again.',
                'sort_order' => 4,
            ],
            [
                'customer_name' => 'Karim Ullah',
                'customer_title' => 'Gamer',
                'rating' => 5,
                'body' => 'PS5 Slim arrived sealed with dualsense. Staff answered my questions on Discord the same evening. Solid experience.',
                'sort_order' => 5,
            ],
            [
                'customer_name' => 'Farzana Akter',
                'customer_title' => 'Small Business Owner',
                'rating' => 4,
                'body' => 'Ordered chargers and cables in bulk for our shop. Quality Anker gear and COD made restocking easy.',
                'sort_order' => 6,
            ],
        ];

        foreach ($reviews as $review) {
            CmsReview::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'customer_name' => $review['customer_name'],
                ],
                array_merge($review, [
                    'is_featured' => true,
                    'is_published' => true,
                ])
            );
        }
    }

    private function seedPages(int $shopId): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'excerpt' => 'Maks Gadget brings authentic phones, laptops, and accessories to shoppers across Bangladesh.',
                'body' => "Maks Gadget is a Dhaka-based gadget store focused on authentic devices, fair pricing, and reliable after-sales support.\n\nFrom flagship smartphones to everyday chargers, we stock brands people trust — Apple, Samsung, Sony, and more — with clear warranty information on every product page.\n\nWhether you shop online with cash on delivery or visit us for advice, our goal is simple: help you buy the right gadget with confidence.",
                'sort_order' => 1,
            ],
            [
                'title' => 'Shipping & Delivery',
                'slug' => 'shipping-delivery',
                'excerpt' => 'Cash on delivery across our service area with order tracking.',
                'body' => "We currently deliver within our Bangladesh service area using trusted courier partners.\n\nMost orders placed before 4 PM are prepared the same day. You can track your package with your Order ID and phone number from the Track Order page.\n\nDelivery charges may vary by zone and are shown at checkout before you confirm.",
                'sort_order' => 2,
            ],
            [
                'title' => 'Returns & Warranty',
                'slug' => 'returns-warranty',
                'excerpt' => 'Easy returns on unused items and warranty support on eligible products.',
                'body' => "Most products can be returned within 30 days if unused and in original packaging. Opened earbuds, software, and special-order items may be excluded.\n\nManufacturer or store warranty details appear on each product page. Keep your invoice handy for any warranty claim — our support team will guide you through the next steps.",
                'sort_order' => 3,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'excerpt' => 'How Maks Gadget collects and protects your information.',
                'body' => "We collect only the information needed to process orders, deliver products, and provide support — such as your name, phone number, and delivery address.\n\nWe do not sell your personal data. Payment for online orders is cash on delivery, so we do not store card details for those checkouts.\n\nFor privacy questions, email support@maksgadget.com.",
                'sort_order' => 4,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'excerpt' => 'Rules for shopping with Maks Gadget online and in store.',
                'body' => "By placing an order with Maks Gadget you agree to these terms.\n\nOrders: Online orders are subject to stock availability. Cash on delivery (COD) orders reserve inventory until packed, cancelled, or delivered.\n\nPricing: The price shown at checkout is the final merchandise price. Delivery charges are calculated by zone and shown before you confirm.\n\nDelivery: Delivery times are estimates. A signature or OTP may be required. If you refuse a COD parcel without a valid reason, we may limit future COD eligibility.\n\nReturns: Unused items in original packaging may be returned within the period stated on our Returns & Warranty page.\n\nLaw: These terms are governed by the laws of Bangladesh. Contact support@maksgadget.com for questions.",
                'sort_order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => $page['slug']],
                array_merge($page, [
                    'meta_title' => $page['title'].' | Maks Gadget',
                    'meta_description' => $page['excerpt'],
                    'show_in_footer' => true,
                    'is_published' => true,
                ])
            );
        }
    }

    private function seedFaqs(int $shopId): void
    {
        $categories = CmsFaqCategory::where('shop_id', $shopId)->get()->keyBy('slug');
        if ($categories->isEmpty()) {
            return;
        }

        $faqs = [
            ['slug' => 'orders-payments', 'q' => 'Do you offer Cash on Delivery (COD)?', 'a' => 'Yes. Most orders across our Bangladesh delivery area support Cash on Delivery. You pay when the parcel arrives and you verify the sealed package.', 'sort' => 1],
            ['slug' => 'orders-payments', 'q' => 'Can I reserve a phone or laptop before paying?', 'a' => 'Online COD orders reserve stock until packed or cancelled. For high-demand flagships, contact support to confirm live stock before placing the order.', 'sort' => 2],
            ['slug' => 'shipping-delivery', 'q' => 'How long does delivery take in Dhaka?', 'a' => 'Most Dhaka orders placed before 4 PM are prepared the same day and typically arrive within 1–2 working days, depending on your area and courier schedule.', 'sort' => 1],
            ['slug' => 'shipping-delivery', 'q' => 'Is delivery free?', 'a' => 'Delivery is free on eligible orders over ৳10,000. Smaller orders show zone-based delivery charges at checkout before you confirm.', 'sort' => 2],
            ['slug' => 'returns-refunds', 'q' => 'What is your return policy on gadgets?', 'a' => 'Unused products in original sealed packaging can usually be returned within 30 days. Opened earbuds, software, and special-order items may be excluded — details are listed on each product page.', 'sort' => 1],
            ['slug' => 'products-warranty', 'q' => 'Are your gadgets original with warranty?', 'a' => 'Yes. We sell sealed, authentic devices. Manufacturer or store warranty details appear on the product page. Keep your invoice for any warranty claim.', 'sort' => 1],
            ['slug' => 'products-warranty', 'q' => 'Can I check IMEI / serial before buying?', 'a' => 'For phones and laptops, our team can help you verify authenticity after purchase. Contact support with your Order ID if you need assistance.', 'sort' => 2],
            ['slug' => 'promotions-discounts', 'q' => 'How do flash sales work?', 'a' => 'Flash sale prices are time-limited and shown on the product card while the deal is active. Stock is limited — once it ends, the regular price returns.', 'sort' => 1],
            ['slug' => 'account-security', 'q' => 'Do I need an account to order?', 'a' => 'You can browse without an account. Creating an account helps you track orders faster and save delivery details for next time.', 'sort' => 1],
            ['slug' => 'others', 'q' => 'Do you help choose the right gadget?', 'a' => 'Absolutely. Tell us your budget and use-case (gaming, study, photography, office) via WhatsApp or the contact form — we will recommend suitable models in stock.', 'sort' => 1],
        ];

        foreach ($faqs as $faq) {
            $category = $categories->get($faq['slug']);
            if (! $category) {
                continue;
            }

            CmsFaq::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'question' => $faq['q'],
                ],
                [
                    'category_id' => $category->id,
                    'category' => $category->name,
                    'answer' => $faq['a'],
                    'sort_order' => $faq['sort'],
                    'is_published' => true,
                ]
            );
        }
    }
}
