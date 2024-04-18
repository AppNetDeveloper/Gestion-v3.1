<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosicion extends Model
{
    use HasFactory;
    protected $table = 'job_posicion';

    // Campos de la tabla
    protected $fillable = [
        'name',
    ];

    // Atributos
    public function getNameAttribute($value)
    {
        return ucfirst($value); // Convertir el nombre a mayúscula inicial
    }

    // Relaciones
    // ... Puedes agregar aquí las relaciones con otras entidades.
}
