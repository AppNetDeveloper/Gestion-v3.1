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
        // Cambiamos el nombre de la tabla a 'task_time_history'
        Schema::create('task_time_history', function (Blueprint $table) {
            $table->id(); // ID del registro de historial de tiempo

            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            // Tarea a la que se dedica este tiempo. Si se borra la tarea, se borran sus registros de tiempo.

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Usuario que registra el tiempo. Si se borra el usuario, se borran sus registros de tiempo.

            $table->timestamp('start_time'); // Momento exacto de inicio del trabajo en la tarea
            $table->timestamp('end_time')->nullable(); // Momento exacto de fin del trabajo en la tarea (puede ser null si está en curso)

            // Duración en minutos. Se puede calcular desde start_time y end_time,
            // pero almacenarlo puede facilitar las consultas.
            // Si end_time es null, duration también debería serlo o 0.
            $table->integer('duration_minutes')->nullable();

            $table->date('log_date'); // Fecha del registro (para facilitar búsquedas por día)

            $table->text('description')->nullable(); // Descripción breve del trabajo realizado durante este intervalo

            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Asegúrate de que el nombre coincida aquí también
        Schema::dropIfExists('task_time_history');
    }
};
