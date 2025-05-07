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

            // Relaciones opcionales (un descuento puede ser general o específico)
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');

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
        Schema::dropIfExists('discounts');
    }
};
