<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mediaItems = [
            [
                'id' => 1,
                'model_type' => 'App\\Models\\GeneralSetting',
                'model_id' => 5,
                'uuid' => 'ca572c67-3663-4838-b8f1-e4aedb06d9cd',
                'collection_name' => 'guest_background',
                'name' => 'AppNet__1___1_-removebg',
                'file_name' => 'AppNet__1___1_-removebg.png',
                'mime_type' => 'image/png',
                'disk' => 'local',
                'conversions_disk' => 'local',
                'size' => 151111,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2024-05-22 07:47:30',
                'updated_at' => '2024-05-22 07:47:30',
            ],
            [
                'id' => 2,
                'model_type' => 'App\\Models\\GeneralSetting',
                'model_id' => 3,
                'uuid' => '9ea688c7-b7dc-4df6-8762-ddc150007a4b',
                'collection_name' => 'dark_logo',
                'name' => 'AppNet__1___1_-removebg-(1)',
                'file_name' => 'AppNet__1___1_-removebg-(1).png',
                'mime_type' => 'image/png',
                'disk' => 'local',
                'conversions_disk' => 'local',
                'size' => 2063,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2024-05-22 07:48:33',
                'updated_at' => '2024-05-22 07:48:33',
            ],
            [
                'id' => 3,
                'model_type' => 'App\\Models\\GeneralSetting',
                'model_id' => 4,
                'uuid' => '06f1639d-ed30-4b4c-97c0-946bfcb6e5e8',
                'collection_name' => 'guest_logo',
                'name' => 'guest-logo',
                'file_name' => 'guest-logo.png',
                'mime_type' => 'image/png',
                'disk' => 'local',
                'conversions_disk' => 'local',
                'size' => 2664,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2024-05-22 07:48:44',
                'updated_at' => '2024-05-22 07:48:44',
            ],
            [
                'id' => 4,
                'model_type' => 'App\\Models\\GeneralSetting',
                'model_id' => 2,
                'uuid' => '55d36133-afb7-4d05-892b-4b8abfbda410',
                'collection_name' => 'favicon',
                'name' => 'AppNet__1___1_-removebg (1)',
                'file_name' => 'AppNet__1___1_-removebg-(1).png',
                'mime_type' => 'image/png',
                'disk' => 'local',
                'conversions_disk' => 'local',
                'size' => 2063,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2024-05-22 07:49:18',
                'updated_at' => '2024-05-22 07:49:18',
            ],
            [
                'id' => 5,
                'model_type' => 'App\\Models\\GeneralSetting',
                'model_id' => 1,
                'uuid' => '2d0220e7-09b4-498e-a9cc-0acd045ee706',
                'collection_name' => 'logo',
                'name' => 'AppNet__1___1_-removebg (1)',
                'file_name' => 'AppNet__1___1_-removebg-(1).png',
                'mime_type' => 'image/png',
                'disk' => 'local',
                'conversions_disk' => 'local',
                'size' => 2063,
                'manipulations' => '[]',
                'custom_properties' => '[]',
                'generated_conversions' => '[]',
                'responsive_images' => '[]',
                'order_column' => 1,
                'created_at' => '2024-05-22 07:49:28',
                'updated_at' => '2024-05-22 07:49:28',
            ],
        ];
        
        foreach ($mediaItems as $item) {
            // Verificar si el registro ya existe antes de insertarlo
            $exists = \DB::table('media')->where('id', $item['id'])->exists();
            
            if (!$exists) {
                \DB::table('media')->insert($item);
            }
        }
    }
}
