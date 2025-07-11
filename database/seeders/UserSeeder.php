<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = collect([
            [
                'name' => 'Super Admin',
                'email' => 'liviudiaconu@appnet.dev',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'super-admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'diaconuliviu85@gmail.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Employee',
                'email' => 'liviudiaconu.dev@gmail.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'employee',
            ],
        ]);

        $users->map(function ($user) {
            $user = collect($user);
            
            // Verificar si el usuario ya existe antes de crearlo
            $existingUser = User::where('email', $user['email'])->first();
            
            if (!$existingUser) {
                $newUser = User::create($user->except('role')->toArray());
                $newUser->assignRole($user['role']);
            } else {
                // Actualizar el rol del usuario existente si es necesario
                $existingUser->syncRoles([$user['role']]);
            }
        });
    }
}
