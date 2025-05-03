<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importar BelongsToMany
use App\Models\WhatsappMessage; // Importar WhatsappMessage
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importar BelongsTo

class Contact extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'email',
        'web',
        'telegram'
    ];

    /**
     * Obtiene los mensajes de WhatsApp asociados a este contacto por número de teléfono y usuario.
     */
    public function whatsappMessages()
    {
        // Asegúrate de que las columnas 'phone' en ambas tablas y 'user_id' sean correctas
        return $this->hasMany(WhatsappMessage::class, 'phone', 'phone')
                    ->where('user_id', $this->user_id); // Asume que WhatsappMessage también tiene user_id
    }

    /**
     * Obtiene las tareas de scraping que encontraron este contacto.
     * Define la relación muchos-a-muchos a través de la tabla pivot 'contact_scraping_task'.
     */
    public function scrapingTasks(): BelongsToMany
    {
        // Laravel infiere los nombres de las claves foráneas y la tabla pivot
        // si seguimos las convenciones.
        return $this->belongsToMany(ScrapingTask::class);
    }

    /**
     * Obtiene el usuario propietario de este contacto.
     */
    public function user(): BelongsTo
    {
         return $this->belongsTo(User::class);
    }

}
