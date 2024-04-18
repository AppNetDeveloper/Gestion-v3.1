<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ShiftDaysSeeder extends Seeder
{
    public function run()
    {
        
        $shifts = [
            [
                'id' => 1,
                'days_of_week' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'],
                'start_time' => '06:30:00',
                'end_time' => '14:30:00',
                'effective_hours' => '08:00:00',
                'split_shift' => false,
            ],
            [
                'id' => 2,
                'days_of_week' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'],
                'start_time' => '14:30:00',
                'end_time' => '22:30:00',
                'effective_hours' => '08:00:00',
                'split_shift' => false,
            ],
            [
                'id' => 3,
                'days_of_week' => ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'],
                'start_time' => '22:30:00',
                'end_time' => '06:30:00',
                'effective_hours' => '08:00:00',
                'split_shift' => false,
            ],
            [
                'id' => 4,
                'days_of_week' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'],
                'start_time' => '08:00:00',
                'end_time' => '14:00:00',
                'effective_hours' => '08:00:00',
                'split_shift' => true,
                'split_start_time' => '15:00:00',
                'split_end_time' => '17:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            foreach ($shift['days_of_week'] as $day) {
                DB::table('shift_days')->insert([
                    'shift_id' => $shift['id'],
                    'day_of_week' => $day,
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                    'effective_hours' => $shift['effective_hours'],
                    'split_shift' => $shift['split_shift'],
                    'split_start_time' => $shift['split_start_time'] ?? null,
                    'split_end_time' => $shift['split_end_time'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
