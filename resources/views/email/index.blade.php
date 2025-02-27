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
                    <span class="text-xl font-bold text-blue-500">{{ $folder }}</span>
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
                    <a href="{{ route('emails.index', ['folder' => $folderName]) }}"
                       class="btn btn-dark rounded-full px-3 py-1 text-sm
                              {{ $folder === $folderName ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}"
                       title="{{ $folderName }}">
                       {{ $folderName }}
                    </a>
                @endforeach
            </div>

            <!-- Lista de correos -->
            <div id="email-list-container" class="bg-white dark:bg-gray-900 border rounded p-2">
                <ul class="divide-y dark:divide-gray-700">
                    @forelse($messages as $mail)
                        @php
                            $isRead = $mail->getFlags()->contains('\Seen');
                        @endphp
                        <li class="py-2 flex justify-between items-center">
                            <a href="{{ route('emails.show', $mail->getUid()) }}?folder={{ $folder }}"
                               class="block hover:bg-gray-100 dark:hover:bg-gray-800 p-2 rounded flex-1">
                                <p class="{{ $isRead ? 'font-normal' : 'font-bold' }}">
                                    {{ decodeMimeHeader($mail->getSubject()) }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ isset($mail->getFrom()[0]) ? $mail->getFrom()[0]->mail : 'N/D' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse((string)$mail->getDate())->format('d/m/Y H:i') }}
                                </p>
                            </a>
                            <!-- Botón para borrar el correo -->
                            <form action="{{ route('emails.delete', $mail->getUid()) }}" method="POST"
                                  onsubmit="return confirm('{{ __('¿Está seguro de borrar este correo?') }}');"
                                  class="ml-2">
                                @csrf
                                <input type="hidden" name="folder" value="{{ $folder }}">
                                <button type="submit"
                                        class="p-2 rounded-full text-red-500 hover:text-red-700"
                                        title="{{ __('Borrar') }}">
                                    <iconify-icon icon="heroicons-outline:trash" class="text-xl"></iconify-icon>
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">{{ __("No se encontraron correos.") }}</li>
                    @endforelse
                </ul>

                <!-- Paginación -->
                <div class="mt-4">
                    {{ $messages->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Detalle del correo -->
        <div class="card bg-white dark:bg-gray-900 shadow p-4">
            @if(isset($message))
                <h2 class="text-2xl font-bold mb-4">{{ decodeMimeHeader($message->getSubject()) }}</h2>
                <p class="text-sm text-gray-600 mb-2">
                    <strong>{{ __("De:") }}</strong> {{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : 'N/D' }}
                </p>
                <p class="text-xs text-gray-500 mb-4">
                    {{ \Carbon\Carbon::parse((string)$message->getDate())->format('d/m/Y H:i') }}
                </p>
                <div class="prose max-w-none mb-4 dark:prose-dark">
                    {!! $message->getHTMLBody() ?: $message->getTextBody() !!}
                </div>

                <!-- Adjuntos (si existen) -->
                @if(count($message->getAttachments()) > 0)
                    <div class="mb-4">
                        <h3 class="font-semibold">{{ __("Archivos adjuntos:") }}</h3>
                        <ul class="list-disc list-inside">
                            @foreach($message->getAttachments() as $index => $attachment)
                                <li>
                                    <a href="{{ route('emails.attachment.download', ['messageUid' => $message->getUid(), 'attachmentIndex' => $index]) }}"
                                       class="text-blue-500 hover:underline" target="_blank"
                                       title="{{ __('Descargar adjunto') }}">
                                        <iconify-icon icon="heroicons-outline:paper-clip" class="text-lg mr-1"></iconify-icon>
                                        {{ $attachment->getName() }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Área para responder -->
                <div class="mt-6">
                    <form action="{{ route('emails.reply', $message->getUid()) }}" method="POST">
                        @csrf
                        <input type="hidden" name="to" value="{{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : '' }}">
                        <input type="hidden" name="subject" value="Re: {{ decodeMimeHeader($message->getSubject()) }}">
                        <label for="reply" class="font-semibold mb-2 block">{{ __("Responder (HTML permitido):") }}</label>
                        <textarea id="reply" name="content" class="w-full border rounded p-2" rows="6" placeholder="{{ __('Escribe tu respuesta...') }}"></textarea>
                        <button type="submit"
                                class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center"
                                title="{{ __('Enviar respuesta') }}">
                            <iconify-icon icon="heroicons-outline:paper-airplane" class="text-xl mr-2"></iconify-icon>
                            {{ __("Enviar Respuesta") }}
                        </button>
                    </form>
                </div>
            @else
                <p class="text-gray-500">{{ __("Selecciona un correo para ver su contenido.") }}</p>
            @endif
        </div>
    </div>

    <!-- Modal para editar configuración IMAP -->
    <div id="modal-imap-settings" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded p-6 w-11/12 md:w-1/2">
            <h3 class="text-xl font-bold mb-4">{{ __("Editar configuración IMAP") }}</h3>
            <form action="{{ route('emails.settings.update') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="imap_host" class="block font-medium">{{ __("Host") }}</label>
                    <input type="text" name="imap_host" id="imap_host" class="w-full border rounded p-2"
                           value="{{ auth()->user()->imap_host }}" required>
                </div>
                <div class="mb-4">
                    <label for="imap_port" class="block font-medium">{{ __("Port") }}</label>
                    <input type="number" name="imap_port" id="imap_port" class="w-full border rounded p-2"
                           value="{{ auth()->user()->imap_port }}" required>
                </div>
                <div class="mb-4">
                    <label for="imap_encryption" class="block font-medium">{{ __("Encryption") }}</label>
                    <input type="text" name="imap_encryption" id="imap_encryption" class="w-full border rounded p-2"
                           value="{{ auth()->user()->imap_encryption }}">
                </div>
                <div class="mb-4">
                    <label for="imap_username" class="block font-medium">{{ __("Username") }}</label>
                    <input type="text" name="imap_username" id="imap_username" class="w-full border rounded p-2"
                           value="{{ auth()->user()->imap_username }}" required>
                </div>
                <div class="mb-4">
                    <label for="imap_password" class="block font-medium">{{ __("Password") }}</label>
                    <input type="password" name="imap_password" id="imap_password" class="w-full border rounded p-2"
                           value="{{ auth()->user()->imap_password }}" required>
                </div>
                <div class="flex justify-end">
                    <button type="button"
                            class="mr-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded flex items-center"
                            onclick="document.getElementById('modal-imap-settings').classList.add('hidden')"
                            title="{{ __('Cancelar') }}">
                        <iconify-icon icon="heroicons-outline:x" class="text-xl mr-1"></iconify-icon>
                        {{ __("Cancelar") }}
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded flex items-center"
                            title="{{ __('Guardar') }}">
                        <iconify-icon icon="heroicons-outline:check" class="text-xl mr-1"></iconify-icon>
                        {{ __("Guardar") }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar configuración SMTP -->
    <div id="modal-smtp-settings" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded p-6 w-11/12 md:w-1/2">
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
                    <button type="button"
                            class="mr-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded flex items-center"
                            onclick="document.getElementById('modal-smtp-settings').classList.add('hidden')"
                            title="{{ __('Cancelar') }}">
                        <iconify-icon icon="heroicons-outline:x" class="text-xl mr-1"></iconify-icon>
                        {{ __("Cancelar") }}
                    </button>
                    <button type="submit"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center"
                            title="{{ __('Guardar') }}">
                        <iconify-icon icon="heroicons-outline:check" class="text-xl mr-1"></iconify-icon>
                        {{ __("Guardar") }}
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                    const container = document.getElementById('email-list-container');
                    if (container) {
                        container.innerHTML = html;
                    } else {
                        console.error('Contenedor no encontrado');
                    }
                })
                .catch(error => console.error('Error al actualizar los emails:', error));
            }
            // Actualiza la lista de correos cada 3 minutos
            setInterval(updateEmailList, 180000);
        </script>
    @endpush
</x-app-layout>
