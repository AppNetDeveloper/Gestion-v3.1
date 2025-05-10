<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Projects Management')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    {{-- Alert end --}}

    <div class="space-y-8">
        <div class="md:flex justify-between items-center">
            <div class="flex-1"></div> {{-- Espaciador --}}

            <div class="flex flex-wrap items-center">
              {{-- Tabs para List/Grid View --}}
              <ul class="nav nav-pills flex items-center flex-wrap list-none pl-0 md:mr-4 mb-2 md:mb-0" id="pills-tabVertical" role="tablist">
                <li class="nav-item flex-grow text-center" role="presentation">
                  <button class="btn inline-flex justify-center btn-white dark:bg-slate-700 dark:text-slate-300 m-1 active" id="pills-list-tab" data-bs-toggle="pill" data-bs-target="#pills-list" role="tab" aria-controls="pills-list" aria-selected="true">
                    <span class="flex items-center">
                      <iconify-icon class="text-xl ltr:mr-2 rtl:ml-2" icon="heroicons-outline:clipboard-list"></iconify-icon>
                      <span>{{ __('List View') }}</span>
                    </span>
                  </button>
                </li>
                <li class="nav-item flex-grow text-center" role="presentation">
                  <button class="btn inline-flex justify-center btn-white dark:bg-slate-700 dark:text-slate-300 m-1" id="pills-grid-tab" data-bs-toggle="pill" data-bs-target="#pills-grid" role="tab" aria-controls="pills-grid" aria-selected="false">
                    <span class="flex items-center">
                      <iconify-icon class="text-xl ltr:mr-2 rtl:ml-2" icon="heroicons-outline:view-grid"></iconify-icon>
                      <span>{{ __('Grid View') }}</span>
                    </span>
                  </button>
                </li>
              </ul>
              @can('projects create')
                <a href="{{ route('projects.create') }}" class="btn inline-flex justify-center btn-dark dark:bg-slate-700 dark:text-slate-300 m-1">
                    <span class="flex items-center">
                    <iconify-icon class="text-xl ltr:mr-2 rtl:ml-2" icon="ph:plus-bold"></iconify-icon>
                    <span>{{ __('Add Project') }}</span>
                    </span>
                </a>
              @endcan
            </div>
        </div>

        <div class="tab-content mt-6" id="pills-tabContent">
            {{-- LIST VIEW --}}
            <div class="tab-pane fade show active" id="pills-list" role="tabpanel" aria-labelledby="pills-list-tab">
                {{-- Contenedor de la tarjeta para la tabla, similar a services.index --}}
                <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
                    <div class="card-body p-6">
                        <div class="overflow-x-auto"> {{-- Eliminado -mx-6 si el card-body ya tiene padding --}}
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden">
                                    <table id="projectsTable" class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700 dataTable">
                                        <thead class="bg-slate-100 dark:bg-slate-700">
                                            <tr>
                                                <th scope="col" class="table-th ">{{ __('NAME') }}</th>
                                                <th scope="col" class="table-th ">{{ __('Client') }}</th>
                                                <th scope="col" class="table-th ">{{ __('Quote #') }}</th>
                                                <th scope="col" class="table-th ">{{ __('START DATE') }}</th>
                                                <th scope="col" class="table-th ">{{ __('END DATE') }}</th>
                                                <th scope="col" class="table-th ">{{ __('STATUS') }}</th>
                                                <th scope="col" class="table-th ">{{ __('ACTION') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                            {{-- DataTables cargará los datos aquí --}}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- GRID VIEW --}}
            <div class="tab-pane fade" id="pills-grid" role="tabpanel" aria-labelledby="pills-grid-tab">
                @if(isset($projects) && $projects->count() > 0)
                    <div class="grid xl:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-5 ">
                        @foreach ($projects as $project)
                            <div class="card rounded-md bg-white dark:bg-slate-800 shadow-base custom-class card-body p-6">
                                <header class="flex justify-between items-end">
                                    <div class="flex space-x-4 items-center rtl:space-x-reverse">
                                        <div class="flex-none">
                                            <div class="h-10 w-10 rounded-md text-lg bg-slate-100 text-slate-900 dark:bg-slate-600 dark:text-slate-200 flex flex-col items-center justify-center font-medium capitalize">
                                                {{ Str::substr($project->project_title, 0, 2) }}
                                            </div>
                                        </div>
                                        <div class="font-medium text-base leading-6">
                                            <a href="{{ route('projects.show', $project->id) }}" class="dark:text-slate-200 text-slate-900 max-w-[160px] truncate hover:underline" title="{{ $project->project_title }}">
                                                {{ Str::limit($project->project_title, 20) }}
                                            </a>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="dropstart relative">
                                            <button class="inline-flex justify-center items-center text-slate-600 dark:text-slate-300" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <iconify-icon class="text-xl" icon="heroicons-outline:dots-vertical"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu min-w-max absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700 shadow z-[2] float-left overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                                @php
                                                    $user = Auth::user();
                                                    $isCustomer = $user->hasRole('customer');
                                                    $isOwner = $isCustomer && $project->client && $project->client->user_id == $user->id;
                                                @endphp
                                                @if ($user->can('projects show') || ($isOwner && $user->can('projects view_own')))
                                                <li>
                                                    <a href="{{ route('projects.show', $project->id) }}" class="hover:bg-slate-900 dark:hover:bg-slate-600 dark:hover:bg-opacity-70 hover:text-white w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm dark:text-slate-300 last:mb-0 cursor-pointer first:rounded-t last:rounded-b flex space-x-2 items-center capitalize rtl:space-x-reverse">
                                                        <iconify-icon icon="heroicons-outline:eye"></iconify-icon> <span>{{ __('View') }}</span>
                                                    </a>
                                                </li>
                                                @endif
                                                @if ($user->can('projects update') && !$isCustomer && !in_array($project->status, ['completed', 'cancelled']))
                                                <li>
                                                    <a href="{{ route('projects.edit', $project->id) }}" class="hover:bg-slate-900 dark:hover:bg-slate-600 dark:hover:bg-opacity-70 hover:text-white w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm dark:text-slate-300 last:mb-0 cursor-pointer first:rounded-t last:rounded-b flex space-x-2 items-center capitalize rtl:space-x-reverse">
                                                        <iconify-icon icon="clarity:note-edit-line"></iconify-icon> <span>{{ __('Edit') }}</span>
                                                    </a>
                                                </li>
                                                @endif
                                                @if ($user->can('projects delete') && !$isCustomer)
                                                <li>
                                                    <button type="button" class="deleteProjectCardBtn hover:bg-slate-900 dark:hover:bg-slate-600 dark:hover:bg-opacity-70 hover:text-white w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm dark:text-slate-300 last:mb-0 cursor-pointer first:rounded-t last:rounded-b flex space-x-2 items-center capitalize rtl:space-x-reverse" data-id="{{ $project->id }}">
                                                        <iconify-icon icon="fluent:delete-28-regular"></iconify-icon> <span>{{ __('Delete') }}</span>
                                                    </button>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </header>
                                <div class="text-slate-600 dark:text-slate-400 text-sm pt-4 pb-8">
                                    {{ Str::limit($project->description, 120) }}
                                </div>
                                <div class="flex space-x-4 rtl:space-x-reverse mb-3">
                                    <div>
                                        <span class="block date-label text-xs text-slate-400">{{ __('START DATE') }}</span>
                                        <span class="block date-text text-sm font-medium text-slate-600 dark:text-slate-300">{{ $project->start_date ? $project->start_date->format('d M, Y') : '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="block date-label text-xs text-slate-400">{{ __('END DATE') }}</span>
                                        <span class="block date-text text-sm font-medium text-slate-600 dark:text-slate-300">{{ $project->due_date ? $project->due_date->format('d M, Y') : '-' }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-6">
                                    <div>
                                        <div class="text-slate-400 dark:text-slate-400 text-sm font-normal mb-1">
                                            {{ __('Client') }}
                                        </div>
                                        <div class="text-sm font-medium text-slate-600 dark:text-slate-300 truncate" title="{{ $project->client->name ?? '' }}">
                                            {{ $project->client->name ?? __('N/A')}}
                                        </div>
                                    </div>
                                    <div class="ltr:text-right rtl:text-left">
                                        @php
                                            $statusText = ucfirst($project->status);
                                            $statusColorClass = 'bg-slate-400 text-slate-400'; // Default
                                            switch ($project->status) {
                                                case 'in_progress': $statusColorClass = 'bg-blue-500 text-blue-500'; break;
                                                case 'completed': $statusColorClass = 'bg-green-500 text-green-500'; break;
                                                case 'pending': $statusColorClass = 'bg-orange-500 text-orange-500'; break;
                                                case 'on_hold': $statusColorClass = 'bg-yellow-500 text-yellow-500'; break;
                                                case 'cancelled': $statusColorClass = 'bg-red-500 text-red-500'; break;
                                            }
                                        @endphp
                                        <span class="inline-flex items-center space-x-1 {{ $statusColorClass }} bg-opacity-[0.16] text-xs font-normal px-2 py-1 rounded-full rtl:space-x-reverse">
                                            {{ __($statusText) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(isset($projects) && $projects->hasPages())
                        <div class="py-6">
                            {{ $projects->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-10">
                        <p class="text-slate-500 dark:text-slate-400">{{ __('No projects found.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            table.dataTable th, table.dataTable td { white-space: nowrap; }
            .table-th { padding-left: 1.5rem; padding-right: 1.5rem; padding-top: 0.75rem; padding-bottom: 0.75rem; text-align: left; font-size: 0.75rem; line-height: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
            .dark .table-th { color: #94a3b8; }
            .table-td { padding-left: 1.5rem; padding-right: 1.5rem; padding-top: 1rem; padding-bottom: 1rem; font-size: 0.875rem; line-height: 1.25rem; }
            /* Estilos de DataTables (copiados de quotes.index) */
            table.dataTable#projectsTable { border-spacing: 0; }
            table.dataTable#projectsTable th, table.dataTable#projectsTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#projectsTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#projectsTable tbody tr:hover { background-color: #1f2937; }
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
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding-top: 1rem; padding-bottom: 1rem; /* Añadido padding-bottom */ }
            .dataTables_wrapper .dataTables_filter input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_filter input { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
            .dataTables_wrapper .dataTables_length select { padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_length select { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
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
                    $('#projectsTable tbody').html('<tr><td colspan="7" class="text-center py-10 text-red-500">{{ __("DataTable library not loaded.") }}</td></tr>');
                    return;
                }

                const projectsDataTable = $('#projectsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("projects.data") }}',
                        type: 'GET',
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error details:", jqXHR);
                            let errorMsg = "{{ __('Error loading data. Please try again.') }}";
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>";
                            }
                             $('#projectsTable tbody').html(`<tr><td colspan="7" class="text-center py-10 text-red-500">${errorMsg}</td></tr>`);
                        }
                    },
                    columns: [
                        { data: 'project_title', name: 'project_title', className: 'table-td' },
                        { data: 'client_name', name: 'client.name', className: 'table-td' },
                        { data: 'quote_number', name: 'quote.quote_number', className: 'table-td' },
                        { data: 'start_date', name: 'start_date', className: 'table-td' },
                        { data: 'due_date', name: 'due_date', className: 'table-td' },
                        { data: 'status', name: 'status', className: 'table-td' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'table-td text-center' }
                    ],
                    order: [[0, "asc"]],
                    // Ajuste del DOM para DataTables para que coincida con el estilo de la imagen
                    dom:  "<'md:flex justify-between items-center'<'flex-1'l><'flex-1'f>>" +
                          "<'overflow-x-auto'tr>" +
                          "<'md:flex justify-between items-center'<'flex-1'i><'flex-1'p>>",
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                        search: "_INPUT_",
                        searchPlaceholder: "{{ __('Search projects...') }}",
                        lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                    },
                    initComplete: function(settings, json) {
                        // Aplicar clases a los inputs de DataTables
                        const $wrapper = $('#projectsTable_wrapper');
                        $wrapper.find('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                        $wrapper.find('.dataTables_length select').addClass('inputField px-3 py-2 pr-8 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition appearance-none');
                    }
                });

                // Handler para eliminar un proyecto (tanto de tabla como de tarjeta)
                $(document).on('click', '.deleteProject, .deleteProjectCardBtn', function () {
                    const projectId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the project permanently.") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        customClass: { popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/projects/${projectId}`, {
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
                                    projectsDataTable.ajax.reload(null, false);
                                    if ($('#pills-grid-tab').hasClass('active')) {
                                        // Para la Grid View, una recarga simple es más fácil por ahora
                                        // Idealmente, se haría una recarga AJAX de la sección de tarjetas
                                        window.location.reload();
                                    }
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Delete error:', error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the project.") }}', 'error');
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>


