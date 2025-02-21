<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskerLinkedin extends Model
{
    use HasFactory;
    
    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'tasker_linkedins';

    /**
     * Atributos asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'prompt',
        'response',
        'status',
        'error',
        'publish_date'
    ];

    /**
     * Cast de atributos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'publish_date' => 'datetime',
    ];

    /**
     * Relación con el modelo User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
