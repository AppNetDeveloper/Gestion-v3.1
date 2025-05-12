<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- $breadcrumbItems se pasará desde InvoiceController@index --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Invoices Management')" />
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

            {{-- Botón para ir a la página de creación de factura manual (si se permite) --}}
            @can('invoices create')
            <div class="mb-6 text-right">
                <a href="{{ route('invoices.create') }}" class="btn btn-primary inline-flex items-center">
                    <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                    {{ __('Create New Invoice') }}
                </a>
            </div>
            @endcan

            {{-- Tabla de facturas --}}
            <div class="overflow-x-auto">
                <table id="invoicesTable" class="w-full border-collapse dataTable">
                    <thead class="bg-slate-100 dark:bg-slate-700">
                        <tr>
                            <th class="table-th ">{{ __('Invoice #') }}</th>
                            <th class="table-th ">{{ __('Client') }}</th>
                            <th class="table-th ">{{ __('Quote #') }}</th>
                            <th class="table-th ">{{ __('Project') }}</th>
                            <th class="table-th ">{{ __('Invoice Date') }}</th>
                            <th class="table-th ">{{ __('Due Date') }}</th>
                            <th class="table-th text-right">{{ __('Total Amount') }}</th>
                            <th class="table-th ">{{ __('Status') }}</th>
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

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            /* Estilos generales y de DataTables (copiados de quotes.index o projects.index) */
            table.dataTable th, table.dataTable td { white-space: nowrap; }
            .table-th { padding-left: 1rem; padding-right: 1rem; padding-top: 0.75rem; padding-bottom: 0.75rem; text-align: left; font-size: 0.75rem; line-height: 1rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; } /* slate-500 */
            .dark .table-th { color: #94a3b8; } /* dark:text-slate-300 */
            .table-td { padding-left: 1rem; padding-right: 1rem; padding-top: 0.75rem; padding-bottom: 0.75rem; font-size: 0.875rem; line-height: 1.25rem; vertical-align: middle;}
            table.dataTable#invoicesTable { border-spacing: 0; }
            table.dataTable#invoicesTable th, table.dataTable#invoicesTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#invoicesTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#invoicesTable tbody tr:hover { background-color: #1f2937; }
            /* ... (otros estilos de DataTables, SweetAlert que ya tenías) ... */
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding-top: 1rem; padding-bottom: 1rem; }
            .dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select {
                padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff;
            }
            .dark .dataTables_wrapper .dataTables_filter input, .dark .dataTables_wrapper .dataTables_length select {
                background-color: #374151; border-color: #4b5563; color: #f3f4f6;
            }
            .dataTables_wrapper .dataTables_length select { padding-right: 2rem; appearance: none;}
        </style>
    @endpush

    @push('scripts')
        {{-- Cargar jQuery PRIMERO (si no está globalmente) --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            $(function() {
                if (typeof $.fn.DataTable === 'undefined') {
                    console.error('DataTables plugin is not loaded.');
                    $('#invoicesTable tbody').html('<tr><td colspan="9" class="text-center py-10 text-red-500">{{ __("DataTable library not loaded.") }}</td></tr>');
                    return;
                }

                const invoicesDataTable = $('#invoicesTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("invoices.data") }}',
                        type: 'GET',
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error details for invoices:", jqXHR);
                            let errorMsg = "{{ __('Error loading invoices. Please try again.') }}";
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>";
                            }
                             $('#invoicesTable tbody').html(`<tr><td colspan="9" class="text-center py-10 text-red-500">${errorMsg}</td></tr>`);
                        }
                    },
                    columns: [
                        { data: 'invoice_number', name: 'invoice_number', className: 'table-td' },
                        { data: 'client_name', name: 'client.name', className: 'table-td' },
                        { data: 'quote_number', name: 'quote.quote_number', className: 'table-td', defaultContent: '-' },
                        { data: 'project_title', name: 'project.project_title', className: 'table-td', defaultContent: '-' },
                        { data: 'invoice_date', name: 'invoice_date', className: 'table-td' },
                        { data: 'due_date', name: 'due_date', className: 'table-td' },
                        { data: 'total_amount', name: 'total_amount', className: 'table-td text-right' },
                        { data: 'status', name: 'status', className: 'table-td' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'table-td text-center' }
                    ],
                    order: [[4, "desc"]], // Ordenar por fecha de factura descendente por defecto
                    dom: "<'md:flex justify-between items-center mb-4'<'flex-1'l><'flex-1'f>>" +
                         "<'overflow-x-auto'tr>" +
                         "<'md:flex justify-between items-center mt-4'<'flex-1'i><'flex-1'p>>",
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                        search: "_INPUT_",
                        searchPlaceholder: "{{ __('Search invoices...') }}",
                        lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                    },
                    initComplete: function(settings, json) {
                        const $wrapper = $('#invoicesTable_wrapper');
                        $wrapper.find('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                        $wrapper.find('.dataTables_length select').addClass('inputField px-3 py-2 pr-8 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition appearance-none');
                    }
                });

                // Handler para eliminar/anular una factura
                $('#invoicesTable').on('click', '.deleteInvoice', function () {
                    const invoiceId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This action might delete or cancel the invoice. This cannot be undone!") }}', // Ajustar texto
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Confirm") }}', // Cambiar texto
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/invoices/${invoiceId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('{{ __("Done!") }}', data.success, 'success'); // Cambiar título
                                    invoicesDataTable.ajax.reload(null, false);
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Delete/Cancel invoice error:', error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while processing the invoice action.") }}', 'error');
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
