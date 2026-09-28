<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('selling_price');
            $table->timestamp('sale_starts_at')->nullable()->after('sale_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
        });

        // Legacy compare-at: original_price was list, selling_price was the deal price.
        $now = now();
        $ends = $now->copy()->addDays(30);

        DB::table('products')
            ->whereNotNull('original_price')
            ->whereColumn('original_price', '>', 'selling_price')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($now, $ends) {
                foreach ($rows as $row) {
                    DB::table('products')->where('id', $row->id)->update([
                        'sale_price' => $row->selling_price,
                        'selling_price' => $row->original_price,
                        'sale_starts_at' => $now,
                        'sale_ends_at' => $ends,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sale_price', 'sale_starts_at', 'sale_ends_at']);
        });
    }
};
