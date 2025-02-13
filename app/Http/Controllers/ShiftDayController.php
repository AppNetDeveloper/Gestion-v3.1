<?php

namespace App\Http\Controllers;

use App\Models\ShiftDay;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\User;

class ShiftDayController extends Controller
{
    /**
     * Muestra una lista paginada de ShiftDays.
     */
    public function index()
    {
        // Obtiene todos los registros con la relación Shift
        $shiftDays = ShiftDay::with('shift')->paginate(15);
        return view('shiftdays.index', compact('shiftDays'));
    }

    /**
     * Muestra el formulario para crear un nuevo ShiftDay.
     */
    public function create()
    {
        // Se obtienen los turnos para mostrarlos en un select, por ejemplo.
        $shifts = Shift::all();
        return view('shiftdays.create', compact('shifts'));
    }

    /**
     * Almacena un nuevo ShiftDay en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id'       => 'required|exists:shift,id',
            'day_of_week'    => 'required|string',
            'start_time'     => 'required', // Puedes agregar una validación de formato si lo deseas
            'end_time'       => 'required',
            'effective_hours'=> 'nullable|numeric',
            // 'split_start_time' y 'split_end_time' si son necesarios
        ]);

        ShiftDay::create($validated);

        return redirect()->route('shiftdays.index')->with('message', 'Shift day created successfully');
    }

    /**
     * Muestra el formulario para editar un ShiftDay existente.
     */
    public function edit(ShiftDay $shiftDay)
    {
        $shifts = Shift::all();
        return view('shiftdays.edit', compact('shiftDay', 'shifts'));
    }

    /**
     * Actualiza el ShiftDay en la base de datos.
     */
    public function update(Request $request, ShiftDay $shiftDay)
    {
        $validated = $request->validate([
            'shift_id'       => 'required|exists:shift,id',
            'day_of_week'    => 'required|string',
            'start_time'     => 'required',
            'end_time'       => 'required',
            'effective_hours'=> 'nullable|numeric',
        ]);

        $shiftDay->update($validated);

        return redirect()->route('shiftdays.index')->with('message', 'Shift day updated successfully');
    }

    /**
     * Elimina un ShiftDay de la base de datos.
     */
    public function destroy(ShiftDay $shiftDay)
    {
        $shiftDay->delete();

        return redirect()->route('shiftdays.index')->with('message', 'Shift day deleted successfully');
    }

    /**
     * Muestra la vista Kanban agrupada por día de la semana.
     */
    /**
     * Vista principal del Kanban de turnos por día.
     */
    public function kanban()
    {
        // Obtiene todos los turnos con sus relaciones Shift y Users
        $allShiftDays = ShiftDay::with(['shift', 'users'])->get();

        // Agrupa por day_of_week (ej.: "MONDAY", "TUESDAY", etc.)
        $shiftDaysGrouped = $allShiftDays->groupBy('day_of_week');

        // Define el orden clásico de los días de la semana
        $daysOfWeek = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

        // Obtiene todos los usuarios reales
        $users = User::all();

        return view('shiftdays.kanban', compact('shiftDaysGrouped', 'daysOfWeek', 'users'));
    }
    public function updateUsers(Request $request, ShiftDay $shiftDay)
    {
        // Validar que se envíe un array de IDs de usuarios
        $data = $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        // Actualiza la relación: crea, elimina o mantiene según corresponda
        $shiftDay->users()->sync($data['users']);

        return response()->json(['success' => true]);
    }

}
