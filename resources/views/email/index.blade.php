<x-app-layout>
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    <!-- Mensajes flash -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">{{ __("Éxito!") }}</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">{{ __("Error!") }}</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if(isset($error) && $error)
        <div class="bg-red-200 border border-red-500 text-red-800 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">{{ __("Conexión IMAP Fallida:") }}</strong>
            <span class="block sm:inline">{{ $error }}</span>
        </div>
    @endif

    <!-- Contenedor general: dos columnas (lista a la izquierda, detalle a la derecha) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Columna Izquierda: Lista de carpetas + Lista de correos -->
        <div class="card bg-white dark:bg-gray-900 shadow p-4">
            <!-- Encabezado: Carpeta actual y botones IMAP/SMTP -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center space-x-2">
                    <h2 class="text-xl font-bold">{{ __("Bandeja:") }}</h2>
                </div>
                <div class="flex space-x-2">
                    <!-- Botón editar IMAP -->
                    <button type="button"
                        class="btn btn-dark rounded-full p-2 hover:bg-blue-600 text-white"
                        onclick="document.getElementById('modal-imap-settings').classList.toggle('hidden')"
                        title="{{ __('Editar IMAP') }}">
                        <iconify-icon icon="heroicons-outline:pencil-alt" class="text-xl"></iconify-icon>
                    </button>
                    <!-- Botón editar SMTP -->
                    <button type="button"
                        class="btn btn-dark rounded-full p-2 hover:bg-green-600 text-white"
                        onclick="document.getElementById('modal-smtp-settings').classList.toggle('hidden')"
                        title="{{ __('Editar SMTP') }}">
                        <iconify-icon icon="heroicons-outline:adjustments" class="text-xl"></iconify-icon>
                    </button>
                </div>
            </div>

            <!-- Barra de carpetas (con el mismo estilo de botones) -->
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach($folders as $f)
                    @php $folderName = $f->name; @endphp
                    <a href="javascript:void(0);"
                       onclick="loadEmailList('{{ $folderName }}')"
                       class="btn btn-dark rounded-full px-3 py-1 text-sm
                              {{ $folder === $folderName ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}"
                       title="{{ $folderName }}">
                       {{ $folderName }}
                    </a>
                @endforeach
            </div>

            <!-- Lista de correos (Cargada mediante AJAX) -->
            <div id="email-list-container" class="bg-white dark:bg-gray-900 border rounded p-2">
                <!-- Esta lista se actualizará mediante AJAX -->
            </div>
        </div>

        <!-- Columna Derecha: Detalle del correo -->
        <div id="email-detail-container" class="card bg-white dark:bg-gray-900 shadow p-4">
            <p class="text-gray-500">{{ __("Selecciona un correo para ver su contenido.") }}</p>
        </div>
    </div>

    <!-- Modal para editar configuración IMAP -->
    @include('email.partials.imap-settings')
    <!-- Modal para editar configuración SMTP -->
    @include('email.partials.smtp-settings')

    @push('styles')
        <style>
            .card {
                border-radius: 0.5rem;
            }
            .dark .card {
                background-color: #1e293b; /* Ejemplo de fondo oscuro */
            }
            /* Ajusta el color del texto en modo oscuro */
            .dark .card h2, .dark .card p, .dark .card label, .dark .card span {
                color: #cbd5e1;
            }
            .prose-dark p, .prose-dark li {
                color: #cbd5e1 !important;
            }
            #modal-imap-settings, #modal-smtp-settings {
                z-index: 1000;
            }
            .prose img {
                max-width: 100%;
                height: auto;
            }
        </style>
    @endpush

    @push('scripts')
        <!-- Iconify para los iconos -->
        <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
        <script>
            // Función para cargar la lista de correos al cambiar de carpeta
            function loadEmailList(folder) {
                const url = "{{ route('emails.index', '') }}/?folder=" + folder;

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('email-list-container').innerHTML = data;
                })
                .catch(error => console.error('Error al cargar los correos:', error));
            }

            // Cargar la lista de correos al iniciar
            loadEmailList('{{ $folder }}');

            // Función para cargar los detalles de un correo cuando se selecciona
            function loadMessageDetail(uid, folder) {
                const url = "{{ route('emails.show', '') }}/" + uid + "?folder=" + folder;

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('email-detail-container').innerHTML = data.html;
                })
                .catch(error => console.error('Error al cargar el correo:', error));
            }
        </script>
    @endpush
</x-app-layout>
