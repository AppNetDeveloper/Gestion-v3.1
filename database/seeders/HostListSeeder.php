<?php

// database/seeders/HostListSeeder.php
namespace Database\Seeders;

use App\Models\HostList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HostListSeeder extends Seeder
{
    public function run()
    {
        HostList::create([
            'host' => 'localhost',
            'token' => Str::random(60), // Genera un token único en cada ejecución
            'name' => 'Localhost Server',
        ]);
    }
}
