<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ShopScoped
{
    protected function authorizeShop(Model $model): void
    {
        if ($model->shop_id !== auth()->user()->shop_id) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function shopId(): int
    {
        return auth()->user()->shop_id;
    }
}
