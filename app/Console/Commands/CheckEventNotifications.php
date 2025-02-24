<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\Notification;
use Carbon\Carbon;

class CheckEventNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:check-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check calendar events and create notifications 1 hour before the event starts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting event notifications check...');

        // Bucle infinito, ideal para ser administrado con Supervisor
        while (true) {
            try {
                $now = Carbon::now();
                // Definimos una ventana de 1 minuto para capturar eventos a 1 hora de iniciar
                $startWindow = $now->copy()->addHour();
                $endWindow = $startWindow->copy()->addMinute();

                $this->info("Checking events between {$startWindow} and {$endWindow}");

                // Buscar eventos cuya fecha de inicio esté dentro de la ventana definida
                $events = Event::whereBetween('start_date', [$startWindow, $endWindow])->get();

                // Ejemplo en el controlador o comando:
                foreach ($events as $event) {
                    $eventUrl = rtrim(config('app.url'), '/') . '/events/' . $event->id;

                    // Utiliza el sistema de traducciones para generar el mensaje
                    $message = __('notifications.event_start', [
                        'title' => $event->title,
                        'url'   => $eventUrl,
                    ]);

                    // Verificar si ya existe una notificación para evitar duplicados
                    $exists = Notification::where('user_id', $event->user_id)
                        ->where('message', $message)
                        ->exists();

                    if (!$exists) {
                        Notification::create([
                            'user_id' => $event->user_id,
                            'message' => $message,
                            'sended'  => 0,
                            'seen'    => 0,
                        ]);

                        $this->info("Notification created for event ID: {$event->id}");
                    }
                }


                // Espera 60 segundos antes de la siguiente verificación
                sleep(60);
            } catch (\Exception $e) {
                $this->error("Error occurred: " . $e->getMessage());
                // En caso de error, esperar 60 segundos y continuar
                sleep(60);
            }
        }
    }
}
