<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Importar el modelo User
use App\Models\TimeControl;
use App\Models\TimeControlStatus;
use App\Models\TimeControlStatusRules; // Importar estos modelos
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Helpers\DistanceHelper;

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
        //para que funcione sin parar da igual que se ha perdido la conexion
        ignore_user_abort(true);

        $user = Auth::user(); // Obtiene el usuario actual
        $statusId = (int)$request->input('status_id'); // Obtiene el ID de estado de la solicitud

        //verificar si el usuario esta dentro de la haria permitida.
        if ($user->point_control_enable !== 1) {
            //return ['success' => false, 'message' => 'No estás en perimetro'];
        }

        // Validación: Asegúrate de que el $statusId es válido

        $lat = $request->input('lat');
        $long = $request->input('long');

        $timeControl = new TimeControl([
            'user_id' => $user->id,
            'time_control_status_id' => $statusId,
            'lat' => $lat,
            'long' => $long,
        ]);

    // Calcular el tiempo de pausa solo si el statusId es 5 (Reanudar)
    if ($statusId === 5) {
        $previousStatus = $this->getLastStatusId();

        if ($previousStatus === 4) {
            $timeControl->time_break = $this->calculateBreakTime($user->id, $previousStatus);
        }

        if ($previousStatus === 6) {
            $timeControl->time_doctor = $this->calculateDoctorTime($user->id, $previousStatus);
        }

        if ($previousStatus === 7) {
            $timeControl->time_smoking = $this->calculateSmokTime($user->id, $previousStatus);
        }

        if ($previousStatus === 8) {
            $timeControl->time_in_vehicle = $this->calculateVehicleTime($user->id, $previousStatus);
            $timeControl->distance_traveled = $this->calcularDistancia($user->id, $previousStatus, $lat, $long);
        }
    }
    // Calcular el tiempo total de trabajo solo si el statusId es 3 (Finalizar)
    if ($statusId === 3) {
        $timeControl->time_working = $this->calculateTotalTime($user->id);
        $timeControl->time_worked = $this->calculateTotalWorkedTime($user->id);
        $timeControl->total_break_time = $this->totalBreakTime($user->id);
        $timeControl->total_time_doctor = $this->totalDoctorTime($user->id);
        $timeControl->total_time_smoking = $this->totalSmokingTime($user->id);
        $timeControl->total_time_in_vehicle = $this->totalVehicleTime($user->id);
        $timeControl->total_distance_traveled = $this->totalDistanceTraveled($user->id);
    }

        $timeControl->save();

        // Respuesta: Devolver un JSON con status:success o manejar el redireccionamiento
        return response()->json(['success' => true]);
    }

    public function calcularDistancia($userId, $previousStatus, $lat, $long): int
    {
        if ($previousStatus !== 8) {
            return 0;
        }

        $lastPause = TimeControl::where('user_id', $userId)
            ->where('time_control_status_id', 8)
            ->orderByDesc('created_at')
            ->first();

        if (!$lastPause) {
            return 0;
        }

        return DistanceHelper::carretera($lastPause->lat, $lastPause->long, $lat, $long);
    }

    // Función para calcular el tiempo de pausa
    public function calculateBreakTime($userId,$previousStatus)
    {

        if ($previousStatus === 4) { // Verificar si era 'Pausa' con id 4
            $lastPause = TimeControl::where('user_id', $userId)  // Filtra por ID de usuario
                ->where('time_control_status_id', 4)       // Filtra por estado de pausa (ID = 4)
                ->orderBy('created_at', 'desc')           // Ordena por fecha de creación descendente
                ->first();                                 // Obtiene el primer resultado (el último)

            // Calcular el tiempo de pausa y actualizar registro
            if ($lastPause) {
                $now = now();
                $formattedNow = $now->format('Y-m-d H:i:s'); // Formato de fecha y hora estándar Salida: 2024-03-24 22:54:01

                $dateNow = Carbon::parse($formattedNow);
                $lastPauseDate = Carbon::parse($lastPause->created_at);
                $timeBreak = $lastPauseDate->diffInMinutes($dateNow);

                return $timeBreak; // Retornar el tiempo de pausa calculado
            } else {
                return 0; // Si no hay pausas anteriores, el tiempo de pausa es cero
            }
        } else {
            return 0; // Si el estado anterior no es una pausa, el tiempo de pausa es cero
        }
    }

    public function calculateVehicleTime($userId,$previousStatus)
    {

        if ($previousStatus === 8) { // Verificar si era 'Pausa' con id 4
            $lastPause = TimeControl::where('user_id', $userId)  // Filtra por ID de usuario
                ->where('time_control_status_id', 8)       // Filtra por estado de pausa (ID = 4)
                ->orderBy('created_at', 'desc')           // Ordena por fecha de creación descendente
                ->first();                                 // Obtiene el primer resultado (el último)

            // Calcular el tiempo de pausa y actualizar registro
            if ($lastPause) {
                $now = now();
                $formattedNow = $now->format('Y-m-d H:i:s'); // Formato de fecha y hora estándar Salida: 2024-03-24 22:54:01

                $dateNow = Carbon::parse($formattedNow);
                $lastPauseDate = Carbon::parse($lastPause->created_at);
                $timeVehicle = $lastPauseDate->diffInMinutes($dateNow);

                return $timeVehicle; // Retornar el tiempo de pausa calculado
            } else {
                return 0; // Si no hay pausas anteriores, el tiempo de pausa es cero
            }
        } else {
            return 0; // Si el estado anterior no es una pausa, el tiempo de pausa es cero
        }
    }
    public function calculateSmokTime($userId,$previousStatus)
    {

        if ($previousStatus === 7) { // Verificar si era 'Pausa' con id 7
            $lastPause = TimeControl::where('user_id', $userId)  // Filtra por ID de usuario
                ->where('time_control_status_id', 7)       // Filtra por estado de pausa (ID = 4)
                ->orderBy('created_at', 'desc')           // Ordena por fecha de creación descendente
                ->first();                                 // Obtiene el primer resultado (el último)

            // Calcular el tiempo de pausa y actualizar registro
            if ($lastPause) {
                $now = now();
                $formattedNow = $now->format('Y-m-d H:i:s'); // Formato de fecha y hora estándar Salida: 2024-03-24 22:54:01

                $dateNow = Carbon::parse($formattedNow);
                $lastPauseDate = Carbon::parse($lastPause->created_at);
                $timeSmok = $lastPauseDate->diffInMinutes($dateNow);

                return $timeSmok; // Retornar el tiempo de pausa calculado
            } else {
                return 0; // Si no hay pausas anteriores, el tiempo de pausa es cero
            }
        } else {
            return 0; // Si el estado anterior no es una pausa, el tiempo de pausa es cero
        }
    }
    public function calculateDoctorTime($userId,$previousStatus)
    {

        if ($previousStatus === 6) { // Verificar si era 'Pausa' con id 6
            $lastPause = TimeControl::where('user_id', $userId)  // Filtra por ID de usuario
                ->where('time_control_status_id', 6)       // Filtra por estado de pausa (ID = 6)
                ->orderBy('created_at', 'desc')           // Ordena por fecha de creación descendente
                ->first();                                 // Obtiene el primer resultado (el último)

            // Calcular el tiempo de pausa y actualizar registro
            if ($lastPause) {
                $now = now();
                $formattedNow = $now->format('Y-m-d H:i:s'); // Formato de fecha y hora estándar Salida: 2024-03-24 22:54:01

                $dateNow = Carbon::parse($formattedNow);
                $lastPauseDate = Carbon::parse($lastPause->created_at);
                $timeDoctor = $lastPauseDate->diffInMinutes($dateNow);

                return $timeDoctor; // Retornar el tiempo de pausa calculado
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

            $timeTotalWork = floor($fecha2->diffInMinutes($fecha1));

            return $timeTotalWork; // Retornar el tiempo total trabajado
        } else {
            return 0; // Si no hay registro de inicio, el tiempo total es cero
        }
    }
    public function totalVehicleTime($userId)
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
            $tiempoTotalPausa += $pausa->time_in_vehicle;
        }

        return $tiempoTotalPausa;

    }
    public function totalDistanceTraveled($userId)
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
        $km = 0;

        foreach ($pausas as $pausa) {
            $km += $pausa->distance_traveled;
        }

        return $km;

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
    public function totalSmokingTime($userId)
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
            $tiempoTotalPausa += $pausa->time_smoking;
        }

        return $tiempoTotalPausa;
    }
    public function totalDoctorTime($userId)
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
            $tiempoTotalPausa += $pausa->time_doctor;
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
