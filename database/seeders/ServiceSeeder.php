<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service; // Asegúrate de importar tu modelo Service
use Illuminate\Support\Facades\DB; // Opcional, si prefieres usar el Query Builder

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Opción 1: Usando el Modelo Eloquent (recomendado)
        Service::create([
            'name' => 'Hora de Programación PHP',
            'description' => 'Desarrollo y mantenimiento de aplicaciones PHP/Laravel.',
            'default_price' => 50.00,
            'unit' => 'hora',
        ]);

        Service::create([
            'name' => 'Mantenimiento Web Básico',
            'description' => 'Actualizaciones, copias de seguridad y revisión mensual.',
            'default_price' => 75.00,
            'unit' => 'mes',
        ]);

        Service::create([
            'name' => 'Diseño de Logotipo',
            'description' => 'Creación de logotipo corporativo con 2 revisiones.',
            'default_price' => 250.00,
            'unit' => 'proyecto',
        ]);

        Service::create([
            'name' => 'Consultoría SEO Inicial',
            'description' => 'Análisis y recomendaciones SEO para una web.',
            'default_price' => 150.00,
            'unit' => 'sesión',
        ]);

        // Puedes añadir más servicios aquí...

        // Opción 2: Usando el Query Builder (DB Facade)
        // DB::table('services')->insert([
        //     'name' => 'Instalación Software',
        //     'description' => 'Instalación y configuración de software específico.',
        //     'default_price' => 30.00,
        //     'unit' => 'unidad',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
    }
}
