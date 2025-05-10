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
    public function up(): void
    {
        Schema::create('project_user', function (Blueprint $table) {
            // Clave primaria compuesta para la tabla pivote (buena práctica)
            // Esto asegura que un usuario no pueda ser asignado dos veces al mismo proyecto.
            $table->primary(['project_id', 'user_id']);

            // Clave foránea para la tabla 'projects'
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            // onDelete('cascade') significa que si un proyecto se elimina,
            // todas sus asignaciones a usuarios en esta tabla también se eliminarán.

            // Clave foránea para la tabla 'users'
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // onDelete('cascade') significa que si un usuario se elimina,
            // todas sus asignaciones a proyectos en esta tabla también se eliminarán.

            // No necesitamos timestamps para esta tabla pivote simple,
            // a menos que quieras rastrear cuándo se hizo la asignación.
            // Si los necesitas, descomenta la siguiente línea:
            // $table->timestamps();

            // Aquí podrías añadir campos adicionales a la relación si fuera necesario,
            // por ejemplo, el rol del usuario dentro de ese proyecto específico:
            // $table->string('role_in_project')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
