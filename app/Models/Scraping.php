<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scraping extends Model
{
    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'keywords',
        'status',
        'linkedin_username',
        'linkedin_password',
        'tasker_id'
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Constantes para los estados de scraping
     */
    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_ERROR = 2;

    /**
     * Obtiene el usuario propietario de esta tarea de scraping.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene los contactos asociados a esta tarea de scraping.
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_scraping');
    }
}
