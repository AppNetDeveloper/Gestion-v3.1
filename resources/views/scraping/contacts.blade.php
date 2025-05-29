<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- Usará $breadcrumbItems definido en el controlador showContacts --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems" :page-title="__('Scraping Task Contacts') . ' (ID: ' . $task->id . ')'" />
    </div>

    {{-- Alert start --}}
    {{-- Puedes añadir alerts específicos para esta página si es necesario --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" class="mb-5" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" class="mb-5" />
    @endif
    {{-- Alert end --}}

    {{-- Task Details Card --}}
    <div class="card shadow rounded-lg overflow-hidden mb-8">
        <div class="bg-white dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
                <h4 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                    {{ __('Task Details') }}
                </h4>
                {{-- Botón para volver a la lista de tareas --}}
                <a href="{{ route('scraping.tasks.index') }}" class="btn btn-sm btn-outline-secondary">
                     <iconify-icon icon="heroicons:arrow-left"></iconify-icon>
                    &nbsp;{{ __('Back to Tasks List') }}
                </a>
            </div>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><strong class="text-slate-600 dark:text-slate-300">{{ __('ID') }}:</strong> {{ $task->id }}</div>
            <div><strong class="text-slate-600 dark:text-slate-300">{{ __('Source') }}:</strong> {{ $task->source }}</div>
            <div><strong class="text-slate-600 dark:text-slate-300">{{ __('Keyword') }}:</strong> {{ $task->keyword }}</div>
            <div><strong class="text-slate-600 dark:text-slate-300">{{ __('Region') }}:</strong> {{ $task->region ?: '-' }}</div>
            <div><strong class="text-slate-600 dark:text-slate-300">{{ __('Status') }}:</strong> {{ __($task->status) }}</div>
            <div><strong class="text-slate-600 dark:text-slate-300">{{ __('Created At') }}:</strong> {{ $task->created_at->format('Y-m-d H:i:s') }}</div>
             <div><strong class="text-slate-600 dark:text-slate-300">{{ __('API Task ID') }}:</strong> {{ $task->api_task_id ?: '-' }}</div>
        </div>
    </div>

    {{-- Found Contacts Table Card --}}
    <div class="card shadow rounded-lg overflow-hidden">
        <div class="bg-white dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
             <h4 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                {{ __('Found Contacts') }} ({{ $contacts->total() }}) {{-- Mostrar total de contactos para esta tarea --}}
            </h4>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800">
            <div class="overflow-x-auto -mx-6 px-6">
                {{-- Tabla para mostrar contactos asociados a ESTA tarea --}}
                <table id="contactsTable" class="w-full min-w-[800px] display">
                    <thead class="bg-slate-100 dark:bg-slate-700 text-xs uppercase text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Name') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Phone') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Email') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Web') }}</th>
                            {{-- Puedes añadir más columnas si las necesitas --}}
                            <th class="px-4 py-3 font-medium text-center">{{ __('Actions') }}</th> {{-- Columna de acciones --}}
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-300">
                        @forelse($contacts as $contact)
                            <tr>
                                <td class="px-4 py-2">{{ $contact->name ?: '-' }}</td>
                                <td class="px-4 py-2">{{ $contact->phone ?: '-' }}</td>
                                <td class="px-4 py-2">{{ $contact->email ?: '-' }}</td>
                                <td class="px-4 py-2">
                                    @if($contact->web)
                                        <a href="{{ $contact->web }}" target="_blank" rel="noopener noreferrer" class="text-primary-500 hover:underline">
                                            {{ Str::limit($contact->web, 50) }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <div class="actions-wrapper" style="gap: 0.75rem;"> {{-- Añadir algo de espacio --}}
                                        {{-- Enlace para Editar (lleva a la ruta estándar de contactos) --}}
                                        {{-- Asegúrate que la ruta 'contacts.edit' existe --}}
                                        <a href="{{ route('contacts.edit', $contact->id) }}" class="action-icon editContact" title="{{ __('Edit Contact') }}">
                                            <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                                        </a>

                                        {{-- Formulario para Borrar (usa la ruta estándar de contactos) --}}
                                        {{-- Asegúrate que la ruta 'contacts.destroy' existe --}}
                                         <form action="{{ route('contacts.destroy', $contact->id) }}" method="POST" class="inline-block deleteContactForm" data-contact-name="{{ $contact->name ?: __('Unnamed Contact') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-icon deleteContact" title="{{ __('Delete Contact') }}">
                                                <iconify-icon icon="heroicons:trash"></iconify-icon>
                                            </button>
                                        </form>
                                        {{-- Puedes añadir aquí botones para WhatsApp, Telegram si es necesario --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">{{ __("No contacts found for this task.") }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Enlaces de Paginación --}}
            <div class="mt-6">
                {{ $contacts->links() }}
            </div>
        </div>
    </div>

    {{-- Estilos y Scripts --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"/>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css"/>
        <style>
             /* Estilos básicos para los iconos de acción (puedes copiarlos de la otra vista si quieres) */
            .actions-wrapper { display: inline-flex; gap: 0.75rem; align-items: center; }
            .action-icon { display: inline-block; color: #64748b; transition: color 0.15s ease-in-out; font-size: 1.25rem; cursor: pointer; background: none; border: none; padding: 0;}
            .dark .action-icon { color: #94a3b8; }
            .action-icon:hover { color: #1e293b; }
            .dark .action-icon:hover { color: #f1f5f9; }
            .action-icon.deleteContact:hover { color: #ef4444; }
            .action-icon.editContact:hover { color: #3b82f6; }
             /* SweetAlert2 Customization */
            .swal2-popup { border-radius: 0.5rem; background-color: #ffffff; }
            .dark .swal2-popup { background-color: #1e293b; color: #e2e8f0; }
            .dark .swal2-title { color: #f1f5f9; }
            .dark .swal2-html-container { color: #cbd5e1; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
        <script>
             $(document).ready(function() {
                // Add a container for the buttons above the table
                $('.card').find('.bg-white.dark\:bg-slate-800.px-6.py-4').append(
                    '<div class="mt-3 flex justify-end">' +
                    '   <button id="exportExcelBtn" class="btn btn-primary btn-sm">' +
                    '       <i class="mr-1"></i> {{ __("Export to Excel") }}' +
                    '   </button>' +
                    '</div>'
                );

                var table = $('#contactsTable').DataTable({
                    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' // Spanish language file
                    },
                    pageLength: 25,
                    responsive: true,
                    order: [[0, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: -1 } // Disable sorting on Actions column
                    ]
                });


                // Add Excel export button functionality
                $('#exportExcelBtn').on('click', function() {
                    let excelButton = $('.buttons-excel');
                    if (excelButton.length) {
                        excelButton[0].click(); // Trigger the export
                    } else {
                        // Fallback if DataTables buttons not initialized
                        table.button('.buttons-excel').trigger();
                    }
                });

                // Initialize the Excel button (hidden)
                new $.fn.dataTable.Buttons(table, {
                    buttons: [
                        {
                            extend: 'excel',
                            className: 'd-none',
                            exportOptions: {
                                columns: [0, 1, 2, 3] // Export all columns except Actions
                            },
                            title: 'Contacts_Task_{{ $task->id }}',
                            filename: 'contacts_task_{{ $task->id }}',
                            titleAttr: '{{ __("Export to Excel") }}',
                            text: '{{ __("Export to Excel") }}'
                        }
                    ]
                });

                // Add the hidden buttons to the document
                table.buttons(0, null).container().appendTo($('#exportExcelBtn').closest('.flex'));
                
                // --- Toast ---
                function buildToast() {
                    const isDark = document.documentElement.classList.contains('dark');
                    return Swal.mixin({ 
                        toast: true, 
                        position: 'top-end', 
                        showConfirmButton: false, 
                        timer: 3000, 
                        timerProgressBar: true, 
                        backdrop: false, 
                        heightAuto: false, 
                        allowOutsideClick: false, 
                        allowEnterKey: false, 
                        returnFocus: false, 
                        focusConfirm: false, 
                        focusCancel: false, 
                        focusDeny: false, 
                        draggable: false, 
                        keydownListenerCapture: false, 
                        customClass: { popup: isDark ? 'dark' : '' }, 
                        didOpen: (toast) => { 
                            toast.addEventListener('mouseenter', Swal.stopTimer); 
                            toast.addEventListener('mouseleave', Swal.resumeTimer); 
                        } 
                    });
                }
                
                let Toast = buildToast();
                const observer = new MutationObserver(() => { 
                    Toast = buildToast(); 
                });
                observer.observe(document.documentElement, { attributes: true });
                // --- End Toast ---

                 // Confirmación para borrar contacto desde esta vista
                 $('.deleteContactForm').on('submit', function(e) {
                    e.preventDefault(); // Prevenir envío normal
                    const form = this;
                    const contactName = $(this).data('contact-name');

                     Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: `{{ __("This will delete the contact:") }} ${contactName}`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit(); // Enviar el formulario si se confirma
                        }
                    });
                 });
             });
        </script>
    @endpush

</x-app-layout>
