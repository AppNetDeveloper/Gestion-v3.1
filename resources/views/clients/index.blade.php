<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- Asegúrate de pasar $breadcrumbItems desde ClientController@index --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Clients Management')" />
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
            {{-- Formulario colapsable para crear nuevo cliente --}}
            <div class="mb-8 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                {{-- Encabezado para desplegar/colapsar --}}
                <div id="toggleClientFormHeader" class="flex justify-between items-center cursor-pointer">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Create New Client') }}</h3>
                    <button type="button" aria-expanded="false" aria-controls="clientFormContainer"
                            class="bg-indigo-100 hover:bg-indigo-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-indigo-600 dark:text-indigo-400 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800
                                   flex items-center justify-center w-8 h-8 transition-colors duration-150">
                        <iconify-icon id="clientFormToggleIcon" icon="heroicons:plus-circle-20-solid" class="text-2xl transition-transform duration-300 ease-in-out"></iconify-icon>
                    </button>
                </div>

                {{-- Contenedor del formulario (inicialmente colapsado por CSS) --}}
                <div id="clientFormContainer" class="overflow-hidden"> {{-- mt-6 se maneja con CSS/JS --}}
                    <form action="{{ route('clients.store') }}" method="POST" class="space-y-6 pt-6">
                        @csrf
                        {{-- Campo Nombre --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Client Name / Company Name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="inputField w-full p-3 border {{ $errors->has('name') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                   placeholder="{{ __('Enter client or company name...') }}" value="{{ old('name') }}" required>
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Fila Email y Teléfono --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Email') }}
                                </label>
                                <input type="email" id="email" name="email"
                                       class="inputField w-full p-3 border {{ $errors->has('email') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="client@example.com" value="{{ old('email') }}">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Phone') }}
                                </label>
                                <input type="tel" id="phone" name="phone"
                                       class="inputField w-full p-3 border {{ $errors->has('phone') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="+34 600 123 456" value="{{ old('phone') }}">
                                @error('phone')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Fila NIF/CIF y Tasa IVA --}}
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="vat_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('VAT Number (NIF/CIF)') }}
                                </label>
                                <input type="text" id="vat_number" name="vat_number"
                                       class="inputField w-full p-3 border {{ $errors->has('vat_number') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="B12345678 / 12345678Z" value="{{ old('vat_number') }}">
                                 @error('vat_number')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="vat_rate" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Applicable VAT Rate (%)') }}
                                </label>
                                <input type="number" id="vat_rate" name="vat_rate" step="0.01" min="0" max="100"
                                       class="inputField w-full p-3 border {{ $errors->has('vat_rate') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="21.00" value="{{ old('vat_rate', '21.00') }}"> {{-- Valor por defecto 21% --}}
                                 @error('vat_rate')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Campo Dirección --}}
                        <div>
                            <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Address') }}
                            </label>
                            <textarea id="address" name="address" rows="2"
                                      class="inputField w-full p-3 border {{ $errors->has('address') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                      placeholder="{{ __('Street, Number, Floor...') }}">{{ old('address') }}</textarea>
                             @error('address')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                         {{-- Fila Ciudad, CP, País --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="city" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('City') }}
                                </label>
                                <input type="text" id="city" name="city"
                                       class="inputField w-full p-3 border {{ $errors->has('city') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="{{ __('e.g., Madrid') }}" value="{{ old('city') }}">
                                @error('city')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Postal Code') }}
                                </label>
                                <input type="text" id="postal_code" name="postal_code"
                                       class="inputField w-full p-3 border {{ $errors->has('postal_code') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="28001" value="{{ old('postal_code') }}">
                                @error('postal_code')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                             <div>
                                <label for="country" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Country') }}
                                </label>
                                <input type="text" id="country" name="country"
                                       class="inputField w-full p-3 border {{ $errors->has('country') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                       placeholder="{{ __('e.g., Spain') }}" value="{{ old('country') }}">
                                @error('country')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Campo Notas --}}
                        <div>
                            <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Notes') }}
                            </label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('notes') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                      placeholder="{{ __('Internal notes about the client...') }}">{{ old('notes') }}</textarea>
                             @error('notes')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Botón Crear Cliente --}}
                        <div class="flex flex-wrap gap-3">
                            <button type="submit"
                                    class="px-6 py-2.5 bg-green-500 hover:bg-green-600 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-full flex items-center transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                                <iconify-icon icon="heroicons:user-plus-solid" class="mr-2"></iconify-icon>
                                {{ __('Create Client') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabla de clientes --}}
            <div class="mt-8">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('List of Clients') }}</h3>
                <div class="overflow-x-auto">
                    <table id="clientsTable" class="w-full border-collapse dataTable">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('ID') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('VAT Number') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('VAT Rate') }}</th> {{-- Nueva Columna --}}
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('City') }}</th>
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
            /* ... (Estilos existentes sin cambios) ... */
            .inputField:focus { /* Tailwind's focus classes handle this */ }
            table.dataTable#clientsTable { border-spacing: 0; }
            table.dataTable#clientsTable th, table.dataTable#clientsTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#clientsTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#clientsTable tbody tr:hover { background-color: #1f2937; }
            table.dataTable thead th.sorting:after, table.dataTable thead th.sorting_asc:after, table.dataTable thead th.sorting_desc:after { display: inline-block; margin-left: 5px; opacity: 0.5; color: inherit; }
            table.dataTable thead th.sorting:after { content: "\\2195"; }
            table.dataTable thead th.sorting_asc:after { content: "\\2191"; }
            table.dataTable thead th.sorting_desc:after { content: "\\2193"; }
            .swal2-popup { width: 90% !important; max-width: 700px !important; border-radius: 0.5rem !important; }
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

            /* Estilos para el contenedor colapsable (cliente) */
            #clientFormContainer { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out; opacity: 0; padding-top: 0 !important; }
            #clientFormContainer.expanded { max-height: 1500px; opacity: 1; padding-top: 1.5rem !important; }
            #clientFormToggleIcon.rotated { transform: rotate(180deg); }
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
                // Lógica para el formulario colapsable de Clientes
                const clientToggleHeader = document.getElementById('toggleClientFormHeader');
                const clientFormContainer = document.getElementById('clientFormContainer');
                const clientToggleIconElement = document.getElementById('clientFormToggleIcon');

                if (clientToggleHeader && clientFormContainer && clientToggleIconElement) {
                    function setClientFormState(expand, animate = true) {
                        // ... (lógica de colapso sin cambios) ...
                        if (!animate) { clientFormContainer.style.transition = 'none'; } else { clientFormContainer.style.transition = 'max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out'; }
                        if (expand) { clientFormContainer.classList.add('expanded'); clientToggleIconElement.setAttribute('icon', 'heroicons:minus-circle-20-solid'); clientToggleIconElement.classList.add('rotated'); clientToggleHeader.setAttribute('aria-expanded', 'true'); } else { clientFormContainer.classList.remove('expanded'); clientToggleIconElement.setAttribute('icon', 'heroicons:plus-circle-20-solid'); clientToggleIconElement.classList.remove('rotated'); clientToggleHeader.setAttribute('aria-expanded', 'false'); }
                        if (!animate) { requestAnimationFrame(() => { clientFormContainer.style.transition = 'max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out'; }); }
                    }
                    const clientHasValidationErrors = {{ ($errors->any() && old('_token')) ? 'true' : 'false' }};
                    setClientFormState(clientHasValidationErrors, false);
                    clientToggleHeader.addEventListener('click', function () { const isExpanded = clientFormContainer.classList.contains('expanded'); setClientFormState(!isExpanded); });
                } else { console.error('Client toggle elements not found!'); }

                // Inicializar DataTables y otros plugins que dependen de jQuery
                $(function() {
                    if (typeof $ === 'undefined') { console.error('jQuery is not loaded.'); return; }
                    if (typeof $.fn.DataTable === 'undefined') { console.error('DataTables plugin is not loaded.'); } else {
                        const clientsDataTable = $('#clientsTable').DataTable({
                            dom: "<'flex flex-col md:flex-row md:justify-between gap-4 mb-4'<'md:w-1/2'l><'md:w-1/2'f>>" + "<'overflow-x-auto't>" + "<'flex flex-col md:flex-row md:justify-between gap-4 mt-4'<'md:w-1/2'i><'md:w-1/2'p>>",
                            ajax: {
                                url: '{{ route("clients.data") }}',
                                dataSrc: 'data',
                                error: function (jqXHR, textStatus, errorThrown) {
                                    console.error("AJAX error details:", jqXHR);
                                    let errorMsg = "{{ __('Error loading data. Please try again.') }}";
                                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) { errorMsg += "<br><small>Server Error: " + $('<div/>').text(jqXHR.responseJSON.message).html() + "</small>"; } else if (jqXHR.responseText) { console.error("Server Response Text:", jqXHR.responseText); }
                                     $('#clientsTable tbody').html( `<tr><td colspan="9" class="text-center py-10 text-red-500">${errorMsg}</td></tr>` ); // Ajustado colspan a 9
                                }
                            },
                            columns: [ // Añadida columna vat_rate
                                { data: 'id', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'name', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'email', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'phone', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'vat_number', name: 'vat_number', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'vat_rate', name: 'vat_rate', className: 'text-sm text-slate-700 dark:text-slate-300 text-right', render: function(data) { return data !== null ? parseFloat(data).toFixed(2) + '%' : '-'; } }, // Nueva columna formateada
                                { data: 'city', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'created_at', className: 'text-sm text-slate-700 dark:text-slate-300' },
                                { data: 'action', orderable: false, searchable: false, className: 'text-sm text-center' }
                            ],
                            order: [[0, "desc"]],
                            responsive: true,
                            autoWidth: false,
                            language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json", search: "_INPUT_", searchPlaceholder: "{{ __('Search clients...') }}", lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}" },
                            initComplete: function(settings, json) {
                                $('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                                $('.dataTables_length select').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                            }
                        });

                        // Handler para eliminar un cliente (sin cambios)
                        $('#clientsTable').on('click', '.deleteClient', function () { /* ... */ });

                        // Handler para editar un cliente (actualizado para vat_rate)
                        $('#clientsTable').on('click', '.editClient', function () {
                            const clientId = $(this).data('id');
                            const name = $(this).data('name');
                            const email = $(this).data('email');
                            const phone = $(this).data('phone');
                            const vat_number = $(this).data('vat_number');
                            const vat_rate = $(this).data('vat_rate'); // Obtener vat_rate
                            const address = $(this).data('address');
                            const city = $(this).data('city');
                            const postal_code = $(this).data('postal_code');
                            const country = $(this).data('country');
                            const notes = $(this).data('notes');

                            Swal.fire({
                                title: '{{ __("Edit Client") }}',
                                html: `
                                    <div class="space-y-4 text-left">
                                        {{-- Nombre --}}
                                        <div> <label for="edit_client_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Client Name / Company Name") }} <span class="text-red-500">*</span></label> <input type="text" id="edit_client_name" class="custom-swal-input" value="${$('<div/>').text(name).html()}" required> </div>
                                        {{-- Email y Teléfono --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4"> <div> <label for="edit_client_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Email") }}</label> <input type="email" id="edit_client_email" class="custom-swal-input" value="${$('<div/>').text(email).html()}"> </div> <div> <label for="edit_client_phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Phone") }}</label> <input type="tel" id="edit_client_phone" class="custom-swal-input" value="${$('<div/>').text(phone).html()}"> </div> </div>
                                        {{-- VAT Number y VAT Rate --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div> <label for="edit_client_vat" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("VAT Number (NIF/CIF)") }}</label> <input type="text" id="edit_client_vat" class="custom-swal-input" value="${$('<div/>').text(vat_number).html()}"> </div>
                                            <div> <label for="edit_client_vat_rate" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Applicable VAT Rate (%)") }}</label> <input type="number" id="edit_client_vat_rate" step="0.01" min="0" max="100" class="custom-swal-input" value="${vat_rate !== null ? parseFloat(vat_rate).toFixed(2) : ''}" placeholder="21.00"> </div>
                                        </div>
                                        {{-- Dirección --}}
                                        <div> <label for="edit_client_address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Address") }}</label> <textarea id="edit_client_address" class="custom-swal-textarea" rows="2">${$('<div/>').text(address).html()}</textarea> </div>
                                        {{-- Ciudad, CP, País --}}
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4"> <div> <label for="edit_client_city" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("City") }}</label> <input type="text" id="edit_client_city" class="custom-swal-input" value="${$('<div/>').text(city).html()}"> </div> <div> <label for="edit_client_postal" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Postal Code") }}</label> <input type="text" id="edit_client_postal" class="custom-swal-input" value="${$('<div/>').text(postal_code).html()}"> </div> <div> <label for="edit_client_country" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Country") }}</label> <input type="text" id="edit_client_country" class="custom-swal-input" value="${$('<div/>').text(country).html()}"> </div> </div>
                                        {{-- Notas --}}
                                        <div> <label for="edit_client_notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Notes") }}</label> <textarea id="edit_client_notes" class="custom-swal-textarea" rows="3">${$('<div/>').text(notes).html()}</textarea> </div>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: '{{ __("Save Changes") }}',
                                cancelButtonText: '{{ __("Cancel") }}',
                                confirmButtonColor: '#4f46e5',
                                customClass: { popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '' },
                                preConfirm: () => {
                                    const newName = document.getElementById('edit_client_name').value;
                                    let errors = [];
                                    if (!newName.trim()) errors.push('{{ __("Client name is required.") }}');
                                    const newVatRate = document.getElementById('edit_client_vat_rate').value;
                                    if (newVatRate && (isNaN(parseFloat(newVatRate)) || parseFloat(newVatRate) < 0 || parseFloat(newVatRate) > 100)) {
                                         errors.push('{{ __("VAT Rate must be a number between 0 and 100.") }}');
                                    }

                                    if (errors.length > 0) { Swal.showValidationMessage(errors.join('<br>')); return false; }
                                    return {
                                        name: newName,
                                        email: document.getElementById('edit_client_email').value,
                                        phone: document.getElementById('edit_client_phone').value,
                                        vat_number: document.getElementById('edit_client_vat').value,
                                        vat_rate: newVatRate ? parseFloat(newVatRate).toFixed(2) : null, // Enviar null si está vacío
                                        address: document.getElementById('edit_client_address').value,
                                        city: document.getElementById('edit_client_city').value,
                                        postal_code: document.getElementById('edit_client_postal').value,
                                        country: document.getElementById('edit_client_country').value,
                                        notes: document.getElementById('edit_client_notes').value
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    const data = result.value;
                                    fetch(`/clients/${clientId}`, {
                                        method: 'PUT',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                        body: JSON.stringify(data)
                                    })
                                    .then(response => response.json())
                                    .then(resp => {
                                        if (resp.success) {
                                            Swal.fire('{{ __("Updated!") }}', resp.success, 'success');
                                            clientsDataTable.ajax.reload(null, false);
                                        } else {
                                            if (resp.errors) { let errorMessages = Object.values(resp.errors).flat().join('<br>'); Swal.fire('{{ __("Validation Error") }}', errorMessages, 'error'); }
                                            else { Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred while updating.") }}', 'error'); }
                                        }
                                    })
                                    .catch(error => { console.error('Update error:', error); Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while updating the client.") }}', 'error'); });
                                }
                            });
                        });
                    } // Fin de if (typeof $.fn.DataTable !== 'undefined')

                }); // Fin de $(function() { ... });

            }); // Fin de document.addEventListener('DOMContentLoaded', ...)
        </script>
    @endpush
</x-app-layout>
