<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Importar el modelo User
use App\Models\TimeControl;
use App\Models\TimeControlStatus;
use App\Models\TimeControlStatusRules; // Importar estos modelos
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class TimeControlController extends Controller
{
    public function showControlPanel()
    {
        $user = Auth::user();

        $currentStatusId = $this->getLastStatusId();

        $allowedButtons = $this->getAllowedButtons($currentStatusId);

        return view('dashboard-ecommerce', compact('allowedButtons', 'user')); // Pasar el usuario a la vista
    }

    private function getAllowedButtons($currentStatusId)
    {
        if (!$currentStatusId) {
            // Si no hay registro, solo se permite 'Inicio de jornada':
            return TimeControlStatus::where('table_name', 'Start Workday')->pluck('id');
        }

        // Obtener las reglas permitidas
        $rules = TimeControlStatusRules::where('time_control_status_id', $currentStatusId)
            ->pluck('permission_id');

        // Adaptar este filtro si utilizas Eloquent para obtener los registros completos
        return TimeControlStatus::whereIn('id', $rules)->pluck('id');
    }

    //Obtener el ultimo estado
    public function getLastStatusId()
    {
        $user = Auth::user(); // Obtiene el usuario actual

        $lastStatus = TimeControl::where('user_id', $user->id)
                                ->latest('created_at') // Ordena por fecha de creación descendente
                                ->first();

        return $lastStatus ? $lastStatus->time_control_status_id : null;
    }

    //final obtener el ultimo fichaje

    //aqui se genera el fichaje...
    public function addNewTimeControl(Request $request)
    {
        $user = Auth::user(); // Obtiene el usuario actual
        $statusId = (int)$request->input('status_id'); // Obtiene el ID de estado de la solicitud

        //verificar si el usuario esta dentro de la haria permitida.
        if ($user->point_control_enable !== 1) {
            //return ['success' => false, 'message' => 'No estás en perimetro'];
        }

        // Validación: Asegúrate de que el $statusId es válido

        $timeControl = new TimeControl([
            'user_id' => $user->id,
            'time_control_status_id' => $statusId,
            'lat' => $request->input('lat'),
            'long' => $request->input('long'),
        ]);

    // Calcular el tiempo de pausa solo si el statusId es 5 (Reanudar)
    if ($statusId === 5) {
        $timeBreak = $this->calculateBreakTime($user->id);
        $timeControl->time_break = $timeBreak;
    }
    if ($statusId === 3) {
        $timeTotalWork = $this->calculateTotalTime($user->id);
        $timeControl->time_working = $timeTotalWork;
        $timeTotalWorked = $this->calculateTotalWorkedTime($user->id);
        $timeControl->time_worked = $timeTotalWorked;
        $totalBreakTime = $this->totalBreakTime($user->id);
        $timeControl->total_break_time = $totalBreakTime;
    }

        $timeControl->save();

        // Respuesta: Devolver un JSON con status:success o manejar el redireccionamiento
        return response()->json(['success' => true]);
    }
    // Función para calcular el tiempo de pausa
    public function calculateBreakTime($userId)
    {
        $previousStatus = $this->getLastStatusId(); // Obtener el estado anterior

        if ($previousStatus === 4) { // Verificar si era 'Pausa' con id 4
            $lastPause = TimeControl::where('user_id', $userId)  // Filtra por ID de usuario
                ->where('time_control_status_id', 4)       // Filtra por estado de pausa (ID = 4)
                ->orderBy('created_at', 'desc')           // Ordena por fecha de creación descendente
                ->first();                                 // Obtiene el primer resultado (el último)

            // Calcular el tiempo de pausa y actualizar registro
            if ($lastPause) {
                $now = now();
            $formattedNow = $now->format('Y-m-d H:i:s'); // Formato de fecha y hora estándar Salida: 2024-03-24 22:54:01

            $fecha1 = Carbon::parse($formattedNow);
            $fecha2 = Carbon::parse($lastPause->created_at);

                $timeBreak = floor($fecha1->diffInMinutes($fecha2));
                return $timeBreak; // Retornar el tiempo de pausa calculado
            } else {
                return 0; // Si no hay pausas anteriores, el tiempo de pausa es cero
            }
        } else {
            return 0; // Si el estado anterior no es una pausa, el tiempo de pausa es cero
        }
    }
    //calcular todo el tiempo en trabajo desde inicio al final
    public function calculateTotalTime($userId)
    {
        $lastStart = TimeControl::where('user_id', $userId)  // Filtra por ID de usuario
            ->where('time_control_status_id', 2)       // Filtra por estado de inicio (ID = 2)
            ->orderBy('created_at', 'desc')           // Ordena por fecha de creación descendente
            ->first();                                 // Obtiene el primer resultado (el último)

        // Calcular el tiempo total trabajado
        if ($lastStart) {
            $now = now();
            $formattedNow = $now->format('Y-m-d H:i:s'); // Formato de fecha y hora estándar Salida: 2024-03-24 22:54:01

            $fecha1 = Carbon::parse($formattedNow);
            $fecha2 = Carbon::parse($lastStart->created_at);

            $timeTotalWork = floor($fecha1->diffInMinutes($fecha2));

            return $timeTotalWork; // Retornar el tiempo total trabajado
        } else {
            return 0; // Si no hay registro de inicio, el tiempo total es cero
        }
    }
    //calcular todo el tiempo trabajando pero quitando las pausas, solo horas efectivas
    public function calculateTotalWorkedTime($userId)
    {


        // Calcular el tiempo total trabajado

            $timeTotalWork = $this->calculateTotalTime($userId);
            $lastTotalBreak = $this->totalBreakTime($userId);
            $timeWorked = $timeTotalWork -$lastTotalBreak;
            return $timeWorked; // Retornar el tiempo trabajado real

    }
//calcular tiempo en descanso, total buscando si hay varias pausas
    public function totalBreakTime($userId)
    {
        // Obtener todas las pausas después del último inicio de jornada
        $pausas = TimeControl::where('user_id', $userId)
            ->where('time_control_status_id', 5) // Filtra por pausas (ID = 5)
            ->where('created_at', '>', TimeControl::where('user_id', $userId)
                ->where('time_control_status_id', 2)
                ->orderBy('created_at', 'desc')
                ->first()->created_at)
            ->orderBy('created_at', 'asc')
            ->get();

        // Calcular el tiempo total de pausa
        $tiempoTotalPausa = 0;

        foreach ($pausas as $pausa) {
            $tiempoTotalPausa += $pausa->time_break;
        }

        return $tiempoTotalPausa;
    }

    //Calcular distancia entre 2 puntos gps
    function haversine($lat1, $lon1, $lat2, $lon2) {

        $R = 6371; // Radio de la Tierra en kilómetros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));

        return $R * $c;
      }



}
