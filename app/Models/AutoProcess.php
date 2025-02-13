<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoProcess extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'auto_processes';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'whatsapp',
        'whatsapp_prompt',
        'telegram',
        'telegram_prompt',
        'email',
        'email_prompt',
    ];

    /**
     * Obtiene el usuario dueño de esta configuración.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
