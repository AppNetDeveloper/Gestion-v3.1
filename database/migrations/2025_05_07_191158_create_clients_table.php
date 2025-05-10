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
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // Añadir la columna user_id aquí
            $table->foreignId('user_id')
                  ->nullable()
                  // ->after('id') // Opcional, y puede causar problemas en algunas DBs con createTable.
                                 // Laravel lo añadirá después de id si es la siguiente definición.
                  ->constrained('users') // Asume que tu tabla de usuarios se llama 'users'
                  ->onDelete('set null'); // Si el usuario se elimina, user_id en clients se pone a NULL

            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('vat_number')->nullable()->unique();
            // El campo vat_rate se añadirá en una migración posterior separada
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Al hacer dropIfExists, las claves foráneas se eliminan con la tabla.
        // Si quisieras ser más explícito o solo eliminar la FK en un Schema::table:
        // Schema::table('clients', function (Blueprint $table) {
        //     if (Schema::hasColumn('clients', 'user_id')) {
        //         // El nombre de la FK por defecto sería 'clients_user_id_foreign'
        //         $table->dropForeign(['user_id']);
        //     }
        // });
        Schema::dropIfExists('clients');
    }
};
