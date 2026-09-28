<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    use HasFactory;

    // This allows us to mass-assign all fields from the controller
    protected $guarded = [];

    // --- Relationships ---

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function returnProduct()
    {
        return $this->belongsTo(Product::class, 'return_product_id');
    }

    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }
}