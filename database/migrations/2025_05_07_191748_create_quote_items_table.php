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
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id(); // ID del ítem del presupuesto
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade'); // Relación con la tabla quotes. Si se borra un presupuesto, se borran sus ítems.
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null'); // Relación con la tabla services. Si se borra el servicio, el ítem permanece pero sin servicio_id.

            $table->string('item_description'); // Descripción del servicio/producto (puede tomarse del servicio o ser personalizada)
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Precio unitario (puede tomarse del servicio o ser personalizado)
            $table->decimal('item_subtotal', 10, 2); // Subtotal del ítem (quantity * unit_price)

            // Campos para descuentos a nivel de línea (opcional pero recomendado)
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null'); // Si se aplica un descuento específico de la tabla discounts
            $table->decimal('line_discount_percentage', 5, 2)->nullable(); // Porcentaje de descuento aplicado a esta línea
            $table->decimal('line_discount_amount', 10, 2)->nullable(); // Cantidad fija de descuento aplicada a esta línea

            $table->decimal('line_total', 10, 2); // Total de la línea después de descuentos (item_subtotal - descuentos de línea)

            // Podrías añadir campos para impuestos a nivel de línea si es necesario
            // $table->decimal('line_tax_rate', 5, 2)->nullable();
            // $table->decimal('line_tax_amount', 10, 2)->nullable();

            $table->integer('sort_order')->default(0); // Para ordenar los ítems dentro del presupuesto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_items');
    }
};
