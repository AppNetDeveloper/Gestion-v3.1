<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact;

class EventController extends Controller
{
    // Muestra la vista del calendario
    public function index()
    {
        // Obtén los contactos (puedes aplicar filtros según corresponda)
        $contacts = Contact::all();

        return view('calendar.index', compact('contacts'));
    }


    // Devuelve los eventos del usuario en formato JSON (para FullCalendar)
    public function fetchEvents(Request $request)
    {
        $events = Event::where('user_id', Auth::id())->get();

        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id'               => $event->id,
                'title'            => $event->title,
                'start'            => $event->start_date,
                'end'              => $event->end_date,
                'category'         => $event->category,
                'video_conferencia'=> $event->video_conferencia,
                'contact_id'       => $event->contact_id,
            ];
        }
        return response()->json($data);
    }


    // Guarda un nuevo evento
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event-title'           => 'required|string|max:255',
            'event-start-date'      => 'required|date',
            'event-end-date'        => 'nullable|date',
            'event-category'        => 'required|string',
            'event-video_conferencia' => 'nullable|string',
            'event-contact_id'      => 'nullable|exists:contacts,id'
        ]);

        $event = new Event();
        $event->user_id           = Auth::id();
        $event->title             = $validated['event-title'];
        $event->start_date        = $validated['event-start-date'];
        $event->end_date          = $validated['event-end-date'];
        $event->category          = $validated['event-category'];
        $event->video_conferencia  = $validated['event-video_conferencia'] ?? null;
        $event->contact_id        = $validated['event-contact_id'] ?? null;
        $event->save();

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'event-title'           => 'required|string|max:255',
            'event-start-date'      => 'required|date',
            'event-end-date'        => 'nullable|date',
            'event-category'        => 'required|string',
            'event-video_conferencia' => 'nullable|string',
            'event-contact_id'      => 'nullable|exists:contacts,id'
        ]);

        $event = Event::where('id', $id)
                      ->where('user_id', auth()->id())
                      ->firstOrFail();

        $event->update([
            'title'             => $validated['event-title'],
            'start_date'        => $validated['event-start-date'],
            'end_date'          => $validated['event-end-date'],
            'category'          => $validated['event-category'],
            'video_conferencia' => $validated['event-video_conferencia'] ?? null,
            'contact_id'        => $validated['event-contact_id'] ?? null,
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
