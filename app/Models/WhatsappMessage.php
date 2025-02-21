<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['user_id', 'phone', 'message', 'status', 'image'];

    // Relación opcional con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
