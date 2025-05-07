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
        Schema::create('projects', function (Blueprint $table) {
            $table->id(); // ID del proyecto
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade'); // Relación con la tabla clients
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->onDelete('set null'); // Relación opcional con el presupuesto que lo originó.

            $table->string('project_title'); // Título o nombre del proyecto
            $table->text('description')->nullable(); // Descripción detallada del proyecto

            $table->date('start_date')->nullable(); // Fecha de inicio prevista/real
            $table->date('due_date')->nullable(); // Fecha de entrega prevista
            $table->date('completion_date')->nullable(); // Fecha de finalización real

            $table->enum('status', [
                'pending',          // Pendiente de iniciar
                'in_progress',      // En progreso
                'on_hold',          // En espera (ej. esperando info del cliente)
                'completed',        // Completado
                'cancelled',        // Cancelado
                'archived'          // Archivado (para proyectos antiguos)
            ])->default('pending'); // Estado del proyecto

            $table->decimal('budgeted_hours', 8, 2)->nullable(); // Horas presupuestadas (si aplica)
            $table->decimal('actual_hours', 8, 2)->nullable(); // Horas reales invertidas (se podría calcular sumando horas de tareas)

            $table->text('internal_notes')->nullable(); // Notas internas sobre el proyecto

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
        Schema::dropIfExists('projects');
    }
};
