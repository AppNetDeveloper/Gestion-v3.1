<?php

namespace Database\Seeders;

use App\Models\MediaManager;
use Database\Seeders\Api\ApiDatabaseSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->call([
            TypeOfContractSeeder::class,
            ShiftSeeder::class,
            JobPositionSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            InvoiceSignaturePermissionSeeder::class,
            UserSeeder::class,
            TimeControlSeeder::class,
            ShiftDaysSeeder::class,
            MediaSeeder::class,
            HostListSeeder::class,
            ServiceSeeder::class,
            DiscountSeeder::class, // Add this line
            ClientSeeder::class,
        ]);

    }
}
