<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- $timeEntry, $task, y $breadcrumbItems se pasan desde TaskTimeHistoryController@editEntry --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Edit Time Entry')" />
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
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-2">
                {{ __('Editing Time Entry for Task:') }} <a href="{{ route('tasks.show', $task->id) }}" class="text-indigo-600 hover:underline">{{ $task->title }}</a>
            </h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                {{ __('Project:') }} <a href="{{ route('projects.show', $task->project->id) }}" class="text-indigo-600 hover:underline">{{ $task->project->project_title }}</a>
            </p>

            <form action="{{ route('task_time_entries.update', $timeEntry->id) }}" method="POST" id="timeEntryForm">
                @csrf
                @method('PUT') {{-- Método para actualizar --}}

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {{-- Log Date --}}
                    <div>
                        <label for="log_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Log Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="log_date" name="log_date"
                               value="{{ old('log_date', $timeEntry->log_date ? \Carbon\Carbon::parse($timeEntry->log_date)->format('Y-m-d') : '') }}"
                               class="inputField w-full p-3 border {{ $errors->has('log_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               required>
                        @error('log_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Start Time --}}
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Start Time') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" id="start_time" name="start_time"
                               value="{{ old('start_time', $timeEntry->start_time ? \Carbon\Carbon::parse($timeEntry->start_time)->format('Y-m-d\TH:i') : '') }}"
                               class="inputField w-full p-3 border {{ $errors->has('start_time') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               required>
                        @error('start_time') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- End Time --}}
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('End Time') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" id="end_time" name="end_time"
                               value="{{ old('end_time', $timeEntry->end_time ? \Carbon\Carbon::parse($timeEntry->end_time)->format('Y-m-d\TH:i') : '') }}"
                               class="inputField w-full p-3 border {{ $errors->has('end_time') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                               required>
                        @error('end_time') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Duration (informativo, se calcula en backend y opcionalmente en frontend) --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Duration (minutes)') }}
                        </label>
                        <input type="text" id="duration_display"
                               value="{{ $timeEntry->duration_minutes }}"
                               class="inputField w-full p-3 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-700 text-slate-400"
                               readonly placeholder="{{ __('Calculated automatically') }}">
                    </div>
                </div>

                {{-- Campo Descripción/Notas --}}
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('Notes / Description') }}
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="inputField w-full p-3 border {{ $errors->has('description') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                              placeholder="{{ __('Enter notes for this time entry...') }}">{{ old('description', $timeEntry->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Botones de Acción --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Update Time Entry') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        {{-- Cargar jQuery PRIMERO (si no está global) --}}
        {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
        <script>
            $(function() { // Document ready
                // Opcional: Añadir JS para calcular la duración en tiempo real mientras se editan las fechas/horas
                function calculateDuration() {
                    const startTimeStr = $('#start_time').val();
                    const endTimeStr = $('#end_time').val();

                    if (startTimeStr && endTimeStr) {
                        // Los inputs datetime-local devuelven strings en formato YYYY-MM-DDTHH:MM
                        // que el constructor de Date de JavaScript puede interpretar.
                        const start = new Date(startTimeStr);
                        const end = new Date(endTimeStr);

                        if (end > start) {
                            const diffMs = end - start; // Diferencia en milisegundos
                            const diffMins = Math.round(diffMs / 60000); // Convertir a minutos y redondear
                            $('#duration_display').val(diffMins);
                        } else {
                            $('#duration_display').val(''); // O 0, o un mensaje de error
                        }
                    } else {
                        $('#duration_display').val('');
                    }
                }

                // Añadir listeners a los campos de fecha/hora
                $('#start_time, #end_time').on('change input', calculateDuration);

                // Calcular la duración al cargar la página si ya hay valores
                calculateDuration();
            });
        </script>
    @endpush
</x-app-layout>
