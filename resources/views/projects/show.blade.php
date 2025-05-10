<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- Asegúrate de pasar $breadcrumbItems y $project desde ProjectController@show --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Project Details') . ': ' . $project->project_title" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    {{-- Alert end --}}

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">

            {{-- Botones de Acción --}}
            <div class="flex flex-wrap justify-end items-center space-x-3 mb-6">
                @can('projects update') {{-- Solo mostrar si tiene permiso general de actualizar proyectos --}}
                    @if (!in_array($project->status, ['completed', 'cancelled']))
                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-secondary btn-sm inline-flex items-center">
                            <iconify-icon icon="heroicons:pencil-square" class="text-lg mr-1"></iconify-icon>
                            {{ __('Edit Project') }}
                        </a>
                    @endif
                @endcan
                {{-- Aquí podrías añadir más botones, como "Añadir Tarea" en el futuro --}}
                {{-- <a href="{{-- route('projects.tasks.create', $project->id) --}}" class="btn btn-primary btn-sm inline-flex items-center">
                    <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                    {{ __('Add Task') }}
                </a> --}}
            </div>

            {{-- Detalles del Proyecto --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Project Title') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300 font-semibold text-base">{{ $project->project_title }}</p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Client') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">
                        @if($project->client)
                            <a href="{{ route('clients.show', $project->client_id) }}" class="hover:underline text-indigo-600 dark:text-indigo-400">
                                {{ $project->client->name }}
                            </a>
                        @else
                            {{ __('N/A') }}
                        @endif
                    </p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Associated Quote') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">
                        @if($project->quote)
                            <a href="{{ route('quotes.show', $project->quote_id) }}" class="hover:underline text-indigo-600 dark:text-indigo-400">
                                {{ $project->quote->quote_number }}
                            </a>
                        @else
                            {{ __('None') }}
                        @endif
                    </p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Status') }}</h5>
                    @php
                        $status = ucfirst($project->status);
                        $color = 'text-slate-500 dark:text-slate-400';
                        switch ($project->status) {
                            case 'in_progress': $color = 'text-blue-500'; break;
                            case 'completed': $color = 'text-green-500'; break;
                            case 'on_hold': $color = 'text-yellow-500'; break;
                            case 'cancelled': $color = 'text-red-500'; break;
                            case 'pending': $color = 'text-orange-500'; break;
                        }
                    @endphp
                    <span class="font-semibold {{ $color }}">{{ __($status) }}</span>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Start Date') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $project->start_date ? $project->start_date->format('d/m/Y') : __('Not set') }}</p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Due Date') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $project->due_date ? $project->due_date->format('d/m/Y') : __('Not set') }}</p>
                </div>
                 <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Budgeted Hours') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $project->budgeted_hours ? number_format($project->budgeted_hours, 2) . 'h' : __('N/A') }}</p>
                </div>
                 <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Actual Hours') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $project->actual_hours ? number_format($project->actual_hours, 2) . 'h' : '0.00h' }}</p>
                </div>
                 <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Created At') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $project->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            {{-- Descripción del Proyecto --}}
            @if($project->description)
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div>
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Project Description') }}</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-wrap">{{ $project->description }}</p>
                </div>
            @endif

            {{-- Sección de Tareas (Placeholder por ahora) --}}
            <hr class="my-6 border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Tasks') }}</h3>
                @can('tasks create') {{-- Asumiendo permiso para crear tareas --}}
                    {{-- <a href="{{-- route('projects.tasks.create', $project->id) --}}" class="btn btn-outline-primary btn-sm">
                        {{ __('Add New Task') }}
                    </a> --}}
                @endcan
            </div>
            <div id="projectTasksContainer">
                {{-- Aquí se listarán las tareas del proyecto (ej. con DataTables o un bucle @foreach) --}}
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Task management will be implemented here.') }}</p>
                {{--
                @if($project->tasks && $project->tasks->count() > 0)
                    <ul>
                        @foreach($project->tasks as $task)
                            <li>{{ $task->title }} - {{ $task->status }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>{{ __('No tasks assigned to this project yet.') }}</p>
                @endif
                --}}
            </div>

             {{-- Notas Internas (si aplica y hay permiso) --}}
            @if($project->internal_notes && Auth::user()->can('projects show')) {{-- O un permiso más específico --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div>
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Internal Notes') }}</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-wrap bg-slate-50 dark:bg-slate-700 p-3 rounded-md">{{ $project->internal_notes }}</p>
                </div>
            @endif

        </div>
    </div>

    @push('styles')
        {{-- Añadir estilos específicos si es necesario --}}
    @endpush

    @push('scripts')
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
        {{-- Añadir scripts específicos si es necesario --}}
    @endpush
</x-app-layout>
