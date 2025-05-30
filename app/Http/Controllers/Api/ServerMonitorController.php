<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HostList;
use App\Models\HostMonitor;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

/**
 * Controlador para el monitoreo de servidores
 */
class ServerMonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Almacena las métricas de un servidor
     *
     * @OA\Post(
     *     path="/api/server-monitor/metrics",
     *     summary="Almacena métricas del servidor",
     *     tags={"Server Monitor"},
     *     security={{"apiToken": {}}},
     *     @OA\Header(
     *         header="token",
     *         description="Token de autenticación del host",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "total_memory", "memory_free", "memory_used", "memory_used_percent", "disk", "cpu"},
     *             @OA\Property(property="token", type="string", example="host-unique-token"),
     *             @OA\Property(property="total_memory", type="number", format="float", example=8192),
     *             @OA\Property(property="memory_free", type="number", format="float", example=4096),
     *             @OA\Property(property="memory_used", type="number", format="float", example=4096),
     *             @OA\Property(property="memory_used_percent", type="number", format="float", example=50.5),
     *             @OA\Property(property="disk", type="number", format="float", example=75.2),
     *             @OA\Property(property="cpu", type="number", format="float", example=25.7)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Métricas almacenadas correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Host no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Host no encontrado")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'token' => 'required|exists:host_lists,token',
            'total_memory' => 'required|numeric',
            'memory_free' => 'required|numeric',
            'memory_used' => 'required|numeric',
            'memory_used_percent' => 'required|numeric',
            'disk' => 'required|numeric',
            'cpu' => 'required|numeric',
        ]);

        // Obtener el host mediante el token
        $host = HostList::where('token', $request->token)->first();

        // Llamar al método de limpieza de registros antiguos
        $this->deleteOldRecords($host);

        // Crear el registro en host_monitors
        $hostMonitor = HostMonitor::create([
            'id_host'             => $host->id,
            'total_memory'        => $request->total_memory,
            'memory_free'         => $request->memory_free,
            'memory_used'         => $request->memory_used,
            'memory_used_percent' => $request->memory_used_percent,
            'disk'                => $request->disk,
            'cpu'                 => $request->cpu,
        ]);

        // Verificar si alguna métrica excede el umbral (80%)
        if ($request->cpu > 80 || $request->memory_used_percent > 80 || $request->disk > 80) {

            // Consultar el registro anterior para este host (excluyendo el actual)
            $previousRecord = HostMonitor::where('id_host', $host->id)
                ->where('id', '<', $hostMonitor->id)
                ->orderBy('id', 'desc')
                ->first();

            // Si existe un registro anterior y también excede el umbral, no se envía la alerta
            $sendAlert = true;
            if ($previousRecord) {
                if ($previousRecord->cpu > 80 || $previousRecord->memory_used_percent > 80 || $previousRecord->disk > 80) {
                    $sendAlert = false;
                }
            }

            if ($sendAlert) {
                // Construir la URL para servermonitor evitando barras duplicadas
                $monitorUrl = rtrim(config('app.url'), '/') . '/servermonitor';

                // Crear el mensaje de alerta
                $alertMessage = "Server Monitor Alert: Host '{$host->name}' metrics exceeded threshold. "
                    . "Check at {$monitorUrl}. CPU: {$request->cpu}%, Memory used: {$request->memory_used_percent}%, Disk: {$request->disk}%.";

                // Si el host tiene un user_id asignado, se crea la notificación para ese usuario
                if ($host->user_id) {
                    Notification::create([
                        'user_id' => $host->user_id,
                        'message' => $alertMessage,
                        'sended'  => 0, // No enviada aún
                        'seen'    => 0, // No vista en la app
                    ]);
                } else {
                    // Si user_id es NULL, buscamos usuarios que tengan el permiso "servermonitorbusynes show"

                    // 1. Obtener los IDs de roles que tienen este permiso (filtrando también por module_name)
                    $roleIds = Role::whereHas('permissions', function ($query) {
                        $query->where('name', 'servermonitorbusynes show')
                              ->where('module_name', 'servermonitorbusynes');
                    })->pluck('id')->toArray();

                    // 2. Buscar usuarios que tengan alguno de esos roles
                    $userIdsFromRoles = User::whereHas('roles', function ($query) use ($roleIds) {
                        $query->whereIn('id', $roleIds);
                    })->pluck('id')->toArray();

                    // 3. Buscar usuarios que tengan el permiso asignado directamente
                    $userIdsDirectPerm = User::whereHas('permissions', function ($query) {
                        $query->where('name', 'servermonitorbusynes show')
                              ->where('module_name', 'servermonitorbusynes');
                    })->pluck('id')->toArray();

                    // 4. Unir ambos conjuntos de usuarios eliminando duplicados
                    $userIds = array_unique(array_merge($userIdsFromRoles, $userIdsDirectPerm));

                    // Crear notificaciones para cada usuario encontrado
                    foreach ($userIds as $userId) {
                        Notification::create([
                            'user_id' => $userId,
                            'message' => $alertMessage,
                            'sended'  => 0,
                            'seen'    => 0,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Data stored successfully',
            'data'    => $hostMonitor,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Eliminar registros antiguos de host_monitors.
     */
    private function deleteOldRecords(HostList $host)
    {
        $host->hostMonitors()
             ->where('created_at', '<', Carbon::now()->subDays(7))
             ->delete();
    }
}
