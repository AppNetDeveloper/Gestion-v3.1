<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration // Usar la sintaxis de clase anónima moderna
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('host_lists', function (Blueprint $table) {
            $table->id();
            $table->string('host')->unique(); // Dirección IP o nombre de host, debe ser único
            $table->string('token')->unique(); // Token único para cada host
            $table->string('name');          // Nombre descriptivo del host

            // Columna para user_id, nullable.
            // Se elimina ->after('name') para evitar problemas de sintaxis con algunas versiones de MariaDB.
            // Laravel añadirá esta columna después de 'name' si es la siguiente definición lógica sin 'after'.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            // ->constrained('users') asume que tu tabla de usuarios se llama 'users'.
            // ->onDelete('set null') significa que si el usuario se elimina, user_id en esta tabla se pondrá a NULL.
            // Puedes cambiarlo a ->onDelete('cascade') si quieres que se eliminen los host_lists asociados.

            $table->timestamps(); // Esto añadirá created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('host_lists', function (Blueprint $table) {
            // Es buena práctica eliminar las claves foráneas antes de eliminar la tabla,
            // especialmente si no se usa dropIfExists directamente.
            // Laravel < 8: $table->dropForeign(['user_id']);
            // Laravel 8+: Intenta eliminar la restricción por su nombre convencional si constrained() lo creó así.
            // O, de forma más explícita si conoces el nombre de la restricción:
            // $table->dropForeign('host_lists_user_id_foreign');
            // Si usaste foreignId()->constrained(), Laravel suele manejar esto bien con dropIfExists.
            // Sin embargo, para ser explícitos y seguros, podemos intentar eliminarla.
            // Si la tabla se elimina con dropIfExists, las FKs también se van.
            // Pero si solo se hace dropColumn, la FK debe eliminarse primero.
            // En este caso, como hacemos dropIfExists abajo, este paso es más una formalidad
            // o para casos donde solo se quiera eliminar la FK.
            if (Schema::hasColumn('host_lists', 'user_id')) { // Verificar si la columna existe
                // Intentar eliminar la FK si existe. El nombre puede variar.
                // Laravel suele nombrar las FKs como: nombretabla_nombrecolumna_foreign
                try {
                    $table->dropForeign(['user_id']); // Esto busca una FK llamada host_lists_user_id_foreign
                } catch (\Exception $e) {
                    // Puede fallar si el nombre de la FK es diferente o ya no existe.
                    // No es crítico si dropIfExists se usa después.
                    // Log::warning("Could not drop foreign key for user_id on host_lists: " . $e->getMessage());
                }
            }
        });
        Schema::dropIfExists('host_lists');
    }
};
