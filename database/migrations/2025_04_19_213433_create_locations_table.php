<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id(); // Primary key auto-incremental (ID de la ubicación)

            // Clave foránea para el usuario (relacionado con tid)
            // Asegúrate de que tu tabla 'users' use bigIncrements o id() para su clave primaria.
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // --- Campos principales de ubicación y tiempo ---
            $table->decimal('latitude', 10, 7); // Precisión adecuada para latitud
            $table->decimal('longitude', 10, 7); // Precisión adecuada para longitud
            $table->timestamp('recorded_at'); // El timestamp 'tst' (cuándo se tomó la lectura) - ¡IMPORTANTE!
            $table->integer('accuracy')->nullable(); // 'acc' - Precisión en metros
            $table->integer('altitude')->nullable(); // 'alt' - Altitud en metros
            $table->integer('velocity')->nullable(); // 'vel' - Velocidad (asumimos km/h)
            $table->smallInteger('course')->nullable(); // 'cog' - Curso/Dirección en grados (0-359)
            $table->integer('vertical_accuracy')->nullable(); // 'vac' - Precisión vertical en metros

            // --- Campos de contexto y dispositivo ---
            $table->unsignedTinyInteger('battery_level')->nullable(); // 'batt' - Nivel de batería (%)
            $table->unsignedTinyInteger('battery_status')->nullable(); // 'bs' - Estado de la batería (interpretar según OwnTracks)
            $table->char('connection_type', 1)->nullable(); // 'conn' - Tipo de conexión (w, m, o)
            $table->string('ssid')->nullable(); // 'SSID' - Nombre de la red WiFi
            $table->string('bssid')->nullable(); // 'BSSID' - MAC del punto de acceso WiFi
            $table->char('trigger_type', 1)->nullable(); // 't' - Tipo de trigger (p, c, b, etc.)

            // --- Campos de metadatos del mensaje OwnTracks ---
            $table->string('type')->nullable(); // '_type' (ej: 'location') - Renombrado para evitar conflictos
            $table->string('owntracks_message_id')->nullable()->index(); // '_id' - ID del mensaje OwnTracks (renombrado)
            $table->timestamp('message_created_at')->nullable(); // 'created_at' del JSON (cuándo se creó/recibió el mensaje)
            $table->tinyInteger('monitoring_mode')->nullable(); // 'm' - Modo de monitorización (interpretar según OwnTracks)

            // Timestamps estándar de Laravel (created_at, updated_at para el registro en DB)
            $table->timestamps();

            // --- Índices adicionales para optimizar consultas ---
            // Ya tenemos índice en user_id por la foreign key
            $table->index('recorded_at'); // Para buscar por fecha/hora rápidamente
            $table->index(['user_id', 'recorded_at']); // Para buscar ubicaciones de un usuario en un rango de tiempo
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
};
