<x-app-layout>
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    {{-- Mensajes flash y errores --}}
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

    {{-- Barra de carpetas --}}
    <div class="mb-4 p-2 border rounded bg-gray-50">
        <ul class="flex space-x-4">
            @foreach($folders as $f)
                @php
                    // Asumimos que $f->name contiene el nombre de la carpeta.
                    $folderName = $f->name;
                @endphp
                <li>
                    <a href="{{ route('emails.index', ['folder' => $folderName]) }}"
                       class="px-3 py-1 rounded {{ $folder === $folderName ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                        {{ $folderName }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="flex flex-col lg:flex-row space-y-4 lg:space-y-0 lg:space-x-4 p-4">
        {{-- Columna Izquierda: Listado de correos y botón de configuración --}}
        <div class="w-full lg:w-1/3 border p-4 rounded shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">{{ __("Bandeja: ") }}{{ $folder }}</h2>
                <!-- Botón para editar configuración IMAP -->
                <div class="flex justify-end space-x-2">
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded"
                            onclick="document.getElementById('modal-imap-settings').classList.toggle('hidden')">
                        {{ __("Editar IMAP") }}
                    </button>
                    <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded"
                            onclick="document.getElementById('modal-smtp-settings').classList.toggle('hidden')">
                        {{ __("Editar SMTP") }}
                    </button>
                </div>
            </div>

            <!-- Contenedor para el listado de correos y la paginación -->
            <div id="email-list-container">
                {{-- Listado de correos --}}
                <ul class="divide-y">
                    @forelse($messages as $mail)
                        @php
                            $isRead = $mail->getFlags()->contains('\Seen');
                        @endphp
                        <li class="py-2">
                            <a href="{{ route('emails.show', $mail->getUid()) }}?folder={{ $folder }}" class="block hover:bg-gray-100 p-2 rounded">
                                <p class="{{ $isRead ? 'font-normal' : 'font-bold' }}">
                                    {{ decodeMimeHeader($mail->getSubject()) }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    De: {{ isset($mail->getFrom()[0]) ? $mail->getFrom()[0]->mail : 'N/D' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse((string)$mail->getDate())->format('d/m/Y H:i') }}
                                </p>
                            </a>
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">{{ __("No se encontraron correos.") }}</li>
                    @endforelse
                </ul>

                {{-- Paginación --}}
                <div class="mt-4">
                    {{ $messages->appends(request()->query())->links() }}
                </div>
            </div>
        </div>


        {{-- Columna Derecha: Detalle del correo --}}
        <div class="w-full lg:w-2/3 border p-4 rounded shadow">
            @if(isset($message))
                <h2 class="text-2xl font-bold mb-4">{{ decodeMimeHeader($message->getSubject()) }}</h2>
                <p class="text-sm text-gray-600 mb-2">
                    <strong>{{ __("De:") }}</strong> {{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : 'N/D' }}
                </p>
                <p class="text-xs text-gray-500 mb-4">
                    {{ \Carbon\Carbon::parse((string)$message->getDate())->format('d/m/Y H:i') }}
                </p>

                <div class="prose max-w-none mb-4">
                    {!! $message->getHTMLBody() ?: $message->getTextBody() !!}
                </div>

                {{-- Adjuntos (si existen) --}}
                @if(count($message->getAttachments()) > 0)
                    <div class="mb-4">
                        <h3 class="font-semibold">{{ __("Archivos adjuntos:") }}</h3>
                        <ul class="list-disc list-inside">
                            @foreach($message->getAttachments() as $index => $attachment)
                                <li>
                                    <a href="{{ route('emails.attachment.download', ['messageUid' => $message->getUid(), 'attachmentIndex' => $index]) }}"
                                       class="text-blue-500 hover:underline" target="_blank">
                                        {{ $attachment->getName() }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Área para responder --}}
                @if(isset($message))
                    <div class="mt-6">
                        <form action="{{ route('emails.reply', $message->getUid()) }}" method="POST">
                            @csrf
                            <!-- Campo oculto con el destinatario (el remitente del mensaje original) -->
                            <input type="hidden" name="to" value="{{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : '' }}">
                            <!-- Campo oculto con el asunto preformateado -->
                            <input type="hidden" name="subject" value="Re: {{ decodeMimeHeader($message->getSubject()) }}">
                            <label for="reply" class="font-semibold mb-2 block">{{ __("Responder (HTML permitido):") }}</label>
                            <textarea id="reply" name="content" class="w-full border rounded p-2" rows="6" placeholder="{{ __('Escribe tu respuesta...') }}"></textarea>
                            <button type="submit" class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                {{ __("Enviar Respuesta") }}
                            </button>
                        </form>
                    </div>
                @endif

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
    <!-- Modal para editar configuración SMTP -->
    <div id="modal-smtp-settings" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
        <div class="bg-white rounded p-6 w-11/12 md:w-1/2">
            <h3 class="text-xl font-bold mb-4">{{ __("Editar configuración SMTP") }}</h3>
            <form action="{{ route('emails.smtp.update') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="smtp_host" class="block font-medium">{{ __("SMTP Host") }}</label>
                    <input type="text" name="smtp_host" id="smtp_host" class="w-full border rounded p-2"
                        value="{{ auth()->user()->smtp_host }}" required>
                </div>
                <div class="mb-4">
                    <label for="smtp_port" class="block font-medium">{{ __("SMTP Port") }}</label>
                    <input type="number" name="smtp_port" id="smtp_port" class="w-full border rounded p-2"
                        value="{{ auth()->user()->smtp_port }}" required>
                </div>
                <div class="mb-4">
                    <label for="smtp_encryption" class="block font-medium">{{ __("SMTP Encryption") }}</label>
                    <input type="text" name="smtp_encryption" id="smtp_encryption" class="w-full border rounded p-2"
                        value="{{ auth()->user()->smtp_encryption }}">
                </div>
                <div class="mb-4">
                    <label for="smtp_username" class="block font-medium">{{ __("SMTP Username") }}</label>
                    <input type="text" name="smtp_username" id="smtp_username" class="w-full border rounded p-2"
                        value="{{ auth()->user()->smtp_username }}" required>
                </div>
                <div class="mb-4">
                    <label for="smtp_password" class="block font-medium">{{ __("SMTP Password") }}</label>
                    <input type="password" name="smtp_password" id="smtp_password" class="w-full border rounded p-2"
                        value="{{ auth()->user()->smtp_password }}" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="mr-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded"
                            onclick="document.getElementById('modal-smtp-settings').classList.add('hidden')">
                        {{ __("Cancelar") }}
                    </button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
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
            function updateEmailList() {
                const appUrl = "{{ rtrim(config('app.url'), '/') }}";
                    let url = `${appUrl}/emails?folder={{ $folder }}`;

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    // Actualizamos el contenedor con la respuesta HTML del partial
                    const container = document.getElementById('email-list-container');
                    if (container) {
                        container.innerHTML = html;
                    } else {
                        console.error('Contenedor no encontrado');
                    }
                })
                .catch(error => console.error('Error al actualizar los emails:', error));
            }
            setInterval(updateEmailList, 180000);
        </script>
    @endpush
</x-app-layout>
