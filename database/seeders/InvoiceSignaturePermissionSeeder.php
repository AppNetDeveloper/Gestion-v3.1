<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InvoiceSignaturePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Asegurarse de que los permisos existen
        $permissions = [
            ['name' => 'invoices sign', 'module_name' => 'invoices'],
            ['name' => 'invoices verify', 'module_name' => 'invoices'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                [
                    'name'       => $permissionData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'module_name' => $permissionData['module_name'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }

        // Asignar permisos a roles
        // Super-admin ya tiene todos los permisos por defecto
        
        // Admin
        $adminRole = Role::where(['name' => 'admin', 'guard_name' => 'web'])->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['invoices sign', 'invoices verify']);
        }
        
        // Manager - solo verificar, no firmar
        $managerRole = Role::where(['name' => 'manager', 'guard_name' => 'web'])->first();
        if ($managerRole) {
            $managerRole->givePermissionTo('invoices verify');
        }
        
        // Employee - solo verificar, no firmar
        $employeeRole = Role::where(['name' => 'employee', 'guard_name' => 'web'])->first();
        if ($employeeRole) {
            $employeeRole->givePermissionTo('invoices verify');
        }
        
        // Customer - solo verificar, no firmar
        $customerRole = Role::where(['name' => 'customer', 'guard_name' => 'web'])->first();
        if ($customerRole) {
            $customerRole->givePermissionTo('invoices verify');
        }

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Invoice signature permissions assigned successfully.');
    }
}
