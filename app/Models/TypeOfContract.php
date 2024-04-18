<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeOfContract extends Model
{
    // Nombre de la tabla en la base de datos
    protected $table = 'type_of_contract';

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
