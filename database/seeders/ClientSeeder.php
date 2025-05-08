<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client; // Asegúrate de importar tu modelo Client

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Client::create([
            'name' => 'Empresa Ejemplo Uno S.L.',
            'email' => 'contacto@empresauno.es',
            'phone' => '912345678',
            'vat_number' => 'B12345678',
            'vat_rate' => 21.00, // <-- Campo añadido
            'address' => 'Calle Falsa 123, Oficina A',
            'city' => 'Madrid',
            'postal_code' => '28001',
            'country' => 'España',
        ]);

        Client::create([
            'name' => 'Juan Pérez Autónomo',
            'email' => 'juan.perez@autonomo.es',
            'phone' => '600123456',
            'vat_number' => '12345678Z',
            'vat_rate' => 21.00, // <-- Campo añadido
            'address' => 'Avenida Principal 45, Bajo',
            'city' => 'Barcelona',
            'postal_code' => '08001',
            'country' => 'España',
        ]);

        Client::create([
            'name' => 'Tech Solutions Global',
            'email' => 'info@techglobalsolutions.com',
            'phone' => '933219876',
            'vat_rate' => 0.00, // <-- Ejemplo con IVA 0 (quizás intracomunitario o exento)
            // vat_number, address, etc. pueden ser nullables si así los definiste
        ]);
    }
}
