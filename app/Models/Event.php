<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'start_date',
        'end_date',
        'category',
        'video_conferencia',
        'contact_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
