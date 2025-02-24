<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HostList;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckServerStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'server:check-status';

    /**
     * The console command description.
     */
    protected $description = 'Bucle infinito que verifica cada 3 minutos el estado de los servidores y notifica si están caídos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Mostrar mensaje inicial utilizando la traducción
        $this->info(__('server_check_start'));

        // Permitir que el script se ejecute indefinidamente
        set_time_limit(0);

        while (true) {
            try {
                $this->checkServers();
            } catch (Exception $e) {
                // Loguear el error y mostrarlo utilizando la traducción
                Log::error(__('error', ['error' => $e->getMessage()]));
                $this->error(__('error', ['error' => $e->getMessage()]));
            }

            // Esperar 3 minutos antes de la siguiente iteración
            sleep(180);
        }
    }

    /**
     * Verifica el estado de cada servidor.
     */
    private function checkServers()
    {
        $hosts = HostList::all();

        foreach ($hosts as $host) {
            // Obtener el registro más reciente del servidor
            $latestRecord = $host->hostMonitors()->latest()->first();

            if (!$latestRecord) {
                $this->sendServerDownNotification(
                    $host,
                    __('no_monitor_records', ['name' => $host->name])
                );
                continue;
            }

            $lastUpdated = Carbon::parse($latestRecord->created_at);
            // Si han pasado 3 o más minutos desde el último registro, se asume que el servidor está caído
            if ($lastUpdated->diffInMinutes(Carbon::now()) >= 3) {
                $this->sendServerDownNotification(
                    $host,
                    __('server_down_alert', [
                        'name'        => $host->name,
                        'last_record' => $lastUpdated->toDateTimeString()
                    ])
                );
            }
        }
    }

    /**
     * Envía notificaciones de servidor caído.
     */
    private function sendServerDownNotification(HostList $host, string $message)
    {
        // Si el host tiene un usuario asignado, notificar a ese usuario
        if ($host->user_id) {
            Notification::create([
                'user_id' => $host->user_id,
                'message' => $message,
                'sended'  => 0,
                'seen'    => 0,
            ]);
        } else {
            // Si no, notificar a todos los usuarios con el permiso correspondiente
            $userIds = User::whereHas('permissions', function($query) {
                $query->where('name', 'servermonitorbusynes show')
                      ->where('module_name', 'servermonitorbusynes');
            })->pluck('id')->toArray();

            foreach ($userIds as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'message' => $message,
                    'sended'  => 0,
                    'seen'    => 0,
                ]);
            }
        }
    }
}
