<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropScrapingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Primero eliminamos la tabla pivot
        Schema::dropIfExists('contact_scraping_task');
        
        // Luego eliminamos la tabla principal
        Schema::dropIfExists('scraping_tasks');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No recreamos las tablas en caso de rollback
        // Si se necesita recrear, se deben usar las migraciones originales
    }
}
