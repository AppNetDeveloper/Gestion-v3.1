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
        Schema::table('contacts', function (Blueprint $table) {
            // Cambiar el tipo de la columna 'web' a TEXT para permitir URLs largas
            // El método change() requiere que instales el paquete doctrine/dbal:
            // composer require doctrine/dbal
            $table->text('web')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Volver al tipo string (VARCHAR 255) si haces rollback
            // ¡CUIDADO! Esto puede truncar datos si ya hay URLs largas guardadas.
            $table->string('web')->nullable()->change();
        });
    }
};
