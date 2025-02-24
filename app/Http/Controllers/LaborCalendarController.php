<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LaborCalendar;
use Carbon\Carbon;

class LaborCalendarController extends Controller
{
    public function index()
    {
        return view('labor_calendar.index');
    }

    // Carga todos los eventos de la tabla (por ejemplo, para el año solicitado)
    public function fetch(Request $request)
    {
        // Opcionalmente, puedes filtrar por rango de fechas o por año.
        $year = $request->input('year'); // se lo pasaremos como parámetro
        if ($year) {
            $start = "$year-01-01";
            $end   = "$year-12-31";
            $events = LaborCalendar::whereBetween('start_date', [$start, $end])->get();
        } else {
            $events = LaborCalendar::all();
        }

        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id'    => $event->id,
                'title' => $event->title,
                'start' => $event->start_date,
                'end'   => $event->end_date,
            ];
        }
        return response()->json($data);
    }

    /**
     * Guarda la configuración de días no laborables para sábado o domingo.
     * - day = 'saturday' o 'sunday'
     * - year = año actual mostrado
     * - status = 1 (activado) o 0 (desactivado)
     */
    public function saveNonWorking(Request $request)
    {
        $day = $request->input('day');      // saturday / sunday
        $year = $request->input('year');    // año
        $status = $request->input('status'); // 1 o 0

        if (!in_array($day, ['saturday', 'sunday'])) {
            return response()->json(['success' => false, 'msg' => 'Día inválido']);
        }
        if (!$year) {
            return response()->json(['success' => false, 'msg' => 'Año inválido']);
        }

        // Eliminamos primero todos los registros auto-generados de este "day" y "year"
        // para que no se dupliquen.
        $this->removeNonWorking($day, $year);

        if ($status == 1) {
            // Generamos todos los sábados o domingos de ese año y los insertamos
            $this->generateNonWorking($day, $year);
        }

        return response()->json(['success' => true]);
    }

    // Genera todos los registros auto-generados (sábados o domingos) para un año
    private function generateNonWorking($day, $year)
    {
        // saturday -> Carbon::SATURDAY (6)
        // sunday -> Carbon::SUNDAY (0)
        $dayConst = ($day === 'saturday') ? Carbon::SATURDAY : Carbon::SUNDAY;

        // Creamos un objeto Carbon al 1 de enero del año
        $date = Carbon::create($year, 1, 1);
        // Avanzamos hasta el primer "day" (sábado o domingo)
        while ($date->dayOfWeek !== $dayConst) {
            $date->addDay();
        }

        // Recorremos todo el año
        while ($date->year == $year) {
            LaborCalendar::create([
                'title'         => ucfirst($day).' sin trabajar',
                'start_date'    => $date->toDateString(),
                'end_date'      => $date->toDateString(),
                'auto_generated'=> true,
            ]);
            $date->addWeek();
        }
    }

    // Elimina todos los registros auto-generados (sábados o domingos) para un año
    private function removeNonWorking($day, $year)
    {
        $title = ucfirst($day).' sin trabajar';
        $start = "$year-01-01";
        $end   = "$year-12-31";

        LaborCalendar::where('title', $title)
            ->where('auto_generated', true)
            ->whereBetween('start_date', [$start, $end])
            ->delete();
    }

    // Devuelve los eventos en formato JSON para FullCalendar
    public function fetchEvents(Request $request)
    {
        $events = LaborCalendar::all();

        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id'    => $event->id,
                'title' => $event->title,
                'start' => $event->start_date,
                'end'   => $event->end_date,
            ];
        }
        return response()->json($data);
    }

    // Guarda un nuevo evento en el calendario laboral
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);

        $event = LaborCalendar::create($validated);

        return response()->json(['success' => true, 'event' => $event]);
    }

    // Actualiza un evento existente
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);

        $event = LaborCalendar::findOrFail($id);
        $event->update($validated);

        return response()->json(['success' => true, 'event' => $event]);
    }

    // Elimina un evento
    public function destroy($id)
    {
        $event = LaborCalendar::findOrFail($id);
        $event->delete();

        return response()->json(['success' => true]);
    }
}
