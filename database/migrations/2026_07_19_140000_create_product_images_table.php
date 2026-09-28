<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // Move existing image / image_2 / image_3 into gallery rows.
        $products = DB::table('products')
            ->select('id', 'image', 'image_2', 'image_3')
            ->get();

        foreach ($products as $product) {
            $order = 0;
            foreach (['image', 'image_2', 'image_3'] as $column) {
                $path = $product->{$column} ?? null;
                if (! $path) {
                    continue;
                }

                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'path' => $path,
                    'sort_order' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
