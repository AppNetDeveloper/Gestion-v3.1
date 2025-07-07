<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Scraping Management')" />
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
            {{-- Formulario colapsable para crear nueva tarea de scraping --}}
            <div class="mb-8 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                {{-- Encabezado para desplegar/colapsar --}}
                <div id="toggleScrapingFormHeader" class="flex justify-between items-center cursor-pointer">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Create New Scraping Task') }}</h3>
                    {{-- Botón de Toggle con fondo azul en modo claro --}}
                    <button type="button" aria-expanded="false" aria-controls="scrapingFormContainer"
                            class="bg-indigo-100 hover:bg-indigo-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-indigo-600 dark:text-indigo-400 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800
                                   flex items-center justify-center w-8 h-8 transition-colors duration-150"> {{-- Clases para tamaño, centrado, fondo y transición --}}
                        <iconify-icon id="formToggleIcon" icon="heroicons:plus-circle-20-solid" class="text-2xl transition-transform duration-300 ease-in-out"></iconify-icon>
                    </button>
                </div>

                {{-- Contenedor del formulario (inicialmente colapsado por CSS) --}}
                <div id="scrapingFormContainer" class="overflow-hidden"> {{-- mt-6 se maneja con CSS/JS --}}
                    <form action="{{ route('scrapings.store') }}" method="POST" class="space-y-6 pt-6"> {{-- Añadido pt-6 para padding superior --}}
                        @csrf
                        <div>
                            <label for="keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Keywords') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea id="keywords" name="keywords"
                                   class="inputField w-full p-3 border {{ $errors->has('keywords') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                   placeholder="{{ __('Enter keywords separated by commas...') }}" rows="3" required>{{ old('keywords') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Enter multiple keywords separated by commas') }}</p>
                            @error('keywords')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-700 pt-4 mt-4">
                            <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ __('LinkedIn Credentials (Optional)') }}</h4>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">
                                <iconify-icon icon="heroicons:exclamation-triangle" class="inline-block mr-1"></iconify-icon>
                                {{ __('Warning: Providing LinkedIn credentials is optional and used at your own risk. LinkedIn may detect automated scraping activity.') }}
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="linkedin_username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        {{ __('LinkedIn Username') }}
                                    </label>
                                    <input type="text" id="linkedin_username" name="linkedin_username"
                                        class="inputField w-full p-3 border {{ $errors->has('linkedin_username') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                        placeholder="{{ __('example@email.com') }}" value="{{ old('linkedin_username') }}">
                                    @error('linkedin_username')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="linkedin_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        {{ __('LinkedIn Password') }}
                                    </label>
                                    <input type="password" id="linkedin_password" name="linkedin_password"
                                        class="inputField w-full p-3 border {{ $errors->has('linkedin_password') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                        placeholder="{{ __('Your password') }}">
                                    @error('linkedin_password')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            {{-- Botón "Create Scraping Task" con estilo del ejemplo (verde, redondeado, con icono) --}}
                            <button type="submit"
    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 transition-colors duration-150 flex items-center text-lg">
    <iconify-icon icon="bi:send-fill" class="mr-2"></iconify-icon>
    {{ __('Create Scraping Task') }}
</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabla de tareas de scraping --}}
            <div class="mt-8">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('List of Scraping Tasks') }}</h3>
                <div class="overflow-x-auto">
                    <table id="scrapingsTable" class="w-full border-collapse dataTable">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('ID') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Keywords') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Contacts') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Created At') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            {{-- Los datos se cargarán mediante DataTables y AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $(function() {
                    // Manejo del formulario colapsable
                    const toggleHeader = document.getElementById('toggleScrapingFormHeader');
                    const formContainer = document.getElementById('scrapingFormContainer');
                    const toggleIcon = document.getElementById('formToggleIcon');
                    
                    // Estado inicial: formulario colapsado
                    let isFormVisible = false;
                    formContainer.style.maxHeight = '0px';
                    
                    toggleHeader.addEventListener('click', function() {
                        isFormVisible = !isFormVisible;
                        if (isFormVisible) {
                            formContainer.style.maxHeight = formContainer.scrollHeight + 'px';
                            toggleIcon.setAttribute('icon', 'heroicons:minus-circle-20-solid');
                            toggleHeader.setAttribute('aria-expanded', 'true');
                        } else {
                            formContainer.style.maxHeight = '0px';
                            toggleIcon.setAttribute('icon', 'heroicons:plus-circle-20-solid');
                            toggleHeader.setAttribute('aria-expanded', 'false');
                        }
                    });

                    // Inicializar DataTables si está disponible
                    if (typeof $.fn.DataTable !== 'undefined') {
                        const scrapingsDataTable = $('#scrapingsTable').DataTable({
                            processing: true,
                            serverSide: true,
                            ajax: "{{ route('scrapings.data') }}",
                            columns: [
                                { data: 'id', name: 'id' },
                                { data: 'keywords', name: 'keywords' },
                                { 
                                    data: 'status', 
                                    name: 'status',
                                    render: function(data) {
                                        if (data == 0) return '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs">{{ __("Pending") }}</span>';
                                        if (data == 1) return '<span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-xs">{{ __("Completed") }}</span>';
                                        if (data == 2) return '<span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-xs">{{ __("Error") }}</span>';
                                        return data;
                                    }
                                },
                                { 
                                    data: 'contacts_count', 
                                    name: 'contacts_count',
                                    render: function(data, type, row) {
                                        if (data > 0) {
                                            return '<a href="/scrapings/' + row.id + '/contacts" class="text-indigo-600 dark:text-indigo-400 hover:underline">' + data + ' {{ __("contacts") }}</a>';
                                        }
                                        return data;
                                    }
                                },
                                { data: 'created_at', name: 'created_at' },
                                {
                                    data: 'action',
                                    name: 'action',
                                    orderable: false,
                                    searchable: false,
                                    className: 'text-center',
                                    render: function(data, type, row) {
                                        return `
                                            <div class="flex justify-center space-x-2">
                                                <button class="view-contacts-btn text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" data-id="${row.id}" title="{{ __('View Contacts') }}">
                                                    <iconify-icon icon="heroicons:eye-20-solid" class="text-xl"></iconify-icon>
                                                </button>
                                                <button class="delete-scraping-btn text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" data-id="${row.id}" title="{{ __('Delete') }}">
                                                    <iconify-icon icon="heroicons:trash-20-solid" class="text-xl"></iconify-icon>
                                                </button>
                                            </div>
                                        `;
                                    }
                                }
                            ],
                            order: [[0, 'desc']],
                            language: {
                                url: '{{ app()->getLocale() == "es" ? asset("vendor/datatables/es-ES.json") : "" }}'
                            },
                            responsive: true
                        });

                        // Manejar el botón de ver contactos
                        $('#scrapingsTable').on('click', '.view-contacts-btn', function() {
                            const scrapingId = $(this).data('id');
                            window.location.href = `/scrapings/${scrapingId}/contacts`;
                        });

                        // Manejar el botón de eliminar tarea de scraping
                        $('#scrapingsTable').on('click', '.delete-scraping-btn', function() {
                            const scrapingId = $(this).data('id');
                            
                            Swal.fire({
                                title: '{{ __("Are you sure?") }}',
                                text: '{{ __("You will not be able to recover this scraping task!") }}',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '{{ __("Yes, delete it!") }}',
                                cancelButtonText: '{{ __("Cancel") }}',
                                confirmButtonColor: '#ef4444',
                                customClass: { popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '' }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch(`/scrapings/${scrapingId}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    })
                                    .then(response => response.json())
                                    .then(resp => {
                                        if (resp.success) {
                                            Swal.fire('{{ __("Deleted!") }}', resp.success, 'success');
                                            scrapingsDataTable.ajax.reload(null, false);
                                        } else {
                                            Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred while deleting.") }}', 'error');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Delete error:', error);
                                        Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the scraping task.") }}', 'error');
                                    });
                                }
                            });
                        });
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
