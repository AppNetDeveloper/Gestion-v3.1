<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeOfContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            ['name' => 'Fijo'],
            ['name' => 'Fijo Discontinuo'],
            ['name' => 'Temporal'],
        ];
        
        foreach ($types as $type) {
            // Verificar si el registro ya existe antes de insertarlo
            $exists = DB::table('type_of_contract')
                ->where('name', $type['name'])
                ->exists();
                
            if (!$exists) {
                DB::table('type_of_contract')->insert($type);
            }
        }
    }
    
}
