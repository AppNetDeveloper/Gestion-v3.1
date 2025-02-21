<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskerLinkedin;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskerLinkedinController extends Controller
{
    /**
     * Retorna la vista que muestra el listado de tareas programadas.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('tasker_linkedin.index');
    }

    /**
     * Devuelve los datos en formato JSON para DataTables, filtrando solo las tareas del usuario autenticado.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        // Obtener las tareas del usuario autenticado
        $tasks = TaskerLinkedin::where('user_id', Auth::id())
            ->select(['id', 'prompt', 'response', 'status', 'error', 'publish_date', 'created_at', 'updated_at'])
            ->get();

        $data = $tasks->map(function ($task) {
            return [
                'id'           => $task->id,
                'prompt'       => $task->prompt,
                'response'     => $task->response,
                'status'       => $task->status,
                'error'        => $task->error,
                'publish_date' => $task->publish_date ? $task->publish_date->toDateTimeString() : '',
                'created_at'   => $task->created_at->toDateTimeString(),
                'updated_at'   => $task->updated_at->toDateTimeString(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Almacena una nueva tarea programada para LinkedIn.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validar la entrada
        $request->validate([
            'prompt'       => 'required|string',
            'publish_date' => 'required|date'
        ]);

        try {
            $task = TaskerLinkedin::create([
                'user_id'      => Auth::id(),
                'prompt'       => $request->input('prompt'),
                'publish_date' => $request->input('publish_date'),
                'status'       => 'pending',
            ]);

            return response()->json([
                'success' => 'Task scheduled successfully.',
                'task_id' => $task->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error saving task: ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy(TaskerLinkedin $task)
    {
        // Asegurarse de que la tarea pertenece al usuario autenticado
        if ($task->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $task->delete();
            return response()->json(['success' => 'Task deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting task: ' . $e->getMessage()], 500);
        }
    }
}
