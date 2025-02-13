<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    // Muestra la vista del calendario
    public function index()
    {
        return view('calendar.index');
    }


    // Devuelve los eventos del usuario en formato JSON (para FullCalendar)
    public function fetchEvents(Request $request)
    {
        $events = Event::where('user_id', Auth::id())->get();

        // Adaptamos la estructura para FullCalendar
        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id'       => $event->id,
                'title'    => $event->title,
                'start'    => $event->start_date,
                'end'      => $event->end_date,
                'category' => $event->category,
            ];
        }
        return response()->json($data);
    }

    // Guarda un nuevo evento
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event-title'      => 'required|string|max:255',
            'event-start-date' => 'required|date',
            'event-end-date'   => 'nullable|date',
            'event-category'   => 'required|string'
        ]);

        $event = new Event();
        $event->user_id    = Auth::id();
        $event->title      = $validated['event-title'];
        $event->start_date = $validated['event-start-date'];
        $event->end_date   = $validated['event-end-date'];
        $event->category   = $validated['event-category'];
        $event->save();

        return response()->json(['success' => true, 'event' => $event]);
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'event-title'      => 'required|string|max:255',
            'event-start-date' => 'required|date',
            'event-end-date'   => 'nullable|date',
            'event-category'   => 'required|string'
        ]);

        // Buscar el evento por su ID y verificar que pertenezca al usuario autenticado
        $event = Event::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->firstOrFail();

        // Actualizar los datos del evento
        $event->update([
            'title'      => $validated['event-title'],
            'start_date' => $validated['event-start-date'],
            'end_date'   => $validated['event-end-date'],
            'category'   => $validated['event-category']
        ]);

        return response()->json(['success' => true, 'event' => $event]);
    }
    public function destroy(Request $request, $id)
    {
        // Buscar el evento y asegurarse de que pertenece al usuario autenticado
        $event = Event::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

        $event->delete();

        return response()->json(['success' => true]);
    }

}
