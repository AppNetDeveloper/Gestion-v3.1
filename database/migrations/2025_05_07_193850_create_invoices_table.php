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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // ID de la factura
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade'); // Cliente al que se factura
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->onDelete('set null'); // Presupuesto original (opcional, si la factura no viene de un presupuesto)
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null'); // Proyecto asociado (opcional)

            $table->string('invoice_number')->unique(); // Número de factura (debe ser único y seguir una secuencia legal)
            $table->date('invoice_date'); // Fecha de emisión de la factura
            $table->date('due_date')->nullable(); // Fecha de vencimiento del pago

            $table->enum('status', [
                'draft',        // Borrador
                'sent',         // Enviada al cliente
                'paid',         // Pagada
                'partially_paid',// Pagada parcialmente
                'overdue',      // Vencida
                'cancelled',    // Anulada
                'refunded'      // Reembolsada (si aplica)
            ])->default('draft');

            $table->decimal('subtotal', 10, 2)->default(0.00); // Suma de los precios de los items antes de descuentos e impuestos
            $table->decimal('discount_amount', 10, 2)->default(0.00); // Descuento total aplicado a la factura
            $table->decimal('tax_amount', 10, 2)->default(0.00); // Impuestos totales (ej. IVA)
            $table->decimal('total_amount', 10, 2)->default(0.00); // Importe total final

            $table->string('currency', 3)->default('EUR'); // Moneda (ej. EUR, USD)

            $table->text('payment_terms')->nullable(); // Términos de pago
            $table->text('notes_to_client')->nullable(); // Notas para el cliente en la factura
            $table->text('internal_notes')->nullable(); // Notas internas

            // Campos para cumplir con Veri*factu / TicketBAI (simplificado, necesitarás más detalle según la normativa)
            $table->string('verifactu_id')->nullable()->unique(); // Identificador único del registro Veri*factu
            $table->text('verifactu_qr_code_data')->nullable(); // Datos para generar el QR

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
        Schema::dropIfExists('invoices');
    }
};
