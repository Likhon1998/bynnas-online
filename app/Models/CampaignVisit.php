<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignVisit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['campaign_id', 'visitor_hash', 'utm_content', 'landing_path', 'referrer_host'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
