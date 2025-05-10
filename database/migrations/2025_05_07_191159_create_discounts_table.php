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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id(); // Columna ID autoincremental y clave primaria
            $table->string('name'); // Nombre descriptivo del descuento, ej: "Descuento VIP"
            $table->text('description')->nullable(); // Descripción más detallada (opcional)

            // Tipo de descuento: 'percentage' (porcentaje) o 'fixed_amount' (cantidad fija)
            $table->enum('type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('value', 8, 2); // Valor del descuento (ej: 10.00 para 10% o 5.00 para 5€)

            // Relaciones opcionales
            // Clave foránea para services
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');

            // Clave foránea para clients (definición más explícita)
            $table->unsignedBigInteger('client_id')->nullable(); // Columna para la FK
            $table->foreign('client_id')
                  ->references('id')->on('clients') // Referencia a la tabla clients, columna id
                  ->onDelete('set null'); // Acción en caso de borrado del cliente

            $table->date('start_date')->nullable(); // Fecha de inicio de validez del descuento
            $table->date('end_date')->nullable();   // Fecha de fin de validez del descuento

            $table->boolean('is_active')->default(true); // Para activar o desactivar el descuento

            $table->integer('minimum_quantity')->nullable(); // Cantidad mínima de ítems para aplicar (opcional)
            $table->decimal('minimum_purchase_amount', 8, 2)->nullable(); // Importe mínimo de compra para aplicar (opcional)

            $table->string('code')->nullable()->unique(); // Código promocional único para aplicar el descuento (opcional)
            $table->integer('usage_limit')->nullable(); // Límite de veces que se puede usar el descuento (opcional)
            $table->integer('used_count')->default(0); // Contador de veces que se ha usado (si hay límite)

            $table->timestamps(); // Columnas created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Al revertir, primero eliminar la clave foránea si es necesario, luego la tabla
        Schema::table('discounts', function (Blueprint $table) {
            // Verificar si la restricción existe antes de intentar eliminarla
            // El nombre de la restricción por defecto sería 'discounts_client_id_foreign'
            // o puedes obtenerlo de information_schema si es necesario.
            // Por simplicidad, intentamos eliminarla. Si no existe, no debería dar error grave.
            if (Schema::hasColumn('discounts', 'client_id')) { // Asegurar que la columna existe
                try {
                    $table->dropForeign(['client_id']); // Laravel intenta encontrar la FK por el nombre de la columna
                } catch (\Exception $e) {
                    // No hacer nada si la FK no existe o hay otro problema al eliminarla
                }
            }
        });
        Schema::dropIfExists('discounts');
    }
};
