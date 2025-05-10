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
use Yajra\DataTables\Facades\DataTables;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks for a specific project.
     * (Esta ruta ahora se usa más para la vista de detalle del proyecto que muestra las tareas)
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Project $project)
    {
        $user = Auth::user();
        $canViewProject = false;
        if ($user->can('projects show')) { $canViewProject = true; }
        elseif ($user->hasRole('customer') && $project->client && $project->client->user_id == $user->id && $user->can('projects view_own')) {
            $canViewProject = true;
        }
        if (!$canViewProject) { abort(403, __('This action is unauthorized.')); }

        // Redirigir a la vista de detalle del proyecto, donde se listarán las tareas.
        return redirect()->route('projects.show', $project->id);
    }

    /**
     * Fetch data for DataTables for tasks of a specific project.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request, Project $project)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $isCustomer = $user->hasRole('customer');

            // Verificar si el usuario puede ver las tareas de este proyecto
            $canViewProjectTasks = false;
            if ($user->can('tasks index')) { // Permiso general para ver tareas
                if (!$isCustomer) { // Admin/empleado puede ver tareas de cualquier proyecto (si tiene permiso general)
                    $canViewProjectTasks = true;
                } elseif ($project->client && $project->client->user_id == $user->id) { // Cliente solo puede ver tareas de sus proyectos
                    $canViewProjectTasks = true;
                }
            }

            if (!$canViewProjectTasks) {
                return DataTables::of(collect([]))->make(true); // Devolver vacío si no tiene permiso
            }

            $query = $project->tasks()->with('users')->latest('id');

            return DataTables::of($query)
                ->addIndexColumn() // Añade DT_RowIndex
                ->addColumn('assigned_users_list', function($row) {
                    return $row->users->isNotEmpty() ? $row->users->pluck('name')->implode(', ') : __('N/A');
                })
                ->editColumn('status', function($row) {
                    $status = ucfirst($row->status ?? 'pending');
                    $color = 'text-slate-500 dark:text-slate-400';
                     switch ($row->status) {
                        case 'in_progress': $color = 'text-blue-500 dark:text-blue-400'; break;
                        case 'completed': $color = 'text-green-500 dark:text-green-400'; break;
                        case 'on_hold': $color = 'text-yellow-500 dark:text-yellow-400'; break;
                        case 'cancelled': $color = 'text-red-500 dark:text-red-400'; break;
                        case 'pending': $color = 'text-orange-500 dark:text-orange-400'; break;
                    }
                    return "<span class='{$color} font-medium'>{$status}</span>";
                })
                ->editColumn('priority', function($row){
                    return ucfirst($row->priority ?? 'medium');
                })
                ->editColumn('due_date', function ($row) {
                    return $row->due_date ? $row->due_date->format('d/m/Y') : '-';
                })
                ->addColumn('action', function($row) use ($user, $isCustomer, $project){ // $project está disponible aquí
                    $actions = '<div class="flex items-center justify-center space-x-1">';
                    $isTaskOwnerOrAssigned = $row->users->contains($user->id); // Verificar si el usuario está asignado a la tarea

                    // Botón Ver Tarea
                    // Permitir ver si tiene permiso general, o es cliente dueño del proyecto, o está asignado a la tarea
                    if ($user->can('tasks show') ||
                        ($isCustomer && $project->client && $project->client->user_id == $user->id) ||
                        $isTaskOwnerOrAssigned) {
                        $actions .= '<a href="'.route('tasks.show', $row->id).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="'.__('View Task').'"><iconify-icon icon="heroicons:eye" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }

                    // Botón Editar Tarea (solo admin/empleados con permiso, o si está asignado y la tarea no está finalizada)
                    $canEditTask = false;
                    if ($user->can('tasks update') && !$isCustomer) {
                        $canEditTask = true;
                    } elseif ($isTaskOwnerOrAssigned && !in_array($row->status, ['completed', 'cancelled'])) {
                        // Permitir a usuarios asignados editar tareas no finalizadas (si se define un permiso 'update own task')
                        // if ($user->can('update own task')) $canEditTask = true;
                        // Por ahora, si está asignado puede editar si no está completada/cancelada
                        $canEditTask = true;
                    }

                    if ($canEditTask && !in_array($row->status, ['completed', 'cancelled'])) {
                         $actions .= '<a href="'.route('tasks.edit', $row->id).'" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="'.__('Edit Task').'"><iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }

                    // Botón Eliminar Tarea (Solo para admin/empleados con permiso)
                    if ($user->can('tasks delete') && !$isCustomer) {
                         $actions .= '<button class="deleteTask text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1" data-id="'.$row->id.'" data-project-id="'.$project->id.'" title="'.__('Delete Task').'"><iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon></button>';
                    }
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return abort(403, 'Unauthorized action.');
    }


    /**
     * Show the form for creating a new task for a specific project.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function create(Project $project)
    {
        if (!Auth::user()->can('tasks create') || Auth::user()->hasRole('customer')) {
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
        $project = $task->project;
        $canView = false;
        $isOwnerOrAssigned = false;

        if ($user->hasRole('customer') && $project && $project->client && $project->client->user_id == $user->id) {
            $isOwnerOrAssigned = true; // Cliente dueño del proyecto
        } elseif ($task->users->contains($user->id)) {
            $isOwnerOrAssigned = true; // Usuario asignado a la tarea
        }

        if ($user->can('tasks show') || ($isOwnerOrAssigned && $user->can('tasks view_own'))) { // Asumiendo 'tasks view_own' para ambos casos
            $canView = true;
        }

        if (!$canView) {
            abort(403, __('This action is unauthorized.'));
        }

        $task->load('project.client', 'users', 'timeHistories');

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
        $user = Auth::user();
        $canUpdate = $user->can('tasks update');
        $isAssigned = $task->users->contains($user->id);

        // Permitir editar si es admin/empleado con permiso, o si está asignado y la tarea no está finalizada
        // (Podrías tener un permiso 'update own task' para ser más granular)
        if ( !($canUpdate && !$user->hasRole('customer')) && !($isAssigned && !in_array($task->status, ['completed', 'cancelled'])) ) {
             abort(403, __('This action is unauthorized.'));
        }

        $project = $task->project;
        $assignableUsers = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'customer');
        })->orderBy('name')->pluck('name', 'id');

        $task->load('users');

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
        $user = Auth::user();
        $canUpdate = $user->can('tasks update');
        $isAssigned = $task->users->contains($user->id);

        if ( !($canUpdate && !$user->hasRole('customer')) && !($isAssigned && !in_array($task->status, ['completed', 'cancelled'])) ) {
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
            'logged_hours' => 'nullable|numeric|min:0',
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
            if (request()->ajax()) {
                return response()->json(['error' => __('This action is unauthorized.')], 403);
            }
            abort(403, __('This action is unauthorized.'));
        }

        $projectId = $task->project_id;

        DB::beginTransaction();
        try {
            $task->users()->detach();
            // $task->timeHistories()->delete(); // Si tienes esta relación y no usas onDelete cascade
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
