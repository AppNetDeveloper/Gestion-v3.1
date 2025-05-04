<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaborCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('labor_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('title');      // Por ejemplo, "Día festivo" o "Sábado sin trabajar"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('auto_generated')->default(false); // Indica si se generó automáticamente
            $table->boolean('is_holiday')->default(false);      // Indica si es un día festivo
            $table->text('description')->nullable();            // Opcional: detalle del día festivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labor_calendars');
    }
}
