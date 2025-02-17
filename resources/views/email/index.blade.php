<x-app-layout>
    {{-- Sección HEAD --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    {{-- Mensajes flash --}}
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

    {{-- Mensaje de error IMAP --}}
    @if(isset($error) && $error)
        <div class="bg-red-200 border border-red-500 text-red-800 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">{{ __("Conexión IMAP Fallida:") }}</strong>
            <span class="block sm:inline">{{ $error }}</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4 p-4">
        {{-- Columna Izquierda: Listado de correos y botón de configuración --}}
        <div class="w-full lg:w-1/3 border p-4 rounded shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">{{ __("Bandeja de entrada") }}</h2>
                <!-- Botón para editar configuración IMAP -->
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded"
                    onclick="document.getElementById('modal-settings').classList.toggle('hidden')">
                    {{ __("Editar IMAP") }}
                </button>
            </div>

            {{-- Listado de correos --}}
            <ul class="divide-y">
                @forelse($messages as $mail)
                    <li class="py-2">
                        <a href="{{ route('emails.show', $mail->getUid()) }}" class="block hover:bg-gray-100 p-2 rounded">
                            <p class="font-semibold">{{ $mail->getSubject() }}</p>
                            <p class="text-sm text-gray-600">
                                De: {{ isset($mail->getFrom()[0]) ? $mail->getFrom()[0]->mail : 'N/D' }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $mail->getDate()->format('d/m/Y H:i') }}</p>
                        </a>
                    </li>
                @empty
                    <li class="py-2 text-gray-500">{{ __("No se encontraron correos.") }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Columna Derecha: Detalle del correo --}}
        <div class="w-full lg:w-2/3 border p-4 rounded shadow">
            @if(isset($message))
                <h2 class="text-2xl font-bold mb-4">{{ $message->getSubject() }}</h2>
                <p class="text-sm text-gray-600 mb-2">
                    <strong>{{ __("De:") }}</strong> {{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : 'N/D' }}
                </p>
                <p class="text-xs text-gray-500 mb-4">{{ $message->getDate()->format('d/m/Y H:i') }}</p>
                <div class="prose max-w-none mb-4">
                    {!! $message->getHTMLBody() ?: $message->getTextBody() !!}
                </div>

                {{-- Adjuntos (si existen) --}}
                @if(count($message->getAttachments()) > 0)
                    <div class="mb-4">
                        <h3 class="font-semibold">{{ __("Archivos adjuntos:") }}</h3>
                        <ul class="list-disc list-inside">
                            @foreach($message->getAttachments() as $attachment)
                                <li>
                                    <a href="{{ $attachment->downloadUrl() }}" class="text-blue-500 hover:underline" target="_blank">
                                        {{ $attachment->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Área para responder --}}
                <div class="mt-6">
                    <label for="reply" class="font-semibold mb-2 block">{{ __("Responder (HTML permitido):") }}</label>
                    <textarea id="reply" name="reply" class="w-full border rounded p-2" rows="6" placeholder="{{ __('Escribe tu respuesta...') }}"></textarea>
                    <button class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        {{ __("Enviar Respuesta") }}
                    </button>
                </div>
            @else
                <p class="text-gray-500">{{ __("Selecciona un correo para ver su contenido.") }}</p>
            @endif
        </div>
    </div>

    {{-- Modal para editar configuración IMAP --}}
    <div id="modal-settings" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
        <div class="bg-white rounded p-6 w-11/12 md:w-1/2">
            <h3 class="text-xl font-bold mb-4">{{ __("Editar configuración IMAP") }}</h3>
            <form action="{{ route('emails.settings.update') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="imap_host" class="block font-medium">{{ __("Host") }}</label>
                    <input type="text" name="imap_host" id="imap_host" class="w-full border rounded p-2" value="{{ auth()->user()->imap_host }}" required>
                </div>
                <div class="mb-4">
                    <label for="imap_port" class="block font-medium">{{ __("Port") }}</label>
                    <input type="number" name="imap_port" id="imap_port" class="w-full border rounded p-2" value="{{ auth()->user()->imap_port }}" required>
                </div>
                <div class="mb-4">
                    <label for="imap_encryption" class="block font-medium">{{ __("Encryption") }}</label>
                    <input type="text" name="imap_encryption" id="imap_encryption" class="w-full border rounded p-2" value="{{ auth()->user()->imap_encryption }}">
                </div>
                <div class="mb-4">
                    <label for="imap_username" class="block font-medium">{{ __("Username") }}</label>
                    <input type="text" name="imap_username" id="imap_username" class="w-full border rounded p-2" value="{{ auth()->user()->imap_username }}" required>
                </div>
                <div class="mb-4">
                    <label for="imap_password" class="block font-medium">{{ __("Password") }}</label>
                    <input type="password" name="imap_password" id="imap_password" class="w-full border rounded p-2" value="{{ auth()->user()->imap_password }}" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="mr-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded" onclick="document.getElementById('modal-settings').classList.add('hidden')">
                        {{ __("Cancelar") }}
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        {{ __("Guardar") }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
        <style>
            #modal-settings { z-index: 1000; }
            .prose img { max-width: 100%; height: auto; }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Aquí puedes agregar scripts adicionales (por ejemplo, para AJAX en el envío de respuesta)
        </script>
    @endpush
</x-app-layout>
