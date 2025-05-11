<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
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

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg mb-6">
        <div class="card-body p-6">

            {{-- Botones de Acción del Proyecto --}}
            <div class="flex flex-wrap justify-end items-center space-x-3 mb-6">
                @can('projects update')
                    @if (!in_array($project->status, ['completed', 'cancelled']))
                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-secondary btn-sm inline-flex items-center">
                            <iconify-icon icon="heroicons:pencil-square" class="text-lg mr-1"></iconify-icon>
                            {{ __('Edit Project') }}
                        </a>
                    @endif
                @endcan
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
                            case 'in_progress': $color = 'text-blue-500 dark:text-blue-400'; break;
                            case 'completed': $color = 'text-green-500 dark:text-green-400'; break;
                            case 'on_hold': $color = 'text-yellow-500 dark:text-yellow-400'; break;
                            case 'cancelled': $color = 'text-red-500 dark:text-red-400'; break;
                            case 'pending': $color = 'text-orange-500 dark:text-orange-400'; break;
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
        </div>
    </div>

    {{-- Sección de Tareas --}}
    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg mt-6">
        <div class="card-header flex justify-between items-center p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Tasks') }}</h3>
            @can('tasks create') {{-- Solo usuarios con permiso pueden crear tareas --}}
                 {{-- El cliente no debería poder crear tareas desde aquí --}}
                @if(!Auth::user()->hasRole('customer'))
                    <a href="{{ route('projects.tasks.create', $project->id) }}" class="btn btn-primary btn-sm inline-flex items-center">
                        <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                        {{ __('Add New Task') }}
                    </a>
                @endif
            @endcan
        </div>
        <div class="card-body p-6">
            <div class="overflow-x-auto">
                <table id="projectTasksTable" class="w-full border-collapse dataTable">
                    <thead class="bg-slate-100 dark:bg-slate-700">
                        <tr>
                            <th class="table-th">{{ __('Title') }}</th>
                            <th class="table-th">{{ __('Assigned To') }}</th>
                            <th class="table-th">{{ __('Priority') }}</th>
                            <th class="table-th">{{ __('Status') }}</th>
                            <th class="table-th">{{ __('Due Date') }}</th>
                            <th class="table-th text-center">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        {{-- Los datos se cargarán vía AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>


     {{-- Notas Internas (si aplica y hay permiso) --}}
    @if($project->internal_notes && Auth::user()->can('projects show'))
        <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg mt-6">
            <div class="card-body p-6">
                <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Internal Notes') }}</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-wrap bg-slate-50 dark:bg-slate-700 p-3 rounded-md">{{ $project->internal_notes }}</p>
            </div>
        </div>
    @endif


    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            table.dataTable th, table.dataTable td { white-space: nowrap; }
            .table-th { padding-left: 1rem; padding-right: 1rem; padding-top: 0.75rem; padding-bottom: 0.75rem; text-align: left; font-size: 0.75rem; line-height: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
            .dark .table-th { color: #94a3b8; }
            .table-td { padding-left: 1rem; padding-right: 1rem; padding-top: 0.75rem; padding-bottom: 0.75rem; font-size: 0.875rem; line-height: 1.25rem; vertical-align: middle;}
            table.dataTable#projectTasksTable { border-spacing: 0; }
            table.dataTable#projectTasksTable th, table.dataTable#projectTasksTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#projectTasksTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#projectTasksTable tbody tr:hover { background-color: #1f2937; }
            /* ... (otros estilos de DataTables que ya tenías) ... */
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
        <script>
            $(function() {
                if (typeof $.fn.DataTable === 'undefined') {
                    console.error('DataTables plugin is not loaded.');
                    $('#projectTasksTable tbody').html('<tr><td colspan="6" class="text-center py-10 text-red-500">{{ __("DataTable library not loaded.") }}</td></tr>');
                    return;
                }

                const projectTasksDataTable = $('#projectTasksTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("projects.tasks.data", $project->id) }}',
                        type: 'GET',
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error details for tasks:", jqXHR);
                            let errorMsg = "{{ __('Error loading tasks. Please try again.') }}";
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>";
                            }
                             $('#projectTasksTable tbody').html(`<tr><td colspan="6" class="text-center py-10 text-red-500">${errorMsg}</td></tr>`);
                        }
                    },
                    columns: [
                        { data: 'title', name: 'title', className: 'table-td' },
                        { data: 'assigned_users_list', name: 'users.name', className: 'table-td', orderable: false, searchable: false },
                        { data: 'priority', name: 'priority', className: 'table-td' },
                        { data: 'status', name: 'status', className: 'table-td' },
                        { data: 'due_date', name: 'due_date', className: 'table-td' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'table-td text-center' }
                    ],
                    order: [[4, "asc"]], // Ordenar por fecha de vencimiento ascendente
                    dom: "<'flex flex-col md:flex-row md:justify-between gap-4 mb-4'<'md:w-1/2'l><'md:w-1/2'f>>" + "<'overflow-x-auto't>" + "<'flex flex-col md:flex-row md:justify-between gap-4 mt-4'<'md:w-1/2'i><'md:w-1/2'p>>",
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                        search: "_INPUT_",
                        searchPlaceholder: "{{ __('Search tasks...') }}",
                        lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                    },
                    initComplete: function(settings, json) {
                        const $wrapper = $('#projectTasksTable_wrapper');
                        $wrapper.find('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                        $wrapper.find('.dataTables_length select').addClass('inputField px-3 py-2 pr-8 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition appearance-none');
                    }
                });

                // Handler para eliminar una tarea
                $('#projectTasksTable').on('click', '.deleteTask', function () {
                    const taskId = $(this).data('id');
                    // const projectId = $(this).data('project-id'); // No es necesario si la ruta de delete es shallow
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the task permanently.") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/tasks/${taskId}`, { // Ruta shallow
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('{{ __("Deleted!") }}', data.success, 'success');
                                    projectTasksDataTable.ajax.reload(null, false);
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Delete task error:', error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the task.") }}', 'error');
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
