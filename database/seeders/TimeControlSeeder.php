<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeControlSeeder extends Seeder
{
    public function run()
    {
        // 1) Definimos los estados a insertar o actualizar
        $statuses = [
            ['id' => 1, 'table_name' => 'Disabled',          'icon' => 'fe:disabled'],
            ['id' => 2, 'table_name' => 'Start Workday',     'icon' => 'tdesign:user-time'],
            ['id' => 3, 'table_name' => 'End Workday',       'icon' => 'gg:play-stop-r'],
            ['id' => 4, 'table_name' => 'Meal Break',        'icon' => 'game-icons:meal'],
            ['id' => 5, 'table_name' => 'Resume Workday',    'icon' => 'ic:sharp-restore'],
            ['id' => 6, 'table_name' => 'Doctor Visit',      'icon' => 'fa6-solid:house-medical-circle-check'],
            ['id' => 7, 'table_name' => 'Smoking Break',     'icon' => 'mdi:smoking'],
        ];

        foreach ($statuses as $status) {
            DB::table('time_control_status')
                ->updateOrInsert(
                    ['id' => $status['id']],                        // Cláusula WHERE
                    ['table_name' => $status['table_name'],         // Valores a insertar o actualizar
                     'icon'       => $status['icon']]
                );
        }

        // 2) Definimos las reglas (status_id + permission_id)
        $rules = [
            ['time_control_status_id' => 2, 'permission_id' => 3],
            ['time_control_status_id' => 2, 'permission_id' => 4],
            ['time_control_status_id' => 3, 'permission_id' => 2],
            ['time_control_status_id' => 4, 'permission_id' => 5],
            ['time_control_status_id' => 5, 'permission_id' => 3],
            ['time_control_status_id' => 5, 'permission_id' => 4],
            ['time_control_status_id' => 6, 'permission_id' => 5],
            ['time_control_status_id' => 2, 'permission_id' => 6],
            ['time_control_status_id' => 2, 'permission_id' => 7],
            ['time_control_status_id' => 5, 'permission_id' => 6],
        ];

        foreach ($rules as $rule) {
            DB::table('time_control_rules')
                ->updateOrInsert(
                    [   // Cláusula WHERE: combinamos ambas columnas para evitar duplicados
                        'time_control_status_id' => $rule['time_control_status_id'],
                        'permission_id'          => $rule['permission_id'],
                    ],
                    [   // No hay otros campos a actualizar; con un array vacío basta
                    ]
                );
        }
    }
}

