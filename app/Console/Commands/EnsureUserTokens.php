<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnsureUserTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ensure-user-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asegura que todos los usuarios tengan un token único';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando usuarios sin token...');
        
        // Obtener todos los usuarios
        $users = User::all();
        $count = 0;
        
        foreach ($users as $user) {
            // Verificar si el usuario ya tiene un token
            $hasToken = DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->exists();
            
            // Si no tiene token, crear uno
            if (!$hasToken) {
                $token = $user->createToken('default-token');
                $count++;
                $this->line("Token creado para el usuario: {$user->name} (ID: {$user->id})");
            }
        }
        
        $this->info("Proceso completado. Se crearon {$count} tokens.");
        
        return Command::SUCCESS;
    }
}
