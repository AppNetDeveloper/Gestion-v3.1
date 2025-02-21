<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'phone', 'address', 'email', 'web', 'telegram'];

    public function whatsappMessages()
    {
        return $this->hasMany(WhatsappMessage::class, 'phone', 'phone')
                    ->where('user_id', $this->user_id);
    }

}
