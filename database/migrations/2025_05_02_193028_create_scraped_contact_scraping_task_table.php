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
        // Tabla pivot para la relación muchos-a-muchos entre scraping_tasks y contacts
        // Nombre sigue convención: modelos en singular, orden alfabético.
        Schema::create('contact_scraping_task', function (Blueprint $table) {
            // Clave foránea para la tarea de scraping
            $table->foreignId('scraping_task_id')
                  ->constrained('scraping_tasks') // Asegúrate que la tabla se llama 'scraping_tasks'
                  ->onDelete('cascade'); // Si se borra la tarea, se borra la relación

            // Clave foránea para el contacto principal
            // *** CORREGIDO: Apunta a la tabla 'contacts' y usa 'contact_id' ***
            $table->foreignId('contact_id')
                  ->constrained('contacts') // Referencia a tu tabla principal de contactos
                  ->onDelete('cascade'); // Si se borra el contacto, se borra la relación

            // Definir la clave primaria compuesta para evitar duplicados
             // *** CORREGIDO: Usa 'contact_id' ***
            $table->primary(['scraping_task_id', 'contact_id']);

            // Opcional: Timestamps si quieres saber cuándo se creó la relación
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         // *** CORREGIDO: Nombre de la tabla pivot ***
        Schema::dropIfExists('contact_scraping_task');
    }
};
