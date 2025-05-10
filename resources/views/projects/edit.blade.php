<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- Asegúrate de pasar $breadcrumbItems y $project desde ProjectController@edit --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Edit Project') . ': ' . $project->project_title" />
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
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">{{ __('Edit Project Details') }}</h3>

            <form action="{{ route('projects.update', $project->id) }}" method="POST" id="projectForm">
                @csrf
                @method('PUT') {{-- Importante para formularios de actualización --}}

                {{-- Sección Datos Generales del Proyecto --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    {{-- Título del Proyecto --}}
                    <div class="lg:col-span-2">
                        <label for="project_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Project Title') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="project_title" name="project_title" value="{{ old('project_title', $project->project_title) }}"
                               class="inputField w-full p-3 border {{ $errors->has('project_title') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               placeholder="{{ __('Enter project title...') }}" required>
                        @error('project_title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Cliente --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Client') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="client_id" name="client_id" class="inputField select2 w-full p-3 border {{ $errors->has('client_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="" disabled>{{ __('Select a client') }}</option>
                            @foreach($clients ?? [] as $id => $name)
                                <option value="{{ $id }}" {{ (old('client_id', $project->client_id) == $id) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Presupuesto Asociado (Opcional) --}}
                    <div>
                        <label for="quote_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Quote') }} ({{ __('Optional') }})
                        </label>
                        <select id="quote_id" name="quote_id" class="inputField select2 w-full p-3 border {{ $errors->has('quote_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                            <option value="">{{ __('None') }}</option>
                            @foreach($availableQuotes ?? [] as $quote_opt)
                                <option value="{{ $quote_opt->id }}"
                                        data-client-id="{{ $quote_opt->client_id }}"
                                        {{ (old('quote_id', $project->quote_id) == $quote_opt->id) ? 'selected' : '' }}>
                                    {{ $quote_opt->quote_number }} - {{ $quote_opt->client->name ?? '' }} ({{ number_format($quote_opt->total_amount, 2) }}€)
                                </option>
                            @endforeach
                        </select>
                        @error('quote_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Estado del Proyecto --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Status') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" class="inputField w-full p-3 border {{ $errors->has('status') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="pending" {{ old('status', $project->status) == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                            <option value="in_progress" {{ old('status', $project->status) == 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                            <option value="on_hold" {{ old('status', $project->status) == 'on_hold' ? 'selected' : '' }}>{{ __('On Hold') }}</option>
                            <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                            <option value="cancelled" {{ old('status', $project->status) == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Horas Presupuestadas --}}
                    <div>
                        <label for="budgeted_hours" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Budgeted Hours') }}
                        </label>
                        <input type="number" id="budgeted_hours" name="budgeted_hours" step="0.1" min="0" value="{{ old('budgeted_hours', $project->budgeted_hours) }}"
                               class="inputField w-full p-3 border {{ $errors->has('budgeted_hours') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               placeholder="0.0">
                        @error('budgeted_hours') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha de Inicio --}}
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Start Date') }}
                        </label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('start_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                        @error('start_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha de Fin Prevista --}}
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Due Date') }}
                        </label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('due_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                        @error('due_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Usuarios Asignados --}}
                    <div class="lg:col-span-3 md:col-span-2">
                        <label for="assigned_project_users" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Assign Users to Project') }}
                        </label>
                        <select id="assigned_project_users" name="assigned_project_users[]" class="inputField select2 w-full" multiple="multiple">
                            @php
                                $assignedUserIds = old('assigned_project_users', $project->assignedUsers->pluck('id')->toArray());
                            @endphp
                            @foreach($assignableUsers ?? [] as $id => $name)
                                <option value="{{ $id }}" {{ (collect($assignedUserIds)->contains($id)) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_project_users') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                         @error('assigned_project_users.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Descripción del Proyecto --}}
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Project Description') }}
                    </label>
                    <textarea id="description" name="description" rows="4"
                              class="inputField w-full p-3 border {{ $errors->has('description') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                              placeholder="{{ __('Enter a detailed description of the project...') }}">{{ old('description', $project->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>


                {{-- Botones de Acción --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Update Project') }}
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
        {{-- Cargar jQuery PRIMERO --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        {{-- Cargar Select2 DESPUÉS de jQuery --}}
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            $(function() { // Document ready
                // Inicializar Select2
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2').each(function() {
                        const $select = $(this);
                        let options = {
                            placeholder: $select.find('option[disabled]').text() || $select.find('option[value=""]').text() || 'Select an option',
                            allowClear: !$select.prop('multiple'), // Permitir limpiar solo si no es múltiple y tiene opción vacía
                            width: '100%'
                        };
                        if ($select.prop('multiple')) {
                            options.placeholder = "{{ __('Select users...') }}";
                        }
                        $select.select2(options);
                    });

                    // Cuando se selecciona un presupuesto, intentar pre-seleccionar el cliente
                    $('#quote_id').on('select2:select', function (e) {
                        const selectedQuoteOption = e.params.data.element;
                        if (selectedQuoteOption) {
                            const clientId = $(selectedQuoteOption).data('client-id');
                            if (clientId) {
                                $('#client_id').val(clientId).trigger('change.select2');
                            }
                        }
                    });
                } else {
                    console.warn('Select2 plugin is not loaded.');
                }
            });
        </script>
    @endpush
</x-app-layout>
