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
        // Keys match a product slug or a whole variant family (variant_group).
        $targets = [
            'matte-lipstick' => 0.80,
            'wireless-earbuds-pro' => 0.85,
            'slim-fit-denim-jeans' => 0.88,
            'soft-plush-teddy-bear' => 0.82,
            'everyday-backpack' => 0.86,
            'hydrating-body-lotion' => 0.90,
        ];

        $starts = now()->subDay();
        $ends = now()->addDays(7);

        foreach ($targets as $key => $ratio) {
            $products = Product::query()
                ->where('shop_id', $shopId)
                ->where(fn ($q) => $q->where('variant_group', $key)->orWhere('slug', $key))
                ->get();

            foreach ($products as $product) {
                $salePrice = round((float) $product->selling_price * $ratio, -1);
                if ($salePrice >= (float) $product->selling_price) {
                    $salePrice = max(1, (float) $product->selling_price - 50);
                }

                $product->applySale($salePrice, $starts, $ends);
            }
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
                'body' => 'Saw the kurti on Facebook, ordered from the link and it arrived next day in Dhaka. Fabric quality is exactly like the photos.',
                'sort_order' => 1,
            ],
            [
                'customer_name' => 'Nusrat Jahan',
                'customer_title' => 'Instagram Shopper',
                'rating' => 5,
                'body' => 'The matte lipstick shades are gorgeous and long-lasting. Support helped me pick the right shade on WhatsApp before I ordered.',
                'sort_order' => 2,
            ],
            [
                'customer_name' => 'Imran Hossain',
                'customer_title' => 'University Student',
                'rating' => 4,
                'body' => 'Bought a pack of cotton tees for campus. Sizes are true to fit and the colours stayed bright after washing.',
                'sort_order' => 3,
            ],
            [
                'customer_name' => 'Sadia Rahman',
                'customer_title' => 'Office Professional',
                'rating' => 5,
                'body' => 'The flash sale price on the backpack was real and delivery was quick. Cash on delivery made it easy to trust a first order.',
                'sort_order' => 4,
            ],
            [
                'customer_name' => 'Karim Ullah',
                'customer_title' => 'Parent',
                'rating' => 5,
                'body' => 'Got building blocks and a teddy bear for my kids. Well packed, safe materials, and the team confirmed my order by phone the same evening.',
                'sort_order' => 5,
            ],
            [
                'customer_name' => 'Farzana Akter',
                'customer_title' => 'Small Business Owner',
                'rating' => 4,
                'body' => 'Ordered bedsheets and kitchenware for our new flat. Good quality for the price and returns were hassle-free.',
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
                'excerpt' => 'Bynnas Social brings trending, authentic products to shoppers across Bangladesh.',
                'body' => "Bynnas Social is a Dhaka-based online store focused on authentic products, fair pricing, and reliable customer support.\n\nWe bring you the products you discover on Facebook, Instagram and TikTok — from brands people trust — with clear details on every product page.\n\nWhether you order from our website, a campaign page, or a social post with cash on delivery, our goal is simple: help you buy with confidence.",
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
                'excerpt' => 'How Bynnas Social collects and protects your information.',
                'body' => "We collect only the information needed to process orders, deliver products, and provide support — such as your name, phone number, and delivery address.\n\nWe do not sell your personal data. Payment for online orders is cash on delivery, so we do not store card details for those checkouts.\n\nFor privacy questions, email support@bynnas.com.",
                'sort_order' => 4,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'excerpt' => 'Rules for shopping with Bynnas Social.',
                'body' => "By placing an order with Bynnas Social you agree to these terms.\n\nOrders: Online orders are subject to stock availability. Cash on delivery (COD) orders reserve inventory until packed, cancelled, or delivered.\n\nPricing: The price shown at checkout is the final merchandise price. Delivery charges are calculated by zone and shown before you confirm.\n\nDelivery: Delivery times are estimates. A signature or OTP may be required. If you refuse a COD parcel without a valid reason, we may limit future COD eligibility.\n\nReturns: Unused items in original packaging may be returned within the period stated on our Returns & Warranty page.\n\nLaw: These terms are governed by the laws of Bangladesh. Contact support@bynnas.com for questions.",
                'sort_order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => $page['slug']],
                array_merge($page, [
                    'meta_title' => $page['title'].' | Bynnas Social',
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
            ['slug' => 'orders-payments', 'q' => 'Is stock reserved when I order?', 'a' => 'Online COD orders reserve stock until packed or cancelled. For high-demand items, contact support to confirm live stock before placing the order.', 'sort' => 2],
            ['slug' => 'shipping-delivery', 'q' => 'How long does delivery take in Dhaka?', 'a' => 'Most Dhaka orders placed before 4 PM are prepared the same day and typically arrive within 1–2 working days, depending on your area and courier schedule.', 'sort' => 1],
            ['slug' => 'shipping-delivery', 'q' => 'Is delivery free?', 'a' => 'Delivery is free on eligible orders over ৳10,000. Smaller orders show zone-based delivery charges at checkout before you confirm.', 'sort' => 2],
            ['slug' => 'returns-refunds', 'q' => 'What is your return policy?', 'a' => 'Unused products in original packaging can usually be returned within 30 days. Opened hygiene items and special-order items may be excluded — details are listed on each product page.', 'sort' => 1],
            ['slug' => 'products-warranty', 'q' => 'Are your products original?', 'a' => 'Yes. We sell authentic products. Any manufacturer or store warranty details appear on the product page. Keep your invoice for any claim.', 'sort' => 1],
            ['slug' => 'products-warranty', 'q' => 'Can I check the product before paying?', 'a' => 'Yes. With Cash on Delivery you can check the parcel in front of the delivery rider before paying. If anything is wrong, contact support with your Order ID.', 'sort' => 2],
            ['slug' => 'promotions-discounts', 'q' => 'How do flash sales work?', 'a' => 'Flash sale prices are time-limited and shown on the product card while the deal is active. Stock is limited — once it ends, the regular price returns.', 'sort' => 1],
            ['slug' => 'account-security', 'q' => 'Do I need an account to order?', 'a' => 'You can browse without an account. Creating an account helps you track orders faster and save delivery details for next time.', 'sort' => 1],
            ['slug' => 'others', 'q' => 'Can you help me choose a product?', 'a' => 'Absolutely. Tell us what you need and your budget via WhatsApp or the contact form — we will recommend suitable products in stock.', 'sort' => 1],
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
