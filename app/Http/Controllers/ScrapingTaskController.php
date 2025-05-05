<?php

namespace App\Http\Controllers;

use App\Models\ScrapingTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables; // Asegúrate de importar esto
use Illuminate\Validation\Rule;
use Throwable; // Usar Throwable para capturar más tipos de errores

class ScrapingTaskController extends Controller
{
    /**
     * Muestra la vista principal del gestor de tareas.
     */
    public function index()
    {
        $breadcrumbItems = [
            // ['name' => __('Dashboard'), 'url' => route('dashboard')], // Asegúrate que la ruta 'dashboard' existe o cámbiala/elimínala
            ['name' => __('Scraping Tasks Manager')],
        ];
        return view('scraping.index', compact('breadcrumbItems'));
    }

    /**
     * Proporciona los datos para la DataTable usando Yajra DataTables.
     */
    public function data(Request $request)
    {
        $query = ScrapingTask::where('user_id', Auth::id())->select([
            'id', 'source', 'keyword', 'region', 'status', 'api_task_id', 'created_at'
        ]);

        try {
            return DataTables::of($query)
                ->editColumn('created_at', fn($task) => $task->created_at ? $task->created_at->format('Y-m-d H:i:s') : '-')
                ->editColumn('region', fn($task) => $task->region ?: '-')
                ->editColumn('api_task_id', fn($task) => $task->api_task_id ?: '-')
                ->editColumn('status', function ($task) {
                    $status = $task->status ?? 'unknown';
                    $badgeClass = 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
                    switch (strtolower($status)) {
                        case 'completed': $badgeClass = 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'; break;
                        case 'failed': $badgeClass = 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'; break;
                        case 'pending': $badgeClass = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'; break;
                        case 'processing': $badgeClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'; break;
                    }
                    return '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ' . $badgeClass . '">' . __($status) . '</span>';
                })
                ->addColumn('actions', function($task) {
                    // --- Lógica de botones ---
                    $isEditable = $task->status === 'pending' && $task->api_task_id === null;
                    $isCompleted = strtolower($task->status ?? '') === 'completed'; // Comparación insensible a mayúsculas

                    $editClass = $isEditable ? 'editTask' : 'disabled';
                    $deleteClass = $isEditable ? 'deleteTask' : 'disabled';
                    $editTitle = $isEditable ? __('Edit Task') : __('Cannot edit processed task');
                    $deleteTitle = $isEditable ? __('Delete Task') : __('Cannot delete processed task');
                    $viewContactsTitle = $isCompleted ? __('View Found Contacts') : __('Task not completed yet');

                    // Botón Editar
                    $editButton = <<<HTML
                        <span class="action-icon {$editClass}"
                            data-id="{$task->id}"
                            data-keyword="{$task->keyword}"
                            data-region="{$task->region}"
                            data-source="{$task->source}"
                            title="{$editTitle}">
                            <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                        </span>
                    HTML;

                    // Botón Ver Contactos (con manejo de error de ruta)
                    $viewContactsButton = '';
                    if ($isCompleted) {
                        try {
                            // Intenta generar la URL. Si falla, $viewUrl será null.
                            $viewUrl = route('scraping.tasks.contacts', $task->id);
                            $viewContactsButton = '<a href="' . $viewUrl . '" class="action-icon viewContacts" title="' . $viewContactsTitle . '"><iconify-icon icon="heroicons:eye"></iconify-icon></a>';
                        } catch (Throwable $e) { // Usar Throwable para capturar más errores
                            Log::error("Error al generar ruta 'scraping.tasks.contacts' para Tarea ID {$task->id}: " . $e->getMessage());
                            // Mostrar icono desactivado si la ruta falla
                            $viewContactsButton = '<span class="action-icon disabled" title="' . __('Route error') . '"><iconify-icon icon="heroicons:exclamation-circle"></iconify-icon></span>';
                        }
                    } else {
                        // Mostrar icono desactivado si no está completada
                        $viewContactsButton = '<span class="action-icon disabled" title="' . $viewContactsTitle . '"><iconify-icon icon="heroicons:eye-slash"></iconify-icon></span>';
                    }

                    // Botón Borrar
                    $deleteButton = <<<HTML
                        <span class="action-icon {$deleteClass}"
                            data-id="{$task->id}" title="{$deleteTitle}">
                            <iconify-icon icon="heroicons:trash"></iconify-icon>
                        </span>
                    HTML;

                    // Combinar botones
                    return '<div class="actions-wrapper">' . $editButton . $viewContactsButton . $deleteButton . '</div>';
                })
                ->rawColumns(['actions', 'status'])
                ->orderColumn('created_at', fn($query, $order) => $query->orderBy('created_at', $order))
                ->orderColumn('id', fn($query, $order) => $query->orderBy('id', $order))
                ->make(true);

        } catch (Throwable $e) { // Capturar Throwable para errores más generales
            Log::error('Error al generar datos para DataTables en ScrapingTaskController@data: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Could not load tasks data.')
            ], 500);
        }
    }


    /**
     * Guarda una nueva tarea de scraping pendiente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'source' => ['required', 'string', Rule::in(['google_ddg', 'empresite', 'paginas_amarillas'])],
        ]);

        try {
            ScrapingTask::create([
                'user_id' => Auth::id(),
                'keyword' => $validated['keyword'],
                'region' => $validated['region'] ?: null,
                'source' => $validated['source'],
                'status' => 'pending',
                'api_task_id' => null,
                'ollama_task_id' => null,
            ]);

            return redirect()->route('scraping.tasks.index')->with('success', __('Scraping task created successfully. It will be processed soon.'));

        } catch (Throwable $e) {
            Log::error("Error al crear ScrapingTask: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Failed to create scraping task.'))->withInput();
        }
    }

    /**
     * Actualiza una tarea de scraping pendiente.
     */
    public function update(Request $request, ScrapingTask $task)
    {
        if ($task->user_id !== Auth::id()) {
            return response()->json(['error' => __('Unauthorized')], 403);
        }
        if ($task->status !== 'pending' || $task->api_task_id !== null) {
             return response()->json(['error' => __('This task cannot be edited anymore.')], 422);
        }

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'source' => ['required', 'string', Rule::in(['google_ddg', 'empresite', 'paginas_amarillas'])],
        ]);

         try {
            $task->update([
                'keyword' => $validated['keyword'],
                'region' => $validated['region'] ?: null,
                'source' => $validated['source'],
            ]);
            return response()->json(['success' => __('Task updated successfully!')]);
        } catch (Throwable $e) {
            Log::error("Error al actualizar ScrapingTask ID {$task->id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => __('Failed to update task.')], 500);
        }
    }

    /**
     * Elimina una tarea de scraping pendiente.
     */
    public function destroy(ScrapingTask $task)
    {
         if ($task->user_id !== Auth::id()) {
            return response()->json(['error' => __('Unauthorized')], 403);
        }
        if ($task->status !== 'pending' || $task->api_task_id !== null) {
             return response()->json(['error' => __('This task cannot be deleted.')], 422);
        }

        try {
            $task->delete();
            return response()->json(['success' => __('Task deleted successfully!')]);
        } catch (Throwable $e) {
             Log::error("Error al eliminar ScrapingTask ID {$task->id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => __('Failed to delete task.')], 500);
        }
    }

     /**
     * Muestra los contactos asociados a una tarea de scraping específica.
     */
    public function showContacts(ScrapingTask $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        $contacts = $task->contacts()->paginate(25);
         $breadcrumbItems = [
            // ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Scraping Tasks Manager'), 'url' => route('scraping.tasks.index')],
            ['name' => __('Task Contacts') . ' (ID: ' . $task->id . ')', 'active' => true],
        ];
        // Asegúrate que la vista 'scraping.contacts' existe
        return view('scraping.contacts', compact('task', 'contacts', 'breadcrumbItems'));
    }
}
