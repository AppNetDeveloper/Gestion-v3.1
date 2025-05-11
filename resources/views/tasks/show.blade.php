<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
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

    {{-- Indicadores Visuales --}}
    @php
        $isTaskPendingOrInProgress = !in_array($task->status, ['completed', 'cancelled']);
        $daysToDisplay = 0;
        $isOverdue = false;
        $isDueSoon = false;

        if ($task->due_date && $isTaskPendingOrInProgress) {
            $dueDateCarbon = \Carbon\Carbon::parse($task->due_date)->startOfDay();
            $today = \Carbon\Carbon::now()->startOfDay();

            if ($dueDateCarbon->lt($today)) {
                $isOverdue = true;
            } else {
                $daysToDisplay = $today->diffInDays($dueDateCarbon, false);
                 if ($daysToDisplay <= 3 && $daysToDisplay >= 0) {
                    $isDueSoon = true;
                }
            }
        }
    @endphp

    @if ($isOverdue)
        <x-alert :message="__('Warning: This task is overdue!')" :type="'danger'" class="mb-4" />
    @elseif ($isDueSoon)
        <x-alert :message="__('Notice: This task is due in :days days.', ['days' => $daysToDisplay])" :type="'warning'" class="mb-4" />
    @endif

    @if ($task->estimated_hours && $task->logged_hours && $task->logged_hours > $task->estimated_hours && $isTaskPendingOrInProgress)
        <x-alert :message="__('Warning: Logged hours (:logged) have exceeded estimated hours (:estimated).', ['logged' => number_format($task->logged_hours,2), 'estimated' => number_format($task->estimated_hours,2)])" :type="'warning'" class="mb-4" />
    @endif


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Columna Principal: Detalles de la Tarea y Control de Tiempo --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
                <div class="card-header flex justify-between items-center p-6 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ $task->title }}</h3>
                    <div>
                        @php
                            $user = Auth::user();
                            $isCustomer = $user->hasRole('customer');
                            $isAssignedToTask = $task->users->contains($user->id);
                            $canEditTask = false;
                            if ($user->can('tasks update') && !$isCustomer) { $canEditTask = true; }
                            elseif ($isAssignedToTask && !in_array($task->status, ['completed', 'cancelled'])) { $canEditTask = true; }
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Project') }}</h5>
                            <p class="text-slate-700 dark:text-slate-300">
                                @if($task->project)
                                    <a href="{{ route('projects.show', $task->project->id) }}" class="hover:underline text-indigo-600 dark:text-indigo-400">
                                        {{ $task->project->project_title }}
                                    </a>
                                @else {{ __('N/A') }} @endif
                            </p>
                        </div>
                        <div>
                            <h5 class="text-slate-500 dark:text-slate-400 text-xs font-medium uppercase mb-1">{{ __('Client') }}</h5>
                            <p class="text-slate-700 dark:text-slate-300">{{ $task->project?->client?->name ?? __('N/A') }}</p>
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
                            <p id="taskLoggedHours" class="text-slate-700 dark:text-slate-300 font-semibold">{{ number_format($task->logged_hours ?? 0, 2) . 'h' }}</p>
                        </div>
                    </div>

                    @if($task->description)
                        <hr class="my-4 border-slate-200 dark:border-slate-700">
                        <div>
                            <h5 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Task Description') }}</h5>
                            <div class="prose dark:prose-invert max-w-none text-sm text-slate-500 dark:text-slate-400">
                                {!! nl2br(e($task->description)) !!}
                            </div>
                        </div>
                    @endif

                    @if($task->users->isNotEmpty())
                    <hr class="my-4 border-slate-200 dark:border-slate-700">
                    <div>
                        <h5 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Assigned Users') }}</h5>
                        <ul class="list-disc list-inside text-sm text-slate-500 dark:text-slate-400">
                            @foreach ($task->users as $assignedUser)
                                <li>{{ $assignedUser->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Sección Control de Tiempo --}}
            @php
                // $activeTimeLog ya se pasa desde el controlador
                $canCurrentUserLogTime = (Auth::user()->can('tasks log_time') || $task->users->contains(Auth::user())) && !Auth::user()->hasRole('customer');
            @endphp

            @if($canCurrentUserLogTime && !in_array($task->status, ['completed', 'cancelled']))
            <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg mt-6">
                <div class="card-header p-6 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Time Tracking') }}</h3>
                </div>
                <div class="card-body p-6">
                    <div class="mb-4">
                        <label for="time_entry_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Notes for this time entry (optional)') }}</label>
                        <input type="text" id="time_entry_description" class="inputField w-full p-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900" placeholder="{{ __('Brief description of work done...') }}">
                    </div>
                    <div id="timeTrackerControls">
                        @if($activeTimeLog)
                            <p class="mb-2 text-sm text-slate-600 dark:text-slate-300">
                                {{ __('Timer started at:') }} <span id="timerStartTimeFormatted">{{ $activeTimeLog->start_time->format('d/m/Y H:i:s') }}</span>
                            </p>
                            <p class="mb-4 text-lg font-semibold text-blue-600 dark:text-blue-400">
                                {{ __('Time Elapsed:') }} <span id="elapsedTimeDisplay">00:00:00</span>
                            </p>
                            <button type="button" id="stopTimerBtn" data-task-id="{{ $task->id }}" class="btn btn-danger inline-flex items-center">
                                <iconify-icon icon="heroicons:stop-circle-solid" class="text-lg mr-1"></iconify-icon>
                                {{ __('Stop Timer') }}
                            </button>
                        @else
                            <button type="button" id="startTimerBtn" data-task-id="{{ $task->id }}" class="btn btn-success inline-flex items-center">
                                <iconify-icon icon="heroicons:play-circle-solid" class="text-lg mr-1"></iconify-icon>
                                {{ __('Start Timer') }}
                            </button>
                        @endif
                    </div>
                    <div id="timeTrackerMessage" class="mt-2 text-sm"></div>
                </div>
            </div>
            @endif
        </div>

        {{-- Columna Derecha: Historial de Tiempos --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
                 <div class="card-header p-6 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Time Log History') }}</h3>
                </div>
                <div class="card-body p-6">
                    <ul id="timeLogList" class="space-y-3">
                        {{-- *** CORRECCIÓN AQUÍ: Iterar sobre la colección ya cargada y filtrada en el controlador si es necesario *** --}}
                        {{-- O, si $task->timeHistories ya está filtrado y ordenado en el controlador, simplemente iterar sobre él. --}}
                        {{-- Por ahora, filtramos y ordenamos aquí, pero asegurándonos de que 'user' esté cargado. --}}
                        @forelse ($task->timeHistories->whereNotNull('end_time')->sortByDesc('start_time') as $entry)
                            <li class="time-log-entry border-b border-slate-100 dark:border-slate-700 pb-3 last:border-b-0" data-entry-id="{{ $entry->id }}">
                                <div class="text-sm text-slate-600 dark:text-slate-300">
                                    {{-- Acceder a user->name solo si user existe --}}
                                    <span class="font-medium">{{ $entry->user?->name ?? __('Unknown User') }}</span>
                                    - {{ $entry->start_time->format('d/m/y H:i') }}
                                    @if($entry->end_time)
                                        - {{ $entry->end_time->format('H:i') }}
                                        (<span class="text-indigo-600 dark:text-indigo-400">{{ round($entry->duration_minutes / 60, 2) }}h</span>)
                                    @else
                                        (<span class="text-blue-500">{{ __('In progress') }}</span>)
                                    @endif
                                </div>
                                @if($entry->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 whitespace-pre-wrap">{{ $entry->description }}</p>
                                @endif
                                @if(Auth::id() == $entry->user_id || (Auth::check() && Auth::user()->can('tasks update')))
                                <div class="mt-1 text-xs">
                                    <a href="{{ route('task_time_entries.edit', $entry->id) }}" class="text-blue-500 hover:underline mr-2">{{ __('Edit') }}</a>
                                    <button type="button" class="deleteTimeEntryBtn text-red-500 hover:underline" data-entry-id="{{ $entry->id }}">{{ __('Delete') }}</button>
                                </div>
                                @endif
                            </li>
                        @empty
                            <li id="noTimeEntries" class="text-sm text-slate-500 dark:text-slate-400">{{ __('No time entries logged for this task yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>


    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            /* ... (tus estilos existentes para Select2 y generales) ... */
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            $(function() { // Document ready
                const taskId = {{ Js::from($task->id) }};
                console.log('Task ID for timer:', taskId);

                let timerInterval;
                let activeTimeLogId = {{ Js::from($activeTimeLog?->id) }};
                let timerStartTime = {{ Js::from($activeTimeLog?->start_time?->toIso8601String()) }};

                console.log('Initial activeTimeLogId:', activeTimeLogId);
                console.log('Initial timerStartTime:', timerStartTime);

                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                function formatTime(seconds) {
                    const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
                    const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
                    const s = Math.floor(seconds % 60).toString().padStart(2, '0');
                    return `${h}:${m}:${s}`;
                }

                function updateElapsedTime() {
                    if (timerStartTime) {
                        const now = new Date();
                        const start = new Date(timerStartTime);
                        const diffSeconds = Math.round((now - start) / 1000);
                        $('#elapsedTimeDisplay').text(formatTime(diffSeconds));
                    }
                }

                function startElapsedTimeInterval() {
                    if (timerStartTime) {
                        console.log('Starting elapsed time interval for start time:', timerStartTime);
                        updateElapsedTime();
                        timerInterval = setInterval(updateElapsedTime, 1000);
                    }
                }

                function stopElapsedTimeInterval() {
                    clearInterval(timerInterval);
                    console.log('Elapsed time interval stopped.');
                }

                function renderTimeLogEntry(entry) {
                    console.log('Rendering new time log entry:', entry);
                    const localeForJs = {{ Js::from(str_replace('_', '-', app()->getLocale())) }};
                    const startTimeFormatted = new Date(entry.start_time).toLocaleString(localeForJs, { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
                    const endTimeFormatted = entry.end_time ? new Date(entry.end_time).toLocaleString(localeForJs, { hour: '2-digit', minute: '2-digit' }) : {{ Js::from(__('In progress')) }};
                    const durationFormatted = entry.duration_minutes ? (entry.duration_minutes / 60).toFixed(2) + 'h' : '';
                    const entryDescription = entry.description ? $('<div/>').text(entry.description).html() : '';

                    let actionsHtml = '';
                    const currentUserId = {{ Js::from(Auth::id()) }};
                    const userCanUpdateTasks = {{ Js::from(Auth::check() && Auth::user()->can('tasks update')) }};

                    if (currentUserId && (currentUserId == entry.user_id || userCanUpdateTasks)) {
                        actionsHtml = `
                            <div class="mt-1 text-xs">
                                <a href="/task-time-entries/${entry.id}/edit" class="text-blue-500 hover:underline mr-2">${{ Js::from(__('Edit')) }}</a>
                                <button type="button" class="deleteTimeEntryBtn text-red-500 hover:underline" data-entry-id="${entry.id}">${{ Js::from(__('Delete')) }}</button>
                            </div>`;
                    }

                    const entryHtml = `
                        <li class="time-log-entry border-b border-slate-100 dark:border-slate-700 pb-3 last:border-b-0" data-entry-id="${entry.id}">
                            <div class="text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-medium">${entry.user ? entry.user.name : {{ Js::from(__('Unknown User')) }}}</span>
                                - ${startTimeFormatted}
                                ${entry.end_time ? '- ' + endTimeFormatted : ''}
                                ${entry.duration_minutes ? '(<span class="text-indigo-600 dark:text-indigo-400">' + durationFormatted + '</span>)' : ''}
                            </div>
                            ${entryDescription ? '<p class="text-xs text-slate-500 dark:text-slate-400 mt-1 whitespace-pre-wrap">' + entryDescription + '</p>' : ''}
                            ${actionsHtml}
                        </li>`;
                    $('#noTimeEntries').hide();
                    $('#timeLogList').prepend(entryHtml);
                }


                if (activeTimeLogId) {
                    startElapsedTimeInterval();
                }

                $(document).on('click', '#startTimerBtn', function() {
                    console.log('Start Timer button clicked');
                    const description = $('#time_entry_description').val();
                    $('#timeTrackerMessage').text('').removeClass('text-red-500 text-green-500');
                    $.ajax({
                        url: `/tasks/${taskId}/time-history/start`,
                        method: 'POST',
                        data: { _token: csrfToken, description: description },
                        success: function(response) {
                            console.log('Start timer success response:', response);
                            if (response.success) {
                                $('#time_entry_description').val('');
                                $('#timeTrackerControls').html(`
                                    <p class="mb-2 text-sm text-slate-600 dark:text-slate-300">
                                        {{ Js::from(__('Timer started at:')) }} <span id="timerStartTimeFormatted">${response.start_time_formatted}</span>
                                    </p>
                                    <p class="mb-4 text-lg font-semibold text-blue-600 dark:text-blue-400">
                                        {{ Js::from(__('Time Elapsed:')) }} <span id="elapsedTimeDisplay">00:00:00</span>
                                    </p>
                                    <button type="button" id="stopTimerBtn" data-task-id="${taskId}" class="btn btn-danger inline-flex items-center">
                                        <iconify-icon icon="heroicons:stop-circle-solid" class="text-lg mr-1"></iconify-icon>
                                        {{ Js::from(__('Stop Timer')) }}
                                    </button>
                                `);
                                activeTimeLogId = response.time_entry_id;
                                timerStartTime = response.start_time_iso;
                                startElapsedTimeInterval();
                                $('#timeTrackerMessage').text(response.success).addClass('text-green-500');
                            } else {
                                $('#timeTrackerMessage').text(response.error || {{ Js::from(__('An error occurred.')) }}).addClass('text-red-500');
                            }
                        },
                        error: function(xhr) {
                            console.error('Start timer AJAX error:', xhr);
                            const errorMsg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : {{ Js::from(__('An error occurred.')) }};
                            $('#timeTrackerMessage').text(errorMsg).addClass('text-red-500');
                        }
                    });
                });

                $(document).on('click', '#stopTimerBtn', function() {
                    console.log('Stop Timer button clicked');
                    const description = $('#time_entry_description').val();
                    $('#timeTrackerMessage').text('').removeClass('text-red-500 text-green-500');
                    $.ajax({
                        url: `/tasks/${taskId}/time-history/stop`,
                        method: 'POST',
                        data: { _token: csrfToken, description: description },
                        success: function(response) {
                            console.log('Stop timer success response:', response);
                            if (response.success) {
                                $('#time_entry_description').val('');
                                $('#timeTrackerControls').html(`
                                    <button type="button" id="startTimerBtn" data-task-id="${taskId}" class="btn btn-success inline-flex items-center">
                                        <iconify-icon icon="heroicons:play-circle-solid" class="text-lg mr-1"></iconify-icon>
                                        ${{ Js::from(__('Start Timer')) }}
                                    </button>
                                `);
                                stopElapsedTimeInterval();
                                activeTimeLogId = null;
                                timerStartTime = null;
                                $('#timeTrackerMessage').text(response.success).addClass('text-green-500');
                                $('#taskLoggedHours').text(parseFloat(response.logged_hours_task || 0).toFixed(2) + 'h');
                                if (response.time_entry) {
                                    renderTimeLogEntry(response.time_entry);
                                }
                            } else {
                                $('#timeTrackerMessage').text(response.error || {{ Js::from(__('An error occurred.')) }}).addClass('text-red-500');
                            }
                        },
                        error: function(xhr) {
                             console.error('Stop timer AJAX error:', xhr);
                             const errorMsg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : {{ Js::from(__('An error occurred.')) }};
                            $('#timeTrackerMessage').text(errorMsg).addClass('text-red-500');
                        }
                    });
                });

                $(document).on('click', '.deleteTimeEntryBtn', function() {
                    const entryId = $(this).data('entry-id');
                    console.log('Delete time entry button clicked for entry ID:', entryId);
                    Swal.fire({
                        title: {{ Js::from(__('Are you sure?')) }},
                        text: {{ Js::from(__('This will delete the time entry permanently.')) }},
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: {{ Js::from(__('Delete')) }},
                        cancelButtonText: {{ Js::from(__('Cancel')) }},
                        confirmButtonColor: '#e11d48',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log('Confirmed deletion for time entry ID:', entryId);
                            $.ajax({
                                url: `/task-time-entries/${entryId}`,
                                method: 'DELETE',
                                data: { _token: csrfToken },
                                success: function(response) {
                                    console.log('Delete time entry success response:', response);
                                    if (response.success) {
                                        Swal.fire({{ Js::from(__('Deleted!')) }}, response.success, 'success');
                                        $(`.time-log-entry[data-entry-id="${entryId}"]`).remove();
                                        if ($('#timeLogList .time-log-entry').length === 0) {
                                            $('#noTimeEntries').show();
                                        }
                                        if(response.logged_hours_task !== undefined) {
                                            $('#taskLoggedHours').text(parseFloat(response.logged_hours_task || 0).toFixed(2) + 'h');
                                        }
                                    } else {
                                        Swal.fire({{ Js::from(__('Error')) }}, response.error || {{ Js::from(__('An error occurred.')) }}, 'error');
                                    }
                                },
                                error: function(xhr) {
                                    console.error('Delete time entry AJAX error:', xhr);
                                    const errorMsg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : {{ Js::from(__('An error occurred.')) }};
                                    Swal.fire({{ Js::from(__('Error')) }}, errorMsg, 'error');
                                }
                            });
                        }
                    });
                });

            }); // Fin document ready jQuery
        </script>
    @endpush
</x-app-layout>

