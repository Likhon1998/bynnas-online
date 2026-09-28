<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsContactMessage extends Model
{
    protected $table = 'cms_contact_messages';

    protected $fillable = [
        'shop_id', 'name', 'email', 'subject', 'order_number', 'message', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
