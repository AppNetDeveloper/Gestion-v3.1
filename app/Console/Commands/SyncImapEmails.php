<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Support\Facades\Auth;

class SyncImapEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imap:sync-emails:forever';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta en bucle infinito la sincronización de correos IMAP para usuarios con configuración activa, a prueba de fallos, con una pausa de 5 minutos entre cada búsqueda.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando sincronización IMAP en bucle infinito...');

        while (true) {
            try {
                // Obtener usuarios que tengan configurada la IMAP y un imap_type distinto de 0
                $users = User::where('imap_type', '!=', 0)
                    ->whereNotNull('imap_host')
                    ->whereNotNull('imap_port')
                    ->whereNotNull('imap_username')
                    ->whereNotNull('imap_password')
                    ->get();

                if ($users->isEmpty()) {
                    $this->info('No se encontraron usuarios con configuración IMAP activa.');
                } else {
                    foreach ($users as $user) {
                        try {
                            $this->info("Procesando usuario ID: {$user->id} - {$user->email}");

                            // Impersonar al usuario para usar sus credenciales (esto puede variar según tu lógica)
                            Auth::loginUsingId($user->id);

                            // Instanciar el servicio IMAP
                            $imapService = new ImapService();

                            // Según el imap_type, ejecutamos la acción correspondiente.
                            switch ($user->imap_type) {
                                case 1:
                                    // Opción 1: Guardar solo los contactos.
                                    $this->info("Sincronizando contactos para usuario ID: {$user->id}");
                                    $imapService->syncContactsForUser();
                                    break;
                                case 2:
                                    // Opción 2: Guardar los correos nuevos.
                                    $this->info("Sincronizando correos nuevos para usuario ID: {$user->id}");
                                    // $imapService->syncNewEmails(); // Por implementar
                                    break;
                                case 3:
                                    // Opción 3: Guardar correos nuevos y auto responder.
                                    $this->info("Sincronizando y auto respondiendo para usuario ID: {$user->id}");
                                    // $imapService->syncNewEmails();
                                    // $imapService->autoReplyEmails();
                                    break;
                                case 4:
                                    // Opción 4: Guardar correos nuevos y usar IA para contestar.
                                    $this->info("Sincronizando y usando IA para responder para usuario ID: {$user->id}");
                                    // $imapService->syncNewEmails();
                                    // $imapService->aiReplyEmails();
                                    break;
                                default:
                                    $this->info("imap_type no reconocido para usuario ID: {$user->id}");
                                    break;
                            }
                        } catch (\Throwable $e) {
                            $this->error("Error al procesar el usuario ID: {$user->id} - " . $e->getMessage());
                            // Continúa con el siguiente usuario
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Error en la iteración principal: " . $e->getMessage());
            }

            $this->info("Pausa de 5 minutos antes de la siguiente sincronización...");
            sleep(300); // Pausa de 300 segundos (5 minutos)
        }

        // Este return nunca se alcanzará, pero se requiere por la firma.
        return 0;
    }
}
