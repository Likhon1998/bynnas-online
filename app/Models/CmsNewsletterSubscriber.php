<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsNewsletterSubscriber extends Model
{
    protected $table = 'cms_newsletter_subscribers';

    protected $fillable = ['shop_id', 'email'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
