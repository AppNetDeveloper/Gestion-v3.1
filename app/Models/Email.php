<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'contact_id',
        'subject',
        'date',
        'sender',
        'folder'
    ];

    /**
     * Define la relación con el usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define la relación con el contacto.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
