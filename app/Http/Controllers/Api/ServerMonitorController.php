<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HostList;
use App\Models\HostMonitor;
use Carbon\Carbon;


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
        // Obtener el host de la tabla host_lists
        $host = HostList::where('token', $request->token)->first();
         // Llamar al método de limpieza
        $this->deleteOldRecords($host);
        // Crear el registro en la tabla host_monitors
        $hostMonitor = HostMonitor::create([
            'id_host' => $host->id,
            'total_memory' => $request->total_memory,
            'memory_free' => $request->memory_free,
            'memory_used' => $request->memory_used,
            'memory_used_percent' => $request->memory_used_percent,
            'disk' => $request->disk,
            'cpu' => $request->cpu,
        ]);

        return response()->json(['message' => 'Data stored successfully', 'data' => $hostMonitor], 201);
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
    private function deleteOldRecords(HostList $host)
    {
        $host->hostMonitors()->where('created_at', '<', Carbon::now()->subDays(7))->delete();
    }
}
