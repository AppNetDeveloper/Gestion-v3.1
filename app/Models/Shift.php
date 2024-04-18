<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shift';

    // Campos de la tabla
    protected $fillable = [
        'name',
    ];

    // Atributos
    public function getNameAttribute($value)
    {
        return ucfirst($value); // Convertir el nombre a mayúscula inicial
    }

}
