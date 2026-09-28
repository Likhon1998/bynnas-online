<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_logout_clears_session_and_redirects_home(): void
    {
        Role::findOrCreate('Customer');

        $user = User::factory()->create();
        $user->assignRole('Customer');

        $response = $this->actingAs($user)->post(route('website.account.logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_guest_can_post_logout_and_still_land_on_home(): void
    {
        $response = $this->post(route('website.account.logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }
}
