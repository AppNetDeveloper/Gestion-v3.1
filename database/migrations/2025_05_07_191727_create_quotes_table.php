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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id(); // ID del presupuesto
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade'); // Relación con la tabla clients. Si se borra un cliente, se borran sus presupuestos.
            $table->string('quote_number')->unique(); // Número de presupuesto único (ej: PRE-2024-0001)
            $table->date('quote_date'); // Fecha de emisión del presupuesto
            $table->date('expiry_date')->nullable(); // Fecha de validez del presupuesto (opcional)
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'invoiced'])->default('draft'); // Estado del presupuesto

            $table->decimal('subtotal', 10, 2)->default(0.00); // Suma de los precios de los items antes de descuentos e impuestos
            $table->decimal('discount_amount', 10, 2)->default(0.00); // Descuento total aplicado al presupuesto (si hay un descuento global)
            $table->decimal('tax_amount', 10, 2)->default(0.00); // Impuestos totales
            $table->decimal('total_amount', 10, 2)->default(0.00); // Importe total final (subtotal - descuento + impuestos)

            $table->text('terms_and_conditions')->nullable(); // Términos y condiciones del presupuesto
            $table->text('notes_to_client')->nullable(); // Notas para el cliente
            $table->text('internal_notes')->nullable(); // Notas internas (no visibles para el cliente)

            // Opcional: Si un descuento general se aplica al presupuesto completo
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null');

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
        Schema::dropIfExists('quotes');
    }
};
