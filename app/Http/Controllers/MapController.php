<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;       // Importar Carbon para manejo de fechas

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
            Log::error("Error fetching control points from table '$tableName': " . $e->getMessage());
            return response()->json(['error' => 'Could not fetch control points'], 500);
        }
    }
    public function getUserHistoryByDay(int $userId, string $dateString)
    {
        // 1. Validar Usuario (Opcional pero recomendado)
        $user = User::find($userId); // O findOrFail($userId) para lanzar 404 si no existe
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        // 2. Validar Fecha y Rango
        try {
            // Parsear la fecha solicitada usando Carbon
            // Carbon asume la zona horaria de la aplicación (config/app.php)
            $requestedDate = Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay(); // Asegura empezar al inicio del día
        } catch (\Exception $e) {
            return response()->json(['error' => 'Formato de fecha inválido. Use AAAA-MM-DD.'], 400);
        }

        // Calcular la fecha límite (hoy - 33 días)
        $limitDate = Carbon::today()->subDays(33)->startOfDay(); // Hoy a las 00:00 menos 33 días

        // Comprobar si la fecha solicitada está dentro del rango permitido
        if ($requestedDate->isBefore($limitDate)) {
            return response()->json(['error' => 'La fecha solicitada es demasiado antigua (máximo 33 días atrás).'], 400);
        }
        // Comprobar si la fecha es futura
        if ($requestedDate->isFuture()) {
             return response()->json(['error' => 'No se puede solicitar una fecha futura.'], 400);
        }


        // 3. Realizar la Consulta
        try {
            // Usamos Eloquent (Location model) si existe y está bien configurado
            // Si no, puedes usar DB::table('locations') como en los otros métodos
            $history = Location::where('user_id', $userId)
                // whereDate compara solo la parte de la fecha de la columna 'recorded_at'
                // con la fecha proporcionada, ignorando la hora.
                // Importante: Esto usa la zona horaria de la BD/App. Si tus 'recorded_at'
                // están en UTC y tu app en otra zona, podrías necesitar whereBetween
                // con las fechas de inicio/fin del día convertidas a UTC.
                ->whereDate('recorded_at', $requestedDate)
                // Seleccionar solo las columnas necesarias para el historial
                ->select('latitude', 'longitude', 'recorded_at', 'accuracy', 'velocity', 'altitude', 'course')
                // Ordenar cronológicamente para trazar la ruta
                ->orderBy('recorded_at', 'asc')
                ->get();

             // No es necesario transformar lat/lon si usas Eloquent con $casts correctos en el modelo Location
             // Si usaras DB::table aquí, necesitarías la transformación:
             /*
             $history->transform(function ($item) {
                 $item->latitude = (float) $item->latitude;
                 $item->longitude = (float) $item->longitude;
                 return $item;
             });
             */

            return response()->json($history);

        } catch (\Exception $e) {
            Log::error("Error fetching history for user $userId on date $dateString: " . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el historial.'], 500);
        }
    }
}
