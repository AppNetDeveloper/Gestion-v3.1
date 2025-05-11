<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- $project, $task y $breadcrumbItems se pasan desde TaskController@edit --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Edit Task') . ': ' . $task->title" />
    </div>

    {{-- Alert start --}}
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
            <p class="font-semibold mb-2">{{ __('Please correct the following errors:') }}</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- Alert end --}}

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">{{ __('Edit Task Details for Project: :projectTitle', ['projectTitle' => $project->project_title]) }}</h3>

            {{-- La ruta para update de tareas shallow es tasks.update --}}
            <form action="{{ route('tasks.update', $task->id) }}" method="POST" id="taskForm">
                @csrf
                @method('PUT') {{-- Método para actualizar --}}

                {{-- Campo Título de la Tarea --}}
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Task Title') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title', $task->title) }}"
                           class="inputField w-full p-3 border {{ $errors->has('title') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                           placeholder="{{ __('Enter task title...') }}" required>
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Campo Descripción --}}
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Description') }}
                    </label>
                    <textarea id="description" name="description" rows="4"
                              class="inputField w-full p-3 border {{ $errors->has('description') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                              placeholder="{{ __('Enter task description...') }}">{{ old('description', $task->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    {{-- Estado --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Status') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" class="inputField w-full p-3 border {{ $errors->has('status') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="pending" {{ old('status', $task->status) == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                            <option value="in_progress" {{ old('status', $task->status) == 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                            <option value="completed" {{ old('status', $task->status) == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                            <option value="on_hold" {{ old('status', $task->status) == 'on_hold' ? 'selected' : '' }}>{{ __('On Hold') }}</option>
                            <option value="cancelled" {{ old('status', $task->status) == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Prioridad --}}
                    <div>
                        <label for="priority" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Priority') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="priority" name="priority" class="inputField w-full p-3 border {{ $errors->has('priority') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="low" {{ old('priority', $task->priority) == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                            <option value="medium" {{ old('priority', $task->priority) == 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                            <option value="high" {{ old('priority', $task->priority) == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                            <option value="urgent" {{ old('priority', $task->priority) == 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                        </select>
                        @error('priority') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Horas Estimadas --}}
                    <div>
                        <label for="estimated_hours" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Estimated Hours') }}
                        </label>
                        <input type="number" id="estimated_hours" name="estimated_hours" step="0.1" min="0" value="{{ old('estimated_hours', $task->estimated_hours) }}"
                               class="inputField w-full p-3 border {{ $errors->has('estimated_hours') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               placeholder="0.0">
                        @error('estimated_hours') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Horas Registradas (solo si quieres que se editen aquí) --}}
                    <div>
                        <label for="logged_hours" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Logged Hours') }}
                        </label>
                        <input type="number" id="logged_hours" name="logged_hours" step="0.1" min="0" value="{{ old('logged_hours', $task->logged_hours) }}"
                               class="inputField w-full p-3 border {{ $errors->has('logged_hours') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               placeholder="0.0">
                        @error('logged_hours') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>


                    {{-- Fecha de Inicio --}}
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Start Date') }}
                        </label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $task->start_date?->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('start_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                        @error('start_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha de Fin Prevista --}}
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Due Date') }}
                        </label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('due_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                        @error('due_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Usuarios Asignados --}}
                    <div class="lg:col-span-3 md:col-span-2">
                        <label for="assigned_users" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Assign Users') }}
                        </label>
                        <select id="assigned_users" name="assigned_users[]" class="inputField select2 w-full" multiple="multiple">
                            @php
                                $assignedUserIds = old('assigned_users', $task->users->pluck('id')->toArray());
                            @endphp
                            @foreach($assignableUsers ?? [] as $id => $name)
                                <option value="{{ $id }}" {{ (collect($assignedUserIds)->contains($id)) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_users') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        @error('assigned_users.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>


                {{-- Botones de Acción --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Update Task') }}
                    </button>
                </div>

            </form>
        </div>
    </div>

    @push('styles')
        {{-- Cargar CSS de Select2 aquí si no está global --}}
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            /* Estilos para Select2 (copiados de quotes.create) */
            .select2-container--default .select2-selection--single { background-color: transparent !important; border: 1px solid #e2e8f0 !important; border-radius: 0.375rem !important; height: calc(1.5em + 0.75rem + 2px + 0.75rem) !important; padding-top: 0.75rem; padding-bottom: 0.75rem; }
            .dark .select2-container--default .select2-selection--single { border: 1px solid #475569 !important; background-color: #0f172a !important; }
            .select2-container--default .select2-selection--single .select2-selection__rendered { color: #0f172a !important; line-height: 1.5rem !important; padding-left: 0.75rem !important; padding-right: 2rem !important; }
            .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #cbd5e1 !important; }
            .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + 0.75rem + 2px + 0.75rem - 2px) !important; right: 0.5rem !important; }
            .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: #64748b transparent transparent transparent !important; }
            .dark .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: #94a3b8 transparent transparent transparent !important; }
            .select2-dropdown { background-color: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 0.375rem !important; }
            .dark .select2-dropdown { background-color: #1e293b !important; border: 1px solid #334155 !important; }
            .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #e2e8f0 !important; background-color: #fff !important; color: #0f172a !important; }
            .dark .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #475569 !important; background-color: #374151 !important; color: #cbd5e1 !important; }
            .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #6366f1 !important; color: white !important; }
            .select2-results__option { color: #374151 !important; }
            .dark .select2-results__option { color: #cbd5e1 !important; }
            .dark .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #4f46e5 !important; }
            /* Estilos para Select2 Multiple */
            .select2-container--default .select2-selection--multiple { background-color: transparent !important; border: 1px solid #e2e8f0 !important; border-radius: 0.375rem !important; min-height: calc(1.5em + 0.75rem + 2px + 0.75rem) !important; padding: 0.375rem 0.5rem !important; }
            .dark .select2-container--default .select2-selection--multiple { border: 1px solid #475569 !important; background-color: #0f172a !important; }
            .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #e0e7ff !important; border: 1px solid #c7d2fe !important; color: #3730a3 !important; border-radius: 0.25rem !important; padding: 0.125rem 0.5rem !important; margin-top: 0.2rem !important; }
            .dark .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #374151 !important; border: 1px solid #4b5563 !important; color: #e2e8f0 !important; }
            .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #4338ca !important; margin-right: 0.25rem !important; }
            .dark .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #a5b4fc !important; }
        </style>
    @endpush

    @push('scripts')
        {{-- Cargar jQuery PRIMERO (si no está global) --}}
        {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
        {{-- Cargar Select2 DESPUÉS de jQuery (si no está global) --}}
        {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            $(function() { // Document ready
                // Inicializar Select2
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2').each(function() {
                        const $select = $(this);
                        let options = {
                            placeholder: $select.find('option[disabled]').text() || $select.find('option[value=""]').text() || 'Select an option',
                            allowClear: !$select.prop('multiple'),
                            width: '100%'
                        };
                        if ($select.prop('multiple')) {
                            options.placeholder = "{{ __('Select users...') }}";
                        }
                        $select.select2(options);
                    });
                } else {
                    console.warn('Select2 plugin is not loaded.');
                }
            });
        </script>
    @endpush
</x-app-layout>
