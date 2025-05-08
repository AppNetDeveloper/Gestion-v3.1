<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- Asegúrate de pasar $breadcrumbItems desde QuoteController@index --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Quotes Management')" />
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

            {{-- Botón para ir a la página de creación --}}
            <div class="mb-6 text-right"> {{-- Alineado a la derecha --}}
                <a href="{{ route('quotes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <iconify-icon icon="heroicons:plus-solid" class="text-lg ltr:mr-2 rtl:ml-2"></iconify-icon>
                    {{ __('Create New Quote') }}
                </a>
            </div>

            {{-- Tabla de presupuestos --}}
            <div class="overflow-x-auto">
                <table id="quotesTable" class="w-full border-collapse dataTable"> {{-- Cambiado ID --}}
                    <thead class="bg-slate-100 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Number') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Client') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Total') }}</th>
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

    {{-- Estilos adicionales --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            .inputField:focus { /* Tailwind's focus classes handle this */ }
            table.dataTable#quotesTable { border-spacing: 0; } /* Cambiado ID */
            table.dataTable#quotesTable th, table.dataTable#quotesTable td { padding: 0.75rem 1rem; vertical-align: middle; } /* Cambiado ID */
            table.dataTable#quotesTable tbody tr:hover { background-color: #f9fafb; } /* Cambiado ID */
            .dark table.dataTable#quotesTable tbody tr:hover { background-color: #1f2937; } /* Cambiado ID */
            table.dataTable thead th.sorting:after, table.dataTable thead th.sorting_asc:after, table.dataTable thead th.sorting_desc:after { display: inline-block; margin-left: 5px; opacity: 0.5; color: inherit; }
            table.dataTable thead th.sorting:after { content: "\\2195"; }
            table.dataTable thead th.sorting_asc:after { content: "\\2191"; }
            table.dataTable thead th.sorting_desc:after { content: "\\2193"; }
            .swal2-popup { width: 90% !important; max-width: 600px !important; border-radius: 0.5rem !important; }
            .dark .swal2-popup { background: #1f2937 !important; color: #d1d5db !important; }
            .dark .swal2-title { color: #f3f4f6 !important; }
            .dark .swal2-html-container { color: #d1d5db !important; }
            .dark .swal2-actions button.swal2-confirm { background-color: #4f46e5 !important; }
            .dark .swal2-actions button.swal2-cancel { background-color: #4b5563 !important; }
            .custom-swal-input, .custom-swal-textarea { width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; padding: 0.75rem !important; display: block !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #fff !important; color: #111827 !important; }
            .custom-swal-input:focus, .custom-swal-textarea:focus { outline: none !important; border-color: #4f46e5 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3) !important; }
            .dark .custom-swal-input, .dark .custom-swal-textarea { background-color: #374151 !important; border-color: #4b5563 !important; color: #f3f4f6 !important; }
            .dark .custom-swal-input:focus, .dark .custom-swal-textarea:focus { border-color: #6366f1 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.4) !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center !important; justify-content: center !important; padding: 0.5rem 1rem !important; margin: 0 0.125rem !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #f9fafb !important; color: #374151 !important; cursor: pointer !important; transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, color 0.15s ease-in-out; }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background-color: #f3f4f6 !important; border-color: #9ca3af !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button { background-color: #374151 !important; color: #d1d5db !important; border-color: #4b5563 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background-color: #4b5563 !important; border-color: #6b7280 !important; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding-top: 1rem; }
            .dataTables_wrapper .dataTables_filter input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_filter input { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
            .dataTables_wrapper .dataTables_length select { padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_length select { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
        </style>
    @endpush

    @push('scripts')
        {{-- *** NO incluir jQuery si ya está cargado globalmente por la app *** --}}
        {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}

        {{-- Cargar DataTables DESPUÉS de que jQuery esté disponible --}}
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        {{-- Otros scripts --}}
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            // Esperar a que el DOM esté completamente cargado
            $(function() { // Atajo de jQuery para document ready
                if (typeof $ === 'undefined') {
                    console.error('jQuery is not loaded. Cannot initialize DataTables or attach jQuery event handlers.');
                    return;
                }
                if (typeof $.fn.DataTable === 'undefined') {
                     console.error('DataTables plugin is not loaded.');
                     // Podrías mostrar un mensaje al usuario
                     $('#quotesTable tbody').html(
                        `<tr><td colspan="6" class="text-center py-10 text-red-500">{{ __("DataTable library not loaded.") }}</td></tr>`
                     );
                     return;
                }

                const quotesDataTable = $('#quotesTable').DataTable({ // Cambiado ID
                    processing: true, // Añadir indicador de procesamiento
                    serverSide: true, // Habilitar procesamiento del lado del servidor si esperas muchos datos
                    ajax: {
                        url: '{{ route("quotes.data") }}', // Cambiada ruta
                        type: 'GET', // O POST si prefieres
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error details:", jqXHR);
                            let errorMsg = "{{ __('Error loading data. Please try again.') }}";
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>";
                            } else if (jqXHR.responseText) {
                                console.error("Server Response Text:", jqXHR.responseText);
                            }
                             $('#quotesTable tbody').html( // Cambiado ID
                                `<tr><td colspan="6" class="text-center py-10 text-red-500">${errorMsg}</td></tr>` // Ajustado colspan
                            );
                        }
                    },
                    columns: [ // Cambiadas columnas
                        { data: 'quote_number', name: 'quote_number', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        { data: 'client_name', name: 'client.name', className: 'text-sm text-slate-700 dark:text-slate-300' }, // Usar client_name definido en el controlador
                        { data: 'quote_date', name: 'quote_date', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        { data: 'status', name: 'status', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        { data: 'total_amount', name: 'total_amount', className: 'text-sm text-slate-700 dark:text-slate-300 text-right' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-sm text-center' }
                    ],
                    order: [[0, "desc"]], // Ordenar por número de presupuesto descendente
                    // Configuración adicional de DataTables (igual que antes)
                    dom: "<'flex flex-col md:flex-row md:justify-between gap-4 mb-4'<'md:w-1/2'l><'md:w-1/2'f>>" +
                         "<'overflow-x-auto't>" +
                         "<'flex flex-col md:flex-row md:justify-between gap-4 mt-4'<'md:w-1/2'i><'md:w-1/2'p>>",
                    responsive: true,
                    autoWidth: false,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                        search: "_INPUT_",
                        searchPlaceholder: "{{ __('Search quotes...') }}", // Cambiado placeholder
                        lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                    },
                    initComplete: function(settings, json) {
                        $('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                        $('.dataTables_length select').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                    }
                });

                // Handler para eliminar un presupuesto
                $('#quotesTable').on('click', '.deleteQuote', function () { // Cambiado selector
                    const quoteId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the quote permanently.") }}', // Cambiado texto
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        customClass: { popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/quotes/${quoteId}`, { // Cambiada ruta
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
                                    quotesDataTable.ajax.reload(null, false); // Cambiado nombre variable
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Delete error:', error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the quote.") }}', 'error'); // Cambiado texto
                            });
                        }
                    });
                });

                // Nota: El handler para editar (.editQuote) ahora es un enlace <a> que redirige
                // a la página de edición, por lo que no necesita un manejador de clic aquí
                // a menos que quieras abrir la edición en un modal (lo cual sería más complejo).

            }); // Fin de $(function() { ... });
        </script>
    @endpush
</x-app-layout>
