<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// Para un modelo de tabla pivote más avanzado, podrías extender Pivot:
// use Illuminate\Database\Eloquent\Relations\Pivot;
// class TaskUser extends Pivot
use Illuminate\Database\Eloquent\Model; // Usaremos Model por ahora para simplicidad

class TaskUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_user'; // Nombre explícito de la tabla pivote

    /**
     * Indicates if the model should be timestamped.
     * Laravel maneja los timestamps en la tabla pivote si los definiste en la migración
     * y si usas los métodos de Eloquent para adjuntar/sincronizar (attach/sync).
     * Si quieres que este modelo los gestione explícitamente, déjalo en true.
     * Si la tabla pivote no tiene timestamps, ponlo a false.
     *
     * @var bool
     */
    public $timestamps = true; // Asumiendo que tu tabla pivote 'task_user' tiene timestamps

    /**
     * The attributes that are mass assignable.
     * Generalmente, para una tabla pivote simple, no necesitas $fillable aquí
     * a menos que tengas columnas adicionales en la tabla pivote que quieras rellenar masivamente
     * a través de este modelo.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        // 'role_in_task', // Ejemplo si tuvieras una columna adicional
    ];

    /**
     * Define la relación con el modelo Task.
     * Esto es opcional si solo usas las relaciones BelongsToMany en Task y User.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Define la relación con el modelo User.
     * Esto es opcional si solo usas las relaciones BelongsToMany en Task y User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
