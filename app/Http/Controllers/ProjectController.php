<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User; // Para la lógica de permisos y asignación
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user->can('projects index') && !$user->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

        $query = Project::with(['client', 'quote'])->latest();

        if ($user->hasRole('customer')) {
            $clientProfile = Client::where('user_id', $user->id)->first();
            if ($clientProfile) {
                $query->where('client_id', $clientProfile->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $projects = $query->paginate(9);

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Projects'), 'url' => route('projects.index')],
        ];
        return view('projects.index', compact('breadcrumbItems', 'projects'));
    }

    /**
     * Fetch data for DataTables.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $isCustomer = $user->hasRole('customer');

            $query = Project::with(['client', 'quote', 'assignedUsers'])->latest(); // Eager load assignedUsers

            if ($isCustomer) {
                $clientProfile = Client::where('user_id', $user->id)->first();
                if ($clientProfile) {
                    $query->where('client_id', $clientProfile->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif (!$user->can('projects index')) {
                 $query->whereRaw('1 = 0');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('client_name', function($row){
                    return $row->client ? $row->client->name : __('N/A');
                })
                ->addColumn('quote_number', function($row){
                    return $row->quote ? $row->quote->quote_number : __('N/A');
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
                ->editColumn('start_date', function ($row) {
                    return $row->start_date ? $row->start_date->format('d/m/Y') : '-';
                })
                ->editColumn('due_date', function ($row) {
                    return $row->due_date ? $row->due_date->format('d/m/Y') : '-';
                })
                ->addColumn('assigned_users_list', function($row) { // Nueva columna para mostrar usuarios asignados
                    return $row->assignedUsers->pluck('name')->implode(', ') ?: __('N/A');
                })
                ->addColumn('action', function($row) use ($user, $isCustomer){
                    $actions = '<div class="flex items-center justify-center space-x-1">';
                    $isOwner = $isCustomer && $row->client && $row->client->user_id == $user->id;

                    if ($user->can('projects show') || ($isOwner && $user->can('projects view_own'))) {
                        $actions .= '<a href="'.route('projects.show', $row->id).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="'.__('View Project').'"><iconify-icon icon="heroicons:eye" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }
                    if ($user->can('projects update') && !$isCustomer && !in_array($row->status, ['completed', 'cancelled'])) {
                         $actions .= '<a href="'.route('projects.edit', $row->id).'" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="'.__('Edit Project').'"><iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }
                    if ($user->can('projects delete') && !$isCustomer) {
                         $actions .= '<button class="deleteProject text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1" data-id="'.$row->id.'" title="'.__('Delete Project').'"><iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon></button>';
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (!Auth::user()->can('projects create')) {
            abort(403, __('This action is unauthorized.'));
        }

        $clients = Client::orderBy('name')->pluck('name', 'id');
        $availableQuotes = Quote::where('status', 'accepted')
                                ->whereDoesntHave('project')
                                ->with('client:id,name')
                                ->orderBy('quote_number')
                                ->get(['id', 'quote_number', 'client_id', 'total_amount']);

        // Obtener usuarios que NO son clientes para asignarlos
        $assignableUsers = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'customer');
        })->orderBy('name')->pluck('name', 'id');

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => __('Create'), 'url' => route('projects.create')],
        ];
        return view('projects.create', compact('breadcrumbItems', 'clients', 'availableQuotes', 'assignableUsers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('projects create')) {
            abort(403, __('This action is unauthorized.'));
        }

        $validator = Validator::make($request->all(), [
            'project_title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id|unique:projects,quote_id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,on_hold,completed,cancelled',
            'budgeted_hours' => 'nullable|numeric|min:0',
            'assigned_project_users' => 'nullable|array', // Para los usuarios asignados al proyecto
            'assigned_project_users.*' => 'exists:users,id', // Cada ID debe existir
        ]);

        if ($validator->fails()) {
            return redirect()->route('projects.create')
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create project. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $projectData = $request->only([
                'project_title', 'client_id', 'quote_id', 'description',
                'start_date', 'due_date', 'status', 'budgeted_hours'
            ]);

            if ($request->filled('quote_id')) {
                $quote = Quote::find($request->input('quote_id'));
                if ($quote && $quote->client_id != $request->input('client_id')) {
                     return redirect()->route('projects.create')
                        ->withInput()
                        ->with('error', __('The selected client does not match the client of the selected quote.'));
                }
                $projectData['client_id'] = $quote->client_id;
            }

            $project = Project::create($projectData);

            // Sincronizar usuarios asignados al proyecto
            if ($request->has('assigned_project_users')) {
                $project->assignedUsers()->sync($request->input('assigned_project_users'));
            } else {
                $project->assignedUsers()->sync([]); // Desasociar todos si no se envía nada
            }

            DB::commit();
            Log::info("Project #{$project->id} created. Associated quote ID: {$project->quote_id}");
            return redirect()->route('projects.show', $project->id)->with('success', __('Project created successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating project: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('projects.create')
                        ->withInput()
                        ->with('error', __('An error occurred while creating the project.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function show(Project $project)
    {
        $user = Auth::user();
        $canView = $user->can('projects show');
        $isOwner = $user->hasRole('customer') && $project->client && $project->client->user_id == $user->id;

        if ($isOwner && $user->can('projects view_own')) {
            $canView = true;
        }
        if (!$canView && !$isOwner) {
             abort(403, __('This action is unauthorized.'));
        }

        $project->load('client', 'quote', 'tasks', 'assignedUsers'); // Cargar usuarios asignados

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $project->project_title, 'url' => route('projects.show', $project->id)],
        ];
        return view('projects.show', compact('project', 'breadcrumbItems'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function edit(Project $project)
    {
        if (!Auth::user()->can('projects update') || Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }
        if (in_array($project->status, ['completed', 'cancelled'])) {
             return redirect()->route('projects.show', $project->id)->with('error', __('This project cannot be edited because it is already :status.', ['status' => $project->status]));
        }

        $clients = Client::orderBy('name')->pluck('name', 'id');
        $availableQuotes = Quote::where('status', 'accepted')
                                ->with('client:id,name')
                                ->where(function($query) use ($project) {
                                    $query->whereDoesntHave('project')
                                          ->orWhere('id', $project->quote_id);
                                })->orderBy('quote_number')
                                ->get(['id', 'quote_number', 'client_id', 'total_amount']);

        $assignableUsers = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'customer');
        })->orderBy('name')->pluck('name', 'id');

        $project->load('assignedUsers'); // Cargar usuarios ya asignados para preseleccionar

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Projects'), 'url' => route('projects.index')],
            ['name' => $project->project_title, 'url' => route('projects.show', $project->id)],
            ['name' => __('Edit'), 'url' => route('projects.edit', $project->id)],
        ];
        return view('projects.edit', compact('project', 'breadcrumbItems', 'clients', 'availableQuotes', 'assignableUsers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Project $project)
    {
         if (!Auth::user()->can('projects update') || Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }
         if (in_array($project->status, ['completed', 'cancelled'])) {
             return redirect()->route('projects.show', $project->id)->with('error', __('This project cannot be edited because it is already :status.', ['status' => $project->status]));
        }

        $validator = Validator::make($request->all(), [
            'project_title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id|unique:projects,quote_id,'.$project->id,
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,on_hold,completed,cancelled',
            'budgeted_hours' => 'nullable|numeric|min:0',
            'assigned_project_users' => 'nullable|array',
            'assigned_project_users.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('projects.edit', $project->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to update project. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $projectData = $request->only([
                'project_title', 'client_id', 'quote_id', 'description',
                'start_date', 'due_date', 'status', 'budgeted_hours'
            ]);
             if ($request->filled('quote_id')) {
                $quote = Quote::find($request->input('quote_id'));
                if ($quote && $quote->client_id != $request->input('client_id')) {
                     return redirect()->route('projects.edit', $project->id)
                        ->withInput()
                        ->with('error', __('The selected client does not match the client of the selected quote.'));
                }
                 $projectData['client_id'] = $quote->client_id;
            } else {
                $projectData['quote_id'] = null;
            }

            $project->update($projectData);

            // Sincronizar usuarios asignados al proyecto
            if ($request->has('assigned_project_users')) {
                $project->assignedUsers()->sync($request->input('assigned_project_users'));
            } else {
                $project->assignedUsers()->sync([]);
            }

            DB::commit();
            return redirect()->route('projects.show', $project->id)->with('success', __('Project updated successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating project: '.$e->getMessage());
            return redirect()->route('projects.edit', $project->id)
                        ->withInput()
                        ->with('error', __('An error occurred while updating the project.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project)
    {
        if (!Auth::user()->can('projects delete') || Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

        DB::beginTransaction();
        try {
            // Antes de eliminar el proyecto, desasociar usuarios para limpiar la tabla pivote
            $project->assignedUsers()->detach();
            // Considerar qué hacer con las tareas asociadas (onDelete('cascade') o eliminarlas manualmente)
            // $project->tasks()->delete();

            $project->delete();
            DB::commit();
            return response()->json(['success' => __('Project deleted successfully!')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting project: '.$e->getMessage());
            return response()->json(['error' => __('An error occurred while deleting the project.')], 500);
        }
    }
}
