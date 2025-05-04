<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * The attributes that are mass assignable.
     *
     * ¡Importante! Incluye aquí todos los campos que vas a rellenar
     * directamente desde el array de datos de OwnTracks.
     * No incluyas 'id', 'created_at', 'updated_at' (gestionados por Eloquent).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'recorded_at', // Corresponde a 'tst'
        'accuracy', // 'acc'
        'altitude', // 'alt'
        'velocity', // 'vel'
        'course', // 'cog'
        'vertical_accuracy', // 'vac'
        'battery_level', // 'batt'
        'battery_status', // 'bs'
        'connection_type', // 'conn'
        'ssid', // 'SSID'
        'bssid', // 'BSSID'
        'trigger_type', // 't'
        'type', // '_type'
        'owntracks_message_id', // '_id'
        'message_created_at', // 'created_at' del JSON
        'monitoring_mode', // 'm'
    ];

    /**
     * The attributes that should be cast.
     *
     * Ayuda a Eloquent a tratar los datos correctamente (fechas, números).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:7', // Asegura que se trate como decimal con 7 dígitos
        'longitude' => 'decimal:7',
        'recorded_at' => 'datetime', // Convierte el timestamp a objeto Carbon/DateTime
        'message_created_at' => 'datetime',
        'accuracy' => 'integer',
        'altitude' => 'integer',
        'velocity' => 'integer',
        'course' => 'integer',
        'vertical_accuracy' => 'integer',
        'battery_level' => 'integer',
        'battery_status' => 'integer',
        'monitoring_mode' => 'integer',
    ];

    /**
     * Define la relación inversa "pertenece a" con el modelo User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
