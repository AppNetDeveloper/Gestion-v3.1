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
        Schema::table('clients', function (Blueprint $table) {
            // Añadir columna para la tasa de IVA aplicable al cliente
            // Puede ser NULL si se aplica la tasa por defecto de la app
            // La ponemos después de 'vat_number' por organización
            $table->decimal('vat_rate', 5, 2)->nullable()->default(21.00)->after('vat_number');
            // default(21.00) establece el IVA estándar español por defecto. Ajusta si es necesario.
            // ->nullable() permite que clientes antiguos o sin IVA específico no tengan valor.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Eliminar la columna si revertimos la migración
            $table->dropColumn('vat_rate');
        });
    }
};
