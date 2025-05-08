<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount; // Asegúrate de importar tu modelo Discount
use App\Models\Client;   // Para descuentos específicos de cliente
use App\Models\Service;  // Para descuentos específicos de servicio

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Discount::create([
            'name' => 'Descuento Bienvenida 10%',
            'description' => '10% de descuento para nuevos clientes en su primer servicio.',
            'type' => 'percentage',
            'value' => 10.00,
            'is_active' => true,
            // 'start_date' => now(), // Opcional
            // 'end_date' => now()->addMonths(3), // Opcional
        ]);

        Discount::create([
            'name' => 'Descuento Fijo Verano',
            'description' => '5€ de descuento en servicios seleccionados durante el verano.',
            'type' => 'fixed_amount',
            'value' => 5.00,
            'is_active' => true,
        ]);

        // Ejemplo de descuento específico para un cliente (asumiendo que el cliente con ID 1 existe)
        $client1 = Client::find(1);
        if ($client1) {
            Discount::create([
                'name' => 'Descuento VIP Cliente 1',
                'client_id' => $client1->id,
                'type' => 'percentage',
                'value' => 15.00,
                'is_active' => true,
            ]);
        }

        // Ejemplo de descuento específico para un servicio (asumiendo que el servicio con ID 1 existe)
        $service1 = Service::find(1);
        if ($service1) {
            Discount::create([
                'name' => 'Oferta Especial Programación PHP',
                'service_id' => $service1->id,
                'type' => 'percentage',
                'value' => 5.00,
                'is_active' => true,
                'code' => 'PHPPROMO', // Ejemplo de código promocional
            ]);
        }
    }
}
