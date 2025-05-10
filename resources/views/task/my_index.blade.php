<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- $breadcrumbItems se pasa desde TaskController@myTasks --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('My Tasks')" />
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
            {{-- No hay botón de "Crear Nueva Tarea" aquí, ya que las tareas se crean dentro de un proyecto --}}
            {{-- Si quisieras un acceso rápido, podrías poner un enlace a la lista de proyectos --}}
            {{-- <div class="mb-6 text-right">
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('View Projects to Add Tasks') }}
                </a>
            </div> --}}

            <div class="overflow-x-auto">
                <table id="myTasksTable" class="w-full border-collapse dataTable">
                    <thead class="bg-slate-100 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Task Title') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Project') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Client') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Priority') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Due Date') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        {{-- Los datos se cargarán vía AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            /* Estilos generales y de DataTables (puedes copiarlos de quotes.index o services.index) */
            table.dataTable th, table.dataTable td { white-space: nowrap; }
            .table-th { padding-left: 1.5rem; padding-right: 1.5rem; padding-top: 0.75rem; padding-bottom: 0.75rem; text-align: left; font-size: 0.75rem; line-height: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
            .dark .table-th { color: #94a3b8; }
            .table-td { padding-left: 1.5rem; padding-right: 1.5rem; padding-top: 1rem; padding-bottom: 1rem; font-size: 0.875rem; line-height: 1.25rem; }
             /* Estilos de DataTables (copiados de quotes.index) */
            table.dataTable#myTasksTable { border-spacing: 0; }
            table.dataTable#myTasksTable th, table.dataTable#myTasksTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#myTasksTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#myTasksTable tbody tr:hover { background-color: #1f2937; }
            table.dataTable thead th.sorting:after, table.dataTable thead th.sorting_asc:after, table.dataTable thead th.sorting_desc:after { display: inline-block; margin-left: 5px; opacity: 0.5; color: inherit; }
            table.dataTable thead th.sorting:after { content: "\\2195"; }
            table.dataTable thead th.sorting_asc:after { content: "\\2191"; }
            table.dataTable thead th.sorting_desc:after { content: "\\2193"; }
            .dataTables_wrapper .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center !important; justify-content: center !important; padding: 0.5rem 1rem !important; margin: 0 0.125rem !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #f9fafb !important; color: #374151 !important; cursor: pointer !important; transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, color 0.15s ease-in-out; }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background-color: #f3f4f6 !important; border-color: #9ca3af !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button { background-color: #374151 !important; color: #d1d5db !important; border-color: #4b5563 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background-color: #4b5563 !important; border-color: #6b7280 !important; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding-top: 1rem; padding-bottom: 1rem; }
            .dataTables_wrapper .dataTables_filter input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_filter input { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
            .dataTables_wrapper .dataTables_length select { padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_length select { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
        </style>
    @endpush

    @push('scripts')
        {{-- Cargar jQuery PRIMERO si no está globalmente --}}
        {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            $(function() {
                if (typeof $.fn.DataTable === 'undefined') {
                    console.error('DataTables plugin is not loaded.');
                    $('#myTasksTable tbody').html('<tr><td colspan="7" class="text-center py-10 text-red-500">{{ __("DataTable library not loaded.") }}</td></tr>');
                    return;
                }

                const myTasksDataTable = $('#myTasksTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("tasks.my.data") }}', // Ruta para obtener datos de "Mis Tareas"
                        type: 'GET',
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error details for my tasks:", jqXHR);
                            let errorMsg = "{{ __('Error loading your tasks. Please try again.') }}";
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>";
                            }
                             $('#myTasksTable tbody').html(`<tr><td colspan="7" class="text-center py-10 text-red-500">${errorMsg}</td></tr>`);
                        }
                    },
                    columns: [
                        { data: 'title', name: 'title', className: 'table-td' },
                        { data: 'project_title', name: 'project.project_title', className: 'table-td' }, // Asume que 'project_title' se añade en el controlador
                        { data: 'client_name', name: 'project.client.name', className: 'table-td' }, // Asume que 'client_name' se añade en el controlador
                        { data: 'priority', name: 'priority', className: 'table-td' },
                        { data: 'status', name: 'status', className: 'table-td' },
                        { data: 'due_date', name: 'due_date', className: 'table-td' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'table-td text-center' }
                    ],
                    order: [[5, "asc"]], // Ordenar por fecha de vencimiento ascendente
                    dom: "<'flex flex-col md:flex-row md:justify-between gap-4 mb-4'<'md:w-1/2'l><'md:w-1/2'f>>" + "<'overflow-x-auto't>" + "<'flex flex-col md:flex-row md:justify-between gap-4 mt-4'<'md:w-1/2'i><'md:w-1/2'p>>",
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                        search: "_INPUT_",
                        searchPlaceholder: "{{ __('Search my tasks...') }}",
                        lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                    },
                    initComplete: function(settings, json) {
                        const $wrapper = $('#myTasksTable_wrapper');
                        $wrapper.find('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                        $wrapper.find('.dataTables_length select').addClass('inputField px-3 py-2 pr-8 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition appearance-none');
                    }
                });

                // No se necesita el handler de 'deleteTask' aquí, ya que las tareas
                // se eliminan desde la vista del proyecto o desde su propia vista de detalle/edición.
                // Si quieres añadirlo, asegúrate de que la ruta y el data-project-id sean correctos.
            });
        </script>
    @endpush
</x-app-layout>
