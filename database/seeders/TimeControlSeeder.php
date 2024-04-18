<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar registros en `time_control_status`
        DB::table('time_control_status')->insert([
            ['id' => 1, 'table_name' => 'Disabled', 'icon' => 'fe:disabled'],// ID 1: Start the workday
            ['id' => 2, 'table_name' => 'Start Workday', 'icon' => 'tdesign:user-time'], // ID 1: Start the workday
            ['id' => 3, 'table_name' => 'End Workday', 'icon' => 'gg:play-stop-r'],  // ID 2: End the workday
            ['id' => 4, 'table_name' => 'Meal Break', 'icon' => 'game-icons:meal'],   // ID 3: Start/end of a designated meal break
            ['id' => 5, 'table_name' => 'Resume Workday', 'icon' => 'ic:sharp-restore'], // ID 4: Resume workday after a break
            ['id' => 6, 'table_name' => 'Doctor Visit', 'icon' => 'fa6-solid:house-medical-circle-check'], // ID 5: Employee out for a doctor's appointment
            ['id' => 7, 'table_name' => 'Smoking Break', 'icon' => 'mdi:smoking'], // ID 5: Employee out for a doctor's appointment
        ]);


        // Insertar registros en `time_control_rules`
        DB::table('time_control_rules')->insert([
            ['time_control_status_id' => 2, 'permission_id' => 3],
            ['time_control_status_id' => 2, 'permission_id' => 4],
            ['time_control_status_id' => 3, 'permission_id' => 2],
            ['time_control_status_id' => 4, 'permission_id' => 5],
            ['time_control_status_id' => 5, 'permission_id' => 3],
            ['time_control_status_id' => 5, 'permission_id' => 4],
            ['time_control_status_id' => 6, 'permission_id' => 5],
            ['time_control_status_id' => 1, 'permission_id' => 7],
            ['time_control_status_id' => 5, 'permission_id' => 6],
        ]);
    }
}

