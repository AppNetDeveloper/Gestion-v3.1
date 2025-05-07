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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id(); // ID del ítem de la factura
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade'); // Factura a la que pertenece el ítem. Si se borra la factura, se borran sus ítems.
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null'); // Servicio facturado (opcional, si es un producto/servicio no catalogado directamente)

            // Podrías tener una relación con quote_items_id si quieres trazar exactamente qué línea del presupuesto se está facturando.
            // $table->foreignId('quote_item_id')->nullable()->constrained('quote_items')->onDelete('set null');

            $table->string('item_description'); // Descripción del servicio/producto facturado
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Precio unitario
            $table->decimal('item_subtotal', 10, 2); // Subtotal del ítem (quantity * unit_price)

            // Descuentos a nivel de línea (opcional)
            $table->decimal('line_discount_percentage', 5, 2)->nullable(); // Porcentaje de descuento
            $table->decimal('line_discount_amount', 10, 2)->nullable(); // Cantidad fija de descuento

            // Impuestos a nivel de línea (muy importante para el desglose del IVA)
            $table->decimal('tax_rate', 5, 2)->default(0.00); // Tasa de impuesto (ej. 21.00 para 21%)
            $table->decimal('tax_amount_per_item', 10, 2)->default(0.00); // Impuesto por unidad después de descuento
            $table->decimal('line_tax_total', 10, 2)->default(0.00); // Impuesto total para esta línea (tax_amount_per_item * quantity)

            $table->decimal('line_total', 10, 2); // Total de la línea (item_subtotal - descuentos + impuestos de línea)

            $table->integer('sort_order')->default(0); // Para ordenar los ítems dentro de la factura
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
        Schema::dropIfExists('invoice_items');
    }
};
