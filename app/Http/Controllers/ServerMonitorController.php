<?php

namespace App\Http\Controllers;

use App\Models\HostList;
use Illuminate\Http\Request;

class ServerMonitorController extends Controller
{
    /**
     * Muestra el dashboard unificado de monitoreo y gestión de servidores.
     */
    public function index()
    {
        $user = auth()->user();
        $query = HostList::query();

        // Filtrar según permisos:
        if ($user->hasPermissionTo('servermonitor show') && $user->hasPermissionTo('servermonitorbusynes show')) {
            // Mostrar hosts propios y globales
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            });
        } elseif ($user->hasPermissionTo('servermonitor show')) {
            // Solo hosts propios
            $query->where('user_id', $user->id);
        } elseif ($user->hasPermissionTo('servermonitorbusynes show')) {
            // Solo hosts globales
            $query->whereNull('user_id');
        } else {
            abort(403, 'No tienes permiso para ver los servidores.');
        }

        // Ordenamos por id ascendente para que el host con el id más pequeño aparezca primero.
        $hosts = $query->with(['hostMonitors' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->orderBy('id', 'asc')
        ->get();

        return view('servermonitor.index', compact('hosts'));
    }


    /**
     * Devuelve en formato JSON el último registro de monitoreo para un host dado.
     */
    public function getLatest(HostList $host)
    {
        $latest = $host->hostMonitors()->orderBy('created_at', 'desc')->first();

        if (!$latest) {
            return response()->json([
                'timestamp' => now()->format('H:i:s'),
                'cpu'       => 0,
                'memory'    => 0,
                'disk'      => 0,
            ]);
        }

        return response()->json([
            'timestamp' => $latest->created_at->format('H:i:s'),
            'cpu'       => $latest->cpu,
            'memory'    => $latest->memory_used_percent,
            'disk'      => $latest->disk,
        ]);
    }

    /**
     * Devuelve en formato JSON los últimos 20 registros de monitoreo para un host.
     */
    public function getHistory(HostList $host)
    {
        // Obtenemos los 40 registros más recientes (orden descendente) y luego los ordenamos ascendentemente
        $history = $host->hostMonitors()
                        ->orderBy('created_at', 'desc')
                        ->limit(160)
                        ->get()
                        ->sortBy('created_at')
                        ->values(); // Reindexar la colección

        $data = $history->map(function($item) {
            return [
                'timestamp' => $item->created_at->format('H:i:s'),
                'cpu'       => $item->cpu,
                'memory'    => $item->memory_used_percent,
                'disk'      => $item->disk,
            ];
        });

        return response()->json($data);
    }
}
