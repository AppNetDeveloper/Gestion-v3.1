<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Location;
use App\Models\User;
//insertamos Log
use App\Models\Log;

class MapController extends Controller
{
    /**
     * Muestra la vista principal del mapa.
     */
    public function index()
    {
        return view('map.index');
    }

    /**
     * Obtiene las últimas ubicaciones registradas para cada usuario.
     * Retorna datos en formato JSON para ser consumidos por JavaScript.
     */
    public function getLatestLocations()
    {
        // (Este método se mantiene igual que antes)
        $latestLocations = DB::table('locations as l')
            ->select('l.latitude', 'l.longitude', 'l.recorded_at', 'l.user_id', 'u.name as user_name')
            ->join('users as u', 'l.user_id', '=', 'u.id')
            ->whereIn('l.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('locations')
                      ->groupBy('user_id');
            })
            ->orderBy('l.recorded_at', 'desc')
            ->get();

        return response()->json($latestLocations);
    }

    /**
     * Obtiene todos los puntos de control definidos.
     * Retorna datos en formato JSON.
     */
    public function getControlPoints()
    {
        // *** CORREGIDO: Nombre de tabla es plural según la última foto ***
        $tableName = 'time_control_points'; // <- Corregido a plural

        try {
            $points = DB::table($tableName)
                ->select(
                    'id',
                    'name',
                    'lat as latitude',    // Usa 'lat' y alias a 'latitude' (Correcto)
                    'long as longitude',   // Usa 'long' y alias a 'longitude' (Correcto)
                    'distance as radius' // Usa 'distance' y alias a 'radius' (Correcto)
                )
                // Usar los nombres de columna reales de tu tabla aquí (Correcto)
                ->whereNotNull('lat')
                ->whereNotNull('long')
                ->whereNotNull('distance')
                ->get();

            // La transformación funciona con los alias (Correcto)
            $points->transform(function ($item) {
                 $item->latitude = (float) $item->latitude;
                 $item->longitude = (float) $item->longitude;
                 $item->radius = (float) $item->radius;
                 return $item;
             });

            return response()->json($points);

        } catch (\Exception $e) {
            \Log::error("Error fetching control points from table '$tableName': " . $e->getMessage());
            return response()->json(['error' => 'Could not fetch control points'], 500);
        }
    }
}
