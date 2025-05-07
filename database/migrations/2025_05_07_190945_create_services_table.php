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
        Schema::create('services', function (Blueprint $table) {
            $table->id(); // Columna ID autoincremental y clave primaria
            $table->string('name'); // Nombre del servicio, ej: "Hora de programación"
            $table->text('description')->nullable(); // Descripción detallada del servicio (opcional)
            $table->decimal('default_price', 8, 2); // Precio estándar del servicio, ej: 50.00 (8 dígitos en total, 2 decimales)
            $table->string('unit')->nullable(); // Unidad de medida, ej: "hora", "unidad", "proyecto" (opcional)
            $table->timestamps(); // Columnas created_at y updated_at automáticamente
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
};
