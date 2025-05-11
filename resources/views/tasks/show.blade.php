<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- $task y $breadcrumbItems se pasan desde TaskController@show --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Task Details') . ': ' . $task->title" />
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
        <div class="card-header flex justify-between items-center p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ $task->title }}</h3>
            <div>
                @php
                    $user = Auth::user();
                    $isCustomer = $user->hasRole('customer');
                    $isAssigned = $task->users->contains($user->id);
                    $canEditTask = false;
                    if ($user->can('tasks update') && !$isCustomer) { $canEditTask = true; }
                    elseif ($isAssigned && !in_array($task->status, ['completed', 'cancelled'])) { $canEditTask = true; }
                @endphp

                @if ($canEditTask && !in_array($task->status, ['completed', 'cancelled']))
                    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-outline-secondary btn-sm inline-flex items-center">
                        <iconify-icon icon="heroicons:pencil-square" class="text-lg mr-1"></iconify-icon>
                        {{ __('Edit Task') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body p-6 space-y-6">
            {{-- Detalles de la Tarea --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Project') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">
                        @if($task->project)
                            <a href="{{ route('projects.show', $task->project->id) }}" class="hover:underline text-indigo-600 dark:text-indigo-400">
                                {{ $task->project->project_title }}
                            </a>
                        @else
                            {{ __('N/A') }}
                        @endif
                    </p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Client') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">
                        {{ $task->project?->client?->name ?? __('N/A') }}
                    </p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Status') }}</h5>
                    @php
                        $statusText = ucfirst($task->status);
                        $statusColor = 'text-slate-500 dark:text-slate-400';
                        switch ($task->status) {
                            case 'in_progress': $statusColor = 'text-blue-500 dark:text-blue-400'; break;
                            case 'completed': $statusColor = 'text-green-500 dark:text-green-400'; break;
                            case 'on_hold': $statusColor = 'text-yellow-500 dark:text-yellow-400'; break;
                            case 'cancelled': $statusColor = 'text-red-500 dark:text-red-400'; break;
                            case 'pending': $statusColor = 'text-orange-500 dark:text-orange-400'; break;
                        }
                    @endphp
                    <span class="font-semibold {{ $statusColor }}">{{ __($statusText) }}</span>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Priority') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300 font-semibold">{{ __(ucfirst($task->priority)) }}</p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Start Date') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $task->start_date ? $task->start_date->format('d/m/Y') : __('Not set') }}</p>
                </div>
                <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Due Date') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $task->due_date ? $task->due_date->format('d/m/Y') : __('Not set') }}</p>
                </div>
                 <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Estimated Hours') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $task->estimated_hours ? number_format($task->estimated_hours, 2) . 'h' : __('N/A') }}</p>
                </div>
                 <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Logged Hours') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $task->logged_hours ? number_format($task->logged_hours, 2) . 'h' : '0.00h' }}</p>
                </div>
                 <div>
                    <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Created At') }}</h5>
                    <p class="text-slate-700 dark:text-slate-300">{{ $task->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            {{-- Descripción de la Tarea --}}
            @if($task->description)
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div>
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Task Description') }}</h4>
                    <div class="prose dark:prose-invert max-w-none text-sm text-slate-500 dark:text-slate-400">
                        {!! nl2br(e($task->description)) !!}
                    </div>
                </div>
            @endif

            {{-- Usuarios Asignados --}}
            @if($task->users->isNotEmpty())
            <hr class="my-6 border-slate-200 dark:border-slate-700">
            <div>
                <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Assigned Users') }}</h4>
                <ul class="list-disc list-inside text-sm text-slate-500 dark:text-slate-400">
                    @foreach ($task->users as $assignedUser)
                        <li>{{ $assignedUser->name }} ({{ $assignedUser->email }})</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Aquí podrías añadir una sección para registrar tiempo (TaskTimeHistory) en el futuro --}}

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
