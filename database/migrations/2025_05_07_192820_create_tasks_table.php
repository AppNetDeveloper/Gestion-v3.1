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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); // ID de la tarea
            // AQUÍ EL CAMBIO IMPORTANTE: project_id en lugar de job_id
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade'); // Relación con la tabla projects. Si se borra un project, se borran sus tareas.

            $table->string('title'); // Título o nombre corto de la tarea
            $table->text('description')->nullable(); // Descripción más detallada de la tarea

            $table->enum('status', [
                'pending',          // Pendiente de iniciar
                'in_progress',      // En progreso
                'completed',        // Completada
                'on_hold',          // En espera
                'cancelled'         // Cancelada
            ])->default('pending'); // Estado de la tarea

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium'); // Prioridad de la tarea

            $table->date('start_date')->nullable(); // Fecha de inicio prevista/real de la tarea
            $table->date('due_date')->nullable(); // Fecha de entrega prevista de la tarea
            $table->date('completed_date')->nullable(); // Fecha de finalización real de la tarea

            $table->decimal('estimated_hours', 8, 2)->nullable(); // Horas estimadas para la tarea
            $table->decimal('logged_hours', 8, 2)->default(0.00); // Horas reales registradas para la tarea

            // Opcional: si una tarea puede depender de otra (para tareas secuenciales)
            // $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('set null');

            $table->integer('sort_order')->default(0); // Para ordenar las tareas dentro de un project

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
        Schema::dropIfExists('tasks');
    }
};
