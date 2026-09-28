<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'email' => 'shop@example.com',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        SiteSetting::create([
            'default_shop_id' => $shop->id,
            'store_name' => 'GAGET STORE',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
