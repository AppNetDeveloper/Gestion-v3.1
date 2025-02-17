<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'prompt', 'status', 'campaign_start', 'model'];

    public function details()
    {
        return $this->hasMany(CampaignDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
