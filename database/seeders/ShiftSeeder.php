<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shifts = [
            ['name' => 'mañana'],
            ['name' => 'tarde'],
            ['name' => 'noche'],
            ['name' => 'hornada partida'],
        ];
        
        foreach ($shifts as $shift) {
            // Verificar si el registro ya existe antes de insertarlo
            $exists = DB::table('shift')
                ->where('name', $shift['name'])
                ->exists();
                
            if (!$exists) {
                DB::table('shift')->insert($shift);
            }
        }
    }
}
