<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal', // <-- THIS IS THE MISSING MAGIC WORD!
    ];

    // Allow OrderItem to find its parent Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Allow OrderItem to fetch the Product name and details
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function soldImeis()
    {
        return $this->hasMany(ProductImei::class, 'order_item_id')
            ->where('status', ProductImei::STATUS_SOLD)
            ->orderBy('id');
    }

    /**
     * Spec / IMEI lines for the printed receipt.
     *
     * @return array<int, string>
     */
    public function receiptDetailLines(): array
    {
        $product = $this->product;
        if (! $product) {
            return [];
        }

        $lines = [];
        foreach ($product->receiptSpecLines() as $spec) {
            $lines[] = $spec['label'].': '.$spec['value'];
        }

        $imeis = $this->relationLoaded('soldImeis')
            ? $this->soldImeis
            : $this->soldImeis()->get();

        foreach ($imeis as $row) {
            $lines[] = 'IMEI: '.$row->imei;
        }

        return $lines;
    }
}