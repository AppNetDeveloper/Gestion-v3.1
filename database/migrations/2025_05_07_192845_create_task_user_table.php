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
        Schema::create('task_user', function (Blueprint $table) {
            // Clave primaria compuesta para la tabla pivote (opcional, pero buena práctica)
            // $table->primary(['task_id', 'user_id']); // Descomentar si quieres una clave primaria explícita

            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            // Si se elimina una tarea, se eliminan sus asignaciones a usuarios.

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Si se elimina un usuario, se eliminan sus asignaciones a tareas.
            // Asegúrate de que tienes una tabla 'users'. Laravel la incluye por defecto.

            // Puedes añadir campos adicionales a la tabla pivote si es necesario,
            // por ejemplo, para roles específicos en la tarea, o notas sobre la asignación.
            // $table->string('role_in_task')->nullable();

            $table->timestamps(); // Opcional para tablas pivote, pero puede ser útil para rastrear cuándo se hizo la asignación.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_user');
    }
};
