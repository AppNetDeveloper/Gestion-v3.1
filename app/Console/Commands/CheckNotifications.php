<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use App\Models\User;
use GuzzleHttp\Client;

class CheckNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa las notificaciones pendientes (sended=0) y envía el mensaje mediante el canal correspondiente (por ahora WhatsApp).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando revisión de notificaciones...');

        // Bucle infinito para la ejecución continua.
        // Es recomendable administrar este comando con Supervisor u otra herramienta.
        while (true) {
            try {
                // Obtener notificaciones que no han sido enviadas (sended = 0)
                $notifications = Notification::where('sended', 0)->get();

                foreach ($notifications as $notification) {
                    // Obtener el usuario asociado a la notificación.
                    $user = User::find($notification->user_id);

                    if (!$user) {
                        // Si no se encuentra el usuario, se marca la notificación como enviada.
                        $notification->update(['sended' => 1]);
                        $this->info("Notificación ID {$notification->id}: Usuario no encontrado. Marcada como enviada.");
                        continue;
                    }

                    // Obtener el teléfono del usuario (se asume que el campo se llama 'phone')
                    $phone = $user->phone ?? null;

                    // Si no existe teléfono, se marca la notificación como enviada.
                    if (!$phone) {
                        $notification->update(['sended' => 1]);
                        $this->info("Notificación ID {$notification->id}: Sin teléfono. Marcada como enviada.");
                        continue;
                    }

                    // Validar que el teléfono esté en el formato requerido.
                    // No debe tener prefijos como "00" o "+" y debe comenzar con uno de los códigos permitidos (34, 35, 36).
                    if (preg_match('/^(00|\+)/', $phone)) {
                        $notification->update(['sended' => 1]);
                        $this->info("Notificación ID {$notification->id}: Teléfono '{$phone}' con prefijo no permitido. Marcada como enviada.");
                        continue;
                    }
                    if (!preg_match('/^(34|35|36)[0-9]+$/', $phone)) {
                        $notification->update(['sended' => 1]);
                        $this->info("Notificación ID {$notification->id}: Teléfono '{$phone}' no comienza con un prefijo permitido. Marcada como enviada.");
                        continue;
                    }

                    // Si el teléfono es válido, se procede a enviar la notificación vía WhatsApp.
                    // En el futuro se podrán integrar otros canales (ej. email).
                    $client = new Client();
                    try {
                        // Construir la URL de la API de WhatsApp (se asume que está definida en .env como WHATSAPP_API_TOKEN)
                        $apiUrl = env('APP_URL') . '/api/whatsapp/send-message-now';
                        $response = $client->post($apiUrl, [
                            'json' => [
                                'token'     => env('WHATSAPP_API_TOKEN'),
                                'sessionId' => env('WHATSAPP_ID_SERVER'), // Ajusta el sessionId según corresponda
                                'jid'       => $phone, // El número debe estar en el formato correcto, ej: 34619929305
                                'message'   => $notification->message,
                            ]
                        ]);

                        $body = $response->getBody()->getContents();
                        $nodeData = json_decode($body, true);

                        if (isset($nodeData['success']) && $nodeData['success']) {
                            // Se marca la notificación como enviada
                            $notification->update(['sended' => 1]);
                            $this->info("Notificación ID {$notification->id} enviada correctamente vía WhatsApp.");
                        } else {
                            $this->error("Error al enviar notificación ID {$notification->id}: " . ($nodeData['message'] ?? 'Error desconocido'));
                        }
                    } catch (\Exception $e) {
                        $this->error("Excepción al enviar notificación ID {$notification->id}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error en el ciclo de notificaciones: " . $e->getMessage());
            }

            // Espera 60 segundos antes de la siguiente verificación.
            sleep(60);
        }
    }
}
