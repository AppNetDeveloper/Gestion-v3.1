<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Services Management')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    {{-- Mostrar errores de validación del formulario de creación --}}
    @if ($errors->any() && old('_token')) {{-- old('_token') ayuda a asegurar que los errores son de este formulario --}}
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
            {{-- Formulario colapsable para crear nuevo servicio --}}
            <div class="mb-8 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                {{-- Encabezado para desplegar/colapsar --}}
                <div id="toggleServiceFormHeader" class="flex justify-between items-center cursor-pointer">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Create New Service') }}</h3>
                    {{-- Botón de Toggle con fondo azul en modo claro --}}
                    <button type="button" aria-expanded="false" aria-controls="serviceFormContainer"
                            class="bg-indigo-100 hover:bg-indigo-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-indigo-600 dark:text-indigo-400 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800
                                   flex items-center justify-center w-8 h-8 transition-colors duration-150"> {{-- Clases para tamaño, centrado, fondo y transición --}}
                        <iconify-icon id="formToggleIcon" icon="heroicons:plus-circle-20-solid" class="text-2xl transition-transform duration-300 ease-in-out"></iconify-icon>
                    </button>
                </div>

                {{-- Contenedor del formulario (inicialmente colapsado por CSS) --}}
                <div id="serviceFormContainer" class="overflow-hidden"> {{-- mt-6 se maneja con CSS/JS --}}
                    <form action="{{ route('services.store') }}" method="POST" class="space-y-6 pt-6"> {{-- Añadido pt-6 para padding superior --}}
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Service Name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="inputField w-full p-3 border {{ $errors->has('name') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                   placeholder="{{ __('Enter service name...') }}" value="{{ old('name') }}" required>
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Description') }}
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('description') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                      placeholder="{{ __('Enter service description...') }}">{{ old('description') }}</textarea>
                             @error('description')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="default_price" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Default Price') }} (€) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="default_price" name="default_price" step="0.01" min="0"
                                       class="inputField w-full p-3 border {{ $errors->has('default_price') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="0.00" value="{{ old('default_price') }}" required>
                                @error('default_price')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="unit" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Unit') }} (e.g., hora, mes, proyecto)
                                </label>
                                <input type="text" id="unit" name="unit"
                                       class="inputField w-full p-3 border {{ $errors->has('unit') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="{{ __('e.g., hora, mes, proyecto') }}" value="{{ old('unit') }}">
                                @error('unit')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            {{-- Botón "Create Service" con estilo más visible --}}
                            <button type="submit"
                                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600 text-white font-medium text-sm rounded-md shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 transition duration-150 ease-in-out">
                                {{-- Puedes volver a añadir el icono si quieres, ahora que el botón tiene fondo --}}
                                {{-- <iconify-icon icon="heroicons:plus-circle" class="mr-2"></iconify-icon> --}}
                                {{ __('Create Service') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabla de servicios --}}
            <div class="mt-8">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('List of Services') }}</h3>
                <div class="overflow-x-auto">
                    <table id="servicesTable" class="w-full border-collapse dataTable">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('ID') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Description') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Price') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Unit') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Created At') }}</th>
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
    </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            .inputField:focus { /* Tailwind's focus classes handle this */ }
            table.dataTable#servicesTable { border-spacing: 0; }
            table.dataTable#servicesTable th, table.dataTable#servicesTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#servicesTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#servicesTable tbody tr:hover { background-color: #1f2937; }
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

            /* Estilos para el contenedor colapsable */
            #serviceFormContainer {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out;
                opacity: 0;
                padding-top: 0 !important;
            }
            #serviceFormContainer.expanded {
                max-height: 1000px;
                opacity: 1;
                padding-top: 1.5rem !important; /* Corresponde a pt-6 en el form */
            }
            #formToggleIcon.rotated {
                transform: rotate(180deg);
            }
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
            // Esperar a que el DOM esté completamente cargado (Vanilla JS)
            document.addEventListener('DOMContentLoaded', function() {
                // Lógica para el formulario colapsable
                const toggleHeader = document.getElementById('toggleServiceFormHeader');
                const formContainer = document.getElementById('serviceFormContainer');
                const toggleIconElement = document.getElementById('formToggleIcon');

                if (toggleHeader && formContainer && toggleIconElement) {
                    function setFormState(expand, animate = true) {
                        if (!animate) {
                            formContainer.style.transition = 'none';
                        } else {
                            formContainer.style.transition = 'max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out';
                        }

                        if (expand) {
                            formContainer.classList.add('expanded');
                            toggleIconElement.setAttribute('icon', 'heroicons:minus-circle-20-solid');
                            toggleIconElement.classList.add('rotated');
                            toggleHeader.setAttribute('aria-expanded', 'true');
                        } else {
                            formContainer.classList.remove('expanded');
                            toggleIconElement.setAttribute('icon', 'heroicons:plus-circle-20-solid');
                            toggleIconElement.classList.remove('rotated');
                            toggleHeader.setAttribute('aria-expanded', 'false');
                        }

                         if (!animate) {
                           requestAnimationFrame(() => {
                                formContainer.style.transition = 'max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out';
                           });
                        }
                    }

                    const hasValidationErrors = {{ ($errors->any() && old('_token')) ? 'true' : 'false' }};
                    setFormState(hasValidationErrors, false);

                    toggleHeader.addEventListener('click', function () {
                        const isExpanded = formContainer.classList.contains('expanded');
                        setFormState(!isExpanded);
                    });
                } else {
                     console.error('Toggle elements not found! Check IDs: toggleServiceFormHeader, serviceFormContainer, formToggleIcon');
                }

                // Inicializar DataTables y otros plugins que dependen de jQuery
                $(function() {
                    if (typeof $ === 'undefined') {
                        console.error('jQuery is not loaded. Cannot initialize DataTables or attach jQuery event handlers.');
                        return;
                    }
                    if (typeof $.fn.DataTable === 'undefined') {
                         console.error('DataTables plugin is not loaded.');
                    } else {
                        const servicesDataTable = $('#servicesTable').DataTable({
                            dom: "<'flex flex-col md:flex-row md:justify-between gap-4 mb-4'<'md:w-1/2'l><'md:w-1/2'f>>" +
                                 "<'overflow-x-auto't>" +
                                 "<'flex flex-col md:flex-row md:justify-between gap-4 mt-4'<'md:w-1/2'i><'md:w-1/2'p>>",
                            ajax: {
                                url: '{{ route("services.data") }}',
                                dataSrc: 'data',
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.error("AJAX error details:", jqXHR);
                                    let errorMsg = "{{ __('Error loading data. Please try again.') }}";
                                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                        errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>";
                                    } else if (jqXHR.responseText) {
                                        console.error("Server Response Text:", jqXHR.responseText);
                                    }
                                     $('#servicesTable tbody').html(
                                        `<tr><td colspan="7" class="text-center py-10 text-red-500">${errorMsg}</td></tr>`
                                    );
                                }
                            },
                            columns: [
                                { data: 'id', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'name', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                {
                                    data: 'description',
                                    className: 'text-sm text-slate-700 dark:text-slate-300',
                                    render: function(data, type, row) {
                                        if (type === 'display' && data && data.length > 40) {
                                            const escapedData = $('<div/>').text(data).html();
                                            return `<span title="${escapedData}">${$('<div/>').text(data.substring(0, 40)).html()}...</span>`;
                                        }
                                        return data ? $('<div/>').text(data).html() : '';
                                    }
                                },
                                { data: 'default_price', className: 'text-sm text-slate-700 dark:text-slate-300 text-right' },
                                { data: 'unit', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'created_at', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'action', orderable: false, className: 'text-sm text-center' }
                            ],
                            order: [[0, "desc"]],
                            responsive: true,
                            autoWidth: false,
                            language: {
                                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                                search: "_INPUT_",
                                searchPlaceholder: "{{ __('Search services...') }}",
                                lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                            },
                            initComplete: function(settings, json) {
                                $('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                                $('.dataTables_length select').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                            }
                        });

                        // Handler para eliminar un servicio
                        $('#servicesTable').on('click', '.deleteService', function () {
                            const serviceId = $(this).data('id');
                            Swal.fire({
                                title: '{{ __("Are you sure?") }}',
                                text: '{{ __("This will delete the service permanently.") }}',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '{{ __("Delete") }}',
                                cancelButtonText: '{{ __("Cancel") }}',
                                confirmButtonColor: '#e11d48',
                                cancelButtonColor: '#64748b',
                                customClass: { popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '' }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch(`/services/${serviceId}`, {
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
                                            servicesDataTable.ajax.reload(null, false);
                                        } else {
                                            Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Delete error:', error);
                                        Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the service.") }}', 'error');
                                    });
                                }
                            });
                        });

                        // Handler para editar un servicio
                        $('#servicesTable').on('click', '.editService', function () {
                            const serviceId = $(this).data('id');
                            const name = $(this).data('name');
                            const description = $(this).data('description');
                            const default_price = $(this).data('default_price');
                            const unit = $(this).data('unit');

                            Swal.fire({
                                title: '{{ __("Edit Service") }}',
                                html: `
                                    <div class="space-y-4 text-left">
                                        <div>
                                            <label for="edit_service_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Service Name") }} <span class="text-red-500">*</span></label>
                                            <input type="text" id="edit_service_name" class="custom-swal-input" value="${$('<div/>').text(name).html()}" required>
                                        </div>
                                        <div>
                                            <label for="edit_service_description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Description") }}</label>
                                            <textarea id="edit_service_description" class="custom-swal-textarea" rows="3">${$('<div/>').text(description).html()}</textarea>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="edit_service_price" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Default Price") }} (€) <span class="text-red-500">*</span></label>
                                                <input type="number" id="edit_service_price" step="0.01" class="custom-swal-input" value="${default_price}" required>
                                            </div>
                                            <div>
                                                <label for="edit_service_unit" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Unit") }}</label>
                                                <input type="text" id="edit_service_unit" class="custom-swal-input" value="${$('<div/>').text(unit).html()}">
                                            </div>
                                        </div>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: '{{ __("Save Changes") }}',
                                cancelButtonText: '{{ __("Cancel") }}',
                                confirmButtonColor: '#4f46e5',
                                customClass: { popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '' },
                                preConfirm: () => {
                                    const newName = document.getElementById('edit_service_name').value;
                                    const newPrice = document.getElementById('edit_service_price').value;
                                    let errors = [];
                                    if (!newName.trim()) errors.push('{{ __("Service name is required.") }}');
                                    if (!newPrice || isNaN(parseFloat(newPrice)) || parseFloat(newPrice) < 0) errors.push('{{ __("Valid default price is required.") }}');

                                    if (errors.length > 0) {
                                        Swal.showValidationMessage(errors.join('<br>'));
                                        return false;
                                    }
                                    return {
                                        name: newName,
                                        description: document.getElementById('edit_service_description').value,
                                        default_price: parseFloat(newPrice),
                                        unit: document.getElementById('edit_service_unit').value
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    const data = result.value;
                                    fetch(`/services/${serviceId}`, {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify(data)
                                    })
                                    .then(response => response.json())
                                    .then(resp => {
                                        if (resp.success) {
                                            Swal.fire('{{ __("Updated!") }}', resp.success, 'success');
                                            servicesDataTable.ajax.reload(null, false);
                                        } else {
                                            if (resp.errors) {
                                                let errorMessages = Object.values(resp.errors).flat().join('<br>');
                                                Swal.fire('{{ __("Validation Error") }}', errorMessages, 'error');
                                            } else {
                                                Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred while updating.") }}', 'error');
                                            }
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Update error:', error);
                                        Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while updating the service.") }}', 'error');
                                    });
                                }
                            });
                        });
                    } // Fin de if (typeof $.fn.DataTable !== 'undefined')

                }); // Fin de $(function() { ... });

            }); // Fin de document.addEventListener('DOMContentLoaded', ...)
        </script>
    @endpush
</x-app-layout>
