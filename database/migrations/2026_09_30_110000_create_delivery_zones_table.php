<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 40);
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('note', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'is_active', 'sort_order']);
        });

        // Carry over the two legacy zones (and their current fees) for every shop.
        $settings = Schema::hasTable('site_settings') ? DB::table('site_settings')->first() : null;
        $inside = (float) ($settings->delivery_inside_dhaka ?? 60);
        $outside = (float) ($settings->delivery_outside_dhaka ?? 120);
        $now = now();

        foreach (DB::table('shops')->pluck('id') as $shopId) {
            DB::table('delivery_zones')->insert([
                ['shop_id' => $shopId, 'name' => 'Inside Dhaka', 'code' => 'inside_dhaka', 'fee' => $inside, 'is_active' => true, 'is_default' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['shop_id' => $shopId, 'name' => 'Outside Dhaka', 'code' => 'outside_dhaka', 'fee' => $outside, 'is_active' => true, 'is_default' => false, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
