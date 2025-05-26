<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OllamaTasker extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model',
        'prompt',
        'response',
        'error',
        'callback_url',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // Add any hidden attributes here
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        // Add any accessors here
    ];

    /**
     * (Opcional) Si deseas especificar la tabla de forma explícita.
     * Por defecto, Laravel asume el nombre plural del modelo.
     *
     * @var string
     */
    // protected $table = 'ollama_taskers';

    /**
     * (Opcional) Si deseas definir el nombre de la clave primaria.
     * Por defecto es 'id'.
     *
     * @var string
     */
    // protected $primaryKey = 'id';

    /**
     * (Opcional) Si los campos 'created_at' y 'updated_at' no se usan.
     *
     * @var bool
     */
    // public $timestamps = true;
}
