<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
// Si usas DataTables para listar tareas dentro de un proyecto:
// use Yajra\DataTables\Facades\DataTables;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks for a specific project.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $canViewProject = false;

        if ($user->can('projects show')) { // Admin/Empleado con permiso general para ver proyectos
            $canViewProject = true;
        } elseif ($user->hasRole('customer') && $project->client && $project->client->user_id == $user->id && $user->can('projects view_own')) {
            $canViewProject = true; // Cliente dueño del proyecto
        }

        if (!$canViewProject || !$user->can('tasks index')) { // Permiso general para listar tareas
             // O un permiso más específico como 'view tasks for project'
            abort(403, __('This action is unauthorized.'));
        }

        // Cargar tareas con usuarios asignados para mostrar en la lista
        // Podrías paginar si son muchas tareas por proyecto
        $tasks = $project->tasks()->with('users')->orderBy('priority')->orderBy('due_date')->get();

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $project->project_title, 'url' => route('projects.show', $project->id)],
            ['name' => __('Tasks'), 'url' => route('projects.tasks.index', $project->id)],
        ];

        // Normalmente, el listado de tareas se muestra en la vista projects.show
        // Si tienes una vista separada para tasks.index:
        // return view('tasks.index', compact('project', 'tasks', 'breadcrumbItems'));
        // Por ahora, redirigimos a la vista del proyecto, donde se listarán las tareas.
        return redirect()->route('projects.show', $project->id);
    }

    /**
     * Show the form for creating a new task for a specific project.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function create(Project $project)
    {
        if (!Auth::user()->can('tasks create')) {
            abort(403, __('This action is unauthorized.'));
        }
        // Un cliente no debería poder crear tareas directamente
        if (Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }


        $assignableUsers = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'customer');
        })->orderBy('name')->pluck('name', 'id');

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $project->project_title, 'url' => route('projects.show', $project->id)],
            ['name' => __('Create Task'), 'url' => route('projects.tasks.create', $project->id)],
        ];

        return view('tasks.create', compact('project', 'assignableUsers', 'breadcrumbItems'));
    }

    /**
     * Store a newly created task in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Project $project)
    {
        if (!Auth::user()->can('tasks create') || Auth::user()->hasRole('customer')) {
             abort(403, __('This action is unauthorized.'));
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('projects.tasks.create', $project->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create task. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $taskData = $request->only([
                'title', 'description', 'status', 'priority',
                'start_date', 'due_date', 'estimated_hours'
            ]);
            $taskData['project_id'] = $project->id;

            $task = Task::create($taskData);

            if ($request->has('assigned_users')) {
                $task->users()->sync($request->input('assigned_users'));
            } else {
                $task->users()->sync([]);
            }

            DB::commit();
            Log::info("Task #{$task->id} created for Project #{$project->id}");
            // Redirigir a la vista de detalle del proyecto, donde se listan las tareas
            return redirect()->route('projects.show', $project->id)->with('success', __('Task created successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating task: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('projects.tasks.create', $project->id)
                        ->withInput()
                        ->with('error', __('An error occurred while creating the task.'));
        }
    }

    /**
     * Display the specified task.
     * (Ruta shallow: /tasks/{task})
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\View\View
     */
    public function show(Task $task)
    {
        $user = Auth::user();
        $project = $task->project; // Obtener el proyecto de la tarea
        $canViewProject = false;
        $isOwner = false;

        if ($user->can('projects show')) { $canViewProject = true; }
        elseif ($user->hasRole('customer') && $project && $project->client && $project->client->user_id == $user->id && $user->can('projects view_own')) {
            $canViewProject = true;
            $isOwner = true;
        }

        if (!$canViewProject || (!$user->can('tasks show') && !$isOwner) ) { // Si no puede ver el proyecto, o no tiene permiso general de ver tareas y no es dueño
            abort(403, __('This action is unauthorized.'));
        }
        // Si es dueño, y tiene permiso 'tasks view_own' o 'tasks view_assigned' (si se implementa)
        // if ($isOwner && !($user->can('tasks view_own') || $user->can('tasks view_assigned'))) {
        //     abort(403, __('This action is unauthorized.'));
        // }


        $task->load('project.client', 'users', 'timeHistories'); // Cargar relaciones

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $task->project->project_title, 'url' => route('projects.show', $task->project->id)],
            ['name' => __('Task Details'), 'url' => route('tasks.show', $task->id)],
        ];
        return view('tasks.show', compact('task', 'breadcrumbItems'));
    }

    /**
     * Show the form for editing the specified task.
     * (Ruta shallow: /tasks/{task})
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\View\View
     */
    public function edit(Task $task)
    {
        if (!Auth::user()->can('tasks update') || Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }
        // Podrías añadir lógica para no editar tareas completadas/canceladas

        $project = $task->project; // Necesario para el breadcrumb y la acción del formulario
        $assignableUsers = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'customer');
        })->orderBy('name')->pluck('name', 'id');

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $project->project_title, 'url' => route('projects.show', $project->id)],
            ['name' => __('Edit Task'), 'url' => route('tasks.edit', $task->id)],
        ];

        return view('tasks.edit', compact('task', 'project', 'assignableUsers', 'breadcrumbItems'));
    }

    /**
     * Update the specified task in storage.
     * (Ruta shallow: /tasks/{task})
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Task $task)
    {
        if (!Auth::user()->can('tasks update') || Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'logged_hours' => 'nullable|numeric|min:0', // Permitir actualizar horas registradas
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('tasks.edit', $task->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to update task. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $taskData = $request->only([
                'title', 'description', 'status', 'priority',
                'start_date', 'due_date', 'estimated_hours', 'logged_hours'
            ]);
            // project_id no debería cambiar al editar una tarea

            $task->update($taskData);

            if ($request->has('assigned_users')) {
                $task->users()->sync($request->input('assigned_users'));
            } else {
                $task->users()->sync([]);
            }

            DB::commit();
            Log::info("Task #{$task->id} updated for Project #{$task->project_id}");
            return redirect()->route('projects.show', $task->project_id)->with('success', __('Task updated successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating task: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('tasks.edit', $task->id)
                        ->withInput()
                        ->with('error', __('An error occurred while updating the task.'));
        }
    }

    /**
     * Remove the specified task from storage.
     * (Ruta shallow: /tasks/{task})
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Task $task)
    {
         if (!Auth::user()->can('tasks delete') || Auth::user()->hasRole('customer')) {
            // Si es llamado vía AJAX, devolver JSON, si no, abortar.
            if (request()->ajax()) {
                return response()->json(['error' => __('This action is unauthorized.')], 403);
            }
            abort(403, __('This action is unauthorized.'));
        }

        $projectId = $task->project_id; // Guardar antes de borrar por si se necesita para redirigir

        DB::beginTransaction();
        try {
            // Desasociar usuarios antes de borrar (sync([]) también lo haría)
            $task->users()->detach();
            // Eliminar registros de tiempo asociados si tienes onDelete('cascade') en la relación
            // o borrarlos manualmente: $task->timeHistories()->delete();
            $task->delete();

            DB::commit();
            Log::info("Task #{$task->id} deleted from Project #{$projectId}");

            if (request()->ajax()) {
                return response()->json(['success' => __('Task deleted successfully!')]);
            }
            return redirect()->route('projects.show', $projectId)->with('success', __('Task deleted successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting task: '.$e->getMessage());
            if (request()->ajax()) {
                return response()->json(['error' => __('An error occurred while deleting the task.')], 500);
            }
            return redirect()->route('projects.show', $projectId)->with('error', __('An error occurred while deleting the task.'));
        }
    }
}
