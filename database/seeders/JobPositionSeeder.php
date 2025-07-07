<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $positions = [
            ['name' => 'Gerente'],
            ['name' => 'Contable'],
            ['name' => 'Administrador'],
            ['name' => 'Comercial'],
            ['name' => 'Oficina'],
            ['name' => 'Operario'],
            ['name' => 'Jefe de Fabrica'],
            ['name' => 'Encargado'],
            ['name' => 'Logistica'],
        ];
        
        foreach ($positions as $position) {
            // Verificar si el registro ya existe antes de insertarlo
            $exists = DB::table('job_posicion')
                ->where('name', $position['name'])
                ->exists();
                
            if (!$exists) {
                DB::table('job_posicion')->insert($position);
            }
        }
    }
}
