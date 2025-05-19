<x-app-layout>
    {{-- Sección HEAD: meta tags --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Iconify --}}
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    @endpush

    @php
        // Función para convertir CSV a imagen (base64) - Mantenida por si acaso
        if (! function_exists('convertCsvImage')) {
            function convertCsvImage($imageString) {
                $parts = explode(',', $imageString, 2);
                if (!isset($parts[1])) { return $imageString; }
                $dataAfterComma = trim($parts[1]);
                if (ctype_digit(str_replace([',', ' '], '', $dataAfterComma))) {
                    $numbers = explode(',', $dataAfterComma);
                    $binaryData = '';
                    foreach ($numbers as $num) { $binaryData .= chr((int)$num); }
                    return $parts[0] . ',' . base64_encode($binaryData);
                }
                return $imageString;
            }
        }
        // Función para formatear texto del mensaje y convertir URLs en enlaces
        if (! function_exists('formatMessageText')) {
            function formatMessageText($text) {
                if (empty($text)) return '';
                // 1. Escapar HTML para seguridad
                $escapedText = e($text);

                // 2. Convertir URLs a enlaces
                $pattern = '/(https?:\/\/[^\s<>"\'`]+)|(www\.[^\s<>"\'`]+)/i';
                $linkedText = preg_replace_callback($pattern, function ($matches) {
                    $url = $matches[0];
                    $href = $url;
                    if (stripos($href, 'www.') === 0) {
                        $href = 'https://' . $href;
                    }
                    $displayUrl = strlen($url) > 50 ? substr($url, 0, 47) . '...' : $url;
                    return '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer" class="message-link">' . e($displayUrl) . '</a>';
                }, $escapedText);

                // 3. Convertir saltos de línea a <br>
                return nl2br($linkedText, false);
            }
        }
    @endphp

    {{-- Mensajes flash (con z-index aumentado) --}}
    @if(session('success'))
        <div class="fixed top-5 right-5 z-[9999] max-w-sm p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-lg dark:bg-green-800 dark:text-green-100 dark:border-green-700" role="alert" id="flash-success">
            <div class="flex items-start">
                <iconify-icon icon="mdi:check-circle" class="text-xl mr-2 text-green-500 dark:text-green-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Success!") }}</strong>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-100 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8 dark:bg-green-800 dark:text-green-300 dark:hover:bg-green-700" onclick="this.parentElement.parentElement.style.display='none'" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif
    @if(session('error'))
         <div class="fixed top-5 right-5 z-[9999] max-w-sm p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-lg dark:bg-red-800 dark:text-red-100 dark:border-red-700" role="alert" id="flash-error">
            <div class="flex items-start">
                <iconify-icon icon="mdi:alert-circle" class="text-xl mr-2 text-red-500 dark:text-red-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Error!") }}</strong>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
                 <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700" onclick="this.parentElement.parentElement.style.display='none'" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif
     @if($errors->any())
        <div class="fixed top-5 right-5 z-[9999] max-w-sm p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-lg dark:bg-red-800 dark:text-red-100 dark:border-red-700" role="alert" id="flash-validation-errors">
            <div class="flex items-start">
                <iconify-icon icon="mdi:alert-circle" class="text-xl mr-2 text-red-500 dark:text-red-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Validation Errors!") }}</strong>
                    <ul class="list-disc list-inside text-sm mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                 <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700" onclick="this.parentElement.parentElement.style.display='none'" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif


    {{-- Contenedor principal del chat --}}
    <div class="flex chat-height overflow-hidden relative bg-white dark:bg-slate-900 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700">

        {{-- Panel Izquierdo: Contactos --}}
        <div class="w-[320px] flex-none border-r border-slate-200 dark:border-slate-700 flex flex-col h-full bg-slate-50 dark:bg-slate-800">
            {{-- Cabecera del Panel Izquierdo: Estado y Acciones --}}
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                 <div id="connection-status-header" class="flex items-center justify-between gap-2">
                     <div id="connection-btn" class="flex-1">
                         {{-- Estado de conexión (se carga con JS) --}}
                         <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('Loading status...') }}</span>
                     </div>
                     <div id="connection-actions" class="flex items-center gap-2">
                         {{-- Botones se cargan con JS --}}
                     </div>
                 </div>
            </div>

            {{-- Barra de Búsqueda de Contactos --}}
            <div class="p-3 border-b border-slate-200 dark:border-slate-700">
                <div class="relative">
                    <input type="text" id="contactSearch" placeholder="{{ __('Search or start new chat') }}" class="w-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
                        <iconify-icon icon="heroicons:magnifying-glass-20-solid"></iconify-icon>
                    </div>
                </div>
            </div>

            {{-- Lista de Contactos --}}
            <div id="contacts-panel" class="flex-1 overflow-y-auto contact-height">
                <ul id="contact-list" class="divide-y divide-slate-100 dark:divide-slate-700">
                    {{-- Renderizado con Blade inicial, se actualizará con AJAX --}}
                    @forelse($sortedContacts as $contact)
                        @php
                            $cleanName = preg_replace('/@.*$/', '', $contact['name'] ?? $contact['phone']);
                            $contactInitial = strtoupper(substr($cleanName, 0, 1));
                            $contactPhone = $contact['phone'];
                            $isActive = isset($selectedPhone) && $selectedPhone === $contactPhone;
                        @endphp
                        <li class="contact-item flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-700 transition duration-150 {{ $isActive ? 'active' : '' }}" data-phone="{{ $contactPhone }}">
                            <a href="{{ route('whatsapp-business.conversation', $contactPhone) }}" class="flex items-center p-3 w-full">
                                <div class="flex-none mr-3">
                                    <div class="contact-avatar-initials w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-300 font-medium">
                                        {{ $contactInitial }}
                                    </div>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="contact-item-name text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ $cleanName }}</p>
                                    <p class="contact-item-detail text-xs text-slate-500 dark:text-slate-400 truncate">{{ $contactPhone }}</p>
                                </div>
                                {{-- <span class="contact-item-time text-xs text-slate-400 dark:text-slate-500 ml-2 whitespace-nowrap">Hora</span> --}}
                            </a>
                            <form action="{{ route('whatsapp-business.chat.destroy', $contactPhone) }}" method="POST" class="mr-2 flex-none delete-chat-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="delete-chat-btn text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 p-1 rounded-full"
                                        data-contact-name="{{ $cleanName }}"
                                        title="{{ __('Delete chat') }}">
                                    <iconify-icon icon="mdi:trash-can-outline" class="text-lg"></iconify-icon>
                                </button>
                            </form>
                        </li>
                    @empty
                         <li class="p-4 text-center text-slate-500 dark:text-slate-400 text-sm">{{ __('No contacts found.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Panel Derecho: Chat --}}
        <div class="flex-1 flex flex-col h-full bg-slate-100 dark:bg-slate-950 chat-bg-pattern">

            @if(isset($selectedPhone))
                @php
                    // Encontrar el contacto seleccionado para mostrar nombre e inicial
                    $contactSelected = collect($sortedContacts)->firstWhere('phone', $selectedPhone);
                    $selectedName = $contactSelected ? preg_replace('/@.*$/', '', $contactSelected['name']) : $selectedPhone;
                    $selectedInitial = strtoupper(substr($selectedName, 0, 1));
                    $selectedJid = $selectedPhone . '@s.whatsapp.net'; // JID completo para enviar mensajes
                @endphp

                {{-- Cabecera del Chat --}}
                <div id="chat-header" class="flex items-center p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 h-[65px] flex-none">
                    <div class="flex items-center flex-1 overflow-hidden">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-300 font-medium mr-3 flex-none chat-header-avatar">
                            {{ $selectedInitial }}
                        </div>
                        <div class="overflow-hidden">
                            <h2 id="chat-name" class="text-base font-medium text-slate-800 dark:text-slate-100 truncate">{{ $selectedName }}</h2>
                            <p id="chat-status" class="text-xs text-slate-500 dark:text-slate-400 truncate">{{-- Online status? --}}</p>
                        </div>
                    </div>
                    {{-- Botones de Acción del Header (Opcional) --}}
                    <div class="flex items-center gap-2 ml-auto">
                         {{-- <button class="btn btn-icon btn-light dark:btn-dark rounded-full w-9 h-9 p-0"><iconify-icon icon="heroicons:magnifying-glass-20-solid"></iconify-icon></button>
                         <button class="btn btn-icon btn-light dark:btn-dark rounded-full w-9 h-9 p-0"><iconify-icon icon="heroicons:ellipsis-vertical-20-solid"></iconify-icon></button> --}}
                    </div>
                </div>

                {{-- Contenedor de Mensajes --}}
                <div id="chat-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-2">
                    {{-- Renderizado con Blade inicial --}}
                    @forelse($messages as $messageItem) {{-- Iterar sobre $messageItem --}}
                        @php
                            // Acceder a los datos del mensaje original
                            $messageData = $messageItem['messageData'] ?? null;
                            // Obtener la URL pública del medio, si existe
                            $publicMediaUrl = $messageItem['publicMediaUrl'] ?? null;

                            // Si no hay datos del mensaje, saltar esta iteración
                            if (!$messageData) continue;

                            // Determinar si el mensaje es propio o recibido
                            $isFromMe = $messageData['key']['fromMe'] ?? false;
                            $messageClass = $isFromMe ? 'message-sent' : 'message-received'; // Clases CSS para estilo

                            // Extraer el contenido del mensaje
                            $conversationText = $messageData['message']['conversation'] ?? null;
                            $extendedText = $messageData['message']['extendedTextMessage']['text'] ?? null;
                            $imageMessage = $messageData['message']['imageMessage'] ?? null;
                            $videoMessage = $messageData['message']['videoMessage'] ?? null;
                            $audioMessage = $messageData['message']['audioMessage'] ?? null;
                            $documentMessage = $messageData['message']['documentMessage'] ?? null;
                            $stickerMessage = $messageData['message']['stickerMessage'] ?? null;

                            // Obtener timestamp y formatear hora
                            $timestamp = isset($messageData['messageTimestamp']) ? \Carbon\Carbon::createFromTimestamp($messageData['messageTimestamp']) : null;
                            $formattedTime = $timestamp ? $timestamp->format('H:i') : '';

                            $messageId = $messageData['key']['id'] ?? null;
                            $remoteJid = $messageData['key']['remoteJid'] ?? '';

                            // Determinar estado del mensaje
                            $messageStatus = $messageData['status'] ?? 1;
                            $statusIcon = 'mdi:check';
                            if ($messageStatus >= 2) $statusIcon = 'mdi:check-all';
                            $statusClass = $messageStatus >= 5 ? 'status-read' : ($messageStatus >= 2 ? 'status-delivered' : ''); // Asume 5=read, 2=delivered
                        @endphp

                        {{-- Contenedor principal del mensaje --}}
                        <div class="flex w-full {{ $isFromMe ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $messageId }}">
                            <div class="relative message-bubble-wrapper {{ $isFromMe ? 'message-sent ml-auto' : 'message-received mr-auto' }}">
                                <div class="message-bubble group">
                                    @php $mediaRendered = false; @endphp

                                    {{-- Mostrar Imagen --}}
                                    @if ($imageMessage && $publicMediaUrl)
                                        <img src="{{ $publicMediaUrl }}" alt="Imagen adjunta" class="chat-image max-w-full h-auto rounded-lg cursor-pointer block mb-1"
                                             onclick="showZoomModal('{{ $publicMediaUrl }}')">
                                        @php $mediaRendered = true; @endphp

                                    {{-- Mostrar Video --}}
                                    @elseif ($videoMessage && $publicMediaUrl)
                                        <video controls class="chat-video max-w-full h-auto rounded-lg block mb-1" style="max-width: 320px;">
                                            <source src="{{ $publicMediaUrl }}" type="{{ $videoMessage['mimetype'] ?? 'video/mp4' }}">
                                            {{ __('Your browser does not support the video tag.') }} <a href="{{ $publicMediaUrl }}">{{ __('Download video') }}</a>
                                        </video>
                                        @php $mediaRendered = true; @endphp

                                    {{-- Mostrar Audio --}}
                                    @elseif ($audioMessage && $publicMediaUrl)
                                         <audio controls class="chat-audio block mb-1 w-full" style="max-width: 280px;">
                                            <source src="{{ $publicMediaUrl }}" type="{{ $audioMessage['mimetype'] ?? 'audio/ogg' }}">
                                            {{ __('Your browser does not support the audio tag.') }} <a href="{{ $publicMediaUrl }}">{{ __('Download audio') }}</a>
                                        </audio>
                                        @php $mediaRendered = true; @endphp

                                    {{-- Mostrar Sticker (como imagen si es webp) --}}
                                    @elseif ($stickerMessage && $publicMediaUrl && ($stickerMessage['mimetype'] ?? '') === 'image/webp')
                                         <img src="{{ $publicMediaUrl }}" alt="Sticker" class="chat-sticker max-w-[150px] h-auto block mb-1">
                                         @php $mediaRendered = true; @endphp

                                    {{-- Mostrar Enlace a Documento --}}
                                    @elseif ($documentMessage && $publicMediaUrl)
                                        <div class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50 mb-1">
                                            <a href="{{ $publicMediaUrl }}" target="_blank" download="{{ $documentMessage['fileName'] ?? 'documento' }}" class="flex items-center text-sm text-slate-700 dark:text-slate-200 hover:underline">
                                                <iconify-icon icon="mdi:file-document-outline" class="text-lg mr-2 flex-none"></iconify-icon>
                                                <span class="truncate">{{ $documentMessage['fileName'] ?? __('Download Document') }}</span>
                                            </a>
                                            @php
                                                $fileSize = $documentMessage['fileLength'] ?? 0;
                                                $formattedSize = $fileSize > 0 ? round($fileSize / 1024) . ' KB' : '';
                                            @endphp
                                            @if($formattedSize)
                                                <span class="text-xs text-slate-500 dark:text-slate-400 block mt-1">{{ $formattedSize }}</span>
                                            @endif
                                        </div>
                                        @php $mediaRendered = true; @endphp
                                    @endif

                                    {{-- Texto del Mensaje (incluyendo caption de media) --}}
                                    @php
                                        $textContent = $conversationText
                                                       ?? $extendedText
                                                       ?? $imageMessage['caption']
                                                       ?? $videoMessage['caption']
                                                       ?? $documentMessage['caption']
                                                       ?? '';
                                    @endphp
                                    @if($textContent)
                                        <div class="message-text-content {{ $mediaRendered ? 'mt-1' : '' }}">
                                            {!! formatMessageText($textContent) !!}
                                        </div>
                                    @elseif(!$mediaRendered)
                                        {{-- Mostrar tipo de mensaje si no hay contenido visible --}}
                                        <p class="text-xs italic text-slate-400 dark:text-slate-500">
                                            [{{ $messageData['message'] ? array_keys($messageData['message'])[0] : 'Mensaje vacío' }}]
                                        </p>
                                    @endif

                                    {{-- Timestamp y Estado --}}
                                    <div class="message-timestamp-wrapper">
                                        <span class="message-timestamp">{{ $formattedTime }}</span>
                                        @if($isFromMe)
                                            <span class="message-status-icon {{ $statusClass }}"><iconify-icon icon="{{ $statusIcon }}"></iconify-icon></span>
                                        @endif
                                    </div>

                                    {{-- Botón Eliminar --}}
                                    @if($messageId)
                                        <button type="button"
                                                class="absolute top-0 right-0 m-1 delete-message-btn text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 p-0.5 rounded-full bg-white/50 dark:bg-slate-800/50 opacity-0 group-hover:opacity-100 transition-opacity"
                                                data-message-id="{{ $messageId }}"
                                                data-remote-jid="{{ $remoteJid }}"
                                                data-from-me="{{ $isFromMe ? 'true' : 'false' }}"
                                                title="{{ __('Delete message') }}">
                                            <iconify-icon icon="mdi:trash-can-outline" class="text-xs"></iconify-icon>
                                        </button>
                                    @endif

                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-slate-500 dark:text-slate-400 py-10">{{ __("No messages found for this contact.") }}</p>
                    @endforelse
                </div>

                {{-- Área de Envío de Mensaje --}}
                <div id="send-message-container" class="p-3 border-t border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex-none">
                    <div class="flex items-end space-x-3 relative">
                        {{-- Botón Emoji --}}
                        <button id="emoji-button" type="button" class="btn btn-icon btn-light dark:btn-dark rounded-full flex-none w-10 h-10 p-0" title="{{ __('Emoji') }}">
                            <iconify-icon icon="mdi:emoticon-happy-outline" class="text-xl"></iconify-icon>
                        </button>

                        {{-- Input de Texto --}}
                        <div class="flex-1">
                            <textarea id="message-input"
                                      placeholder="{{ __('Type a message') }}..."
                                      class="w-full p-2.5 pr-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-slate-200 focus:ring-emerald-500 focus:border-emerald-500 resize-none overflow-hidden"
                                      rows="1"
                                      style="min-height: 44px; max-height: 120px;"
                                      oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"
                            ></textarea>
                        </div>

                        {{-- Botón de Enviar --}}
                        <button id="sendMessageButton" class="btn btn-primary rounded-full flex-none inline-flex items-center justify-center w-12 h-12 p-0" title="{{ __('Send') }}">
                            <iconify-icon icon="mdi:send" class="text-xl"></iconify-icon>
                        </button>
                    </div>
                </div>

            @else
                {{-- Placeholder si no hay chat seleccionado --}}
                <div class="flex flex-col items-center justify-center h-full text-center bg-slate-100 dark:bg-slate-950 chat-bg-pattern">
                    <iconify-icon icon="logos:whatsapp-icon" class="text-8xl mb-6 opacity-80"></iconify-icon>
                    <h3 class="text-xl font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Keep your phone connected') }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
                        {{ __("Select a contact from the list to start a conversation.") }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <style>
            .chat-height { height: calc(100vh - 100px); min-height: 550px; }
            .contact-height { /* No max-height, usa flex-1 */ }
            .chat-bg-pattern { background-color: #e2e8f0; /* bg-slate-200 */ }
            .dark .chat-bg-pattern { background-color: #0f172a; /* dark:bg-slate-900 */ }
            #contact-list .contact-item.active { background-color: #e2e8f0; /* bg-slate-200 */ }
            .dark #contact-list .contact-item.active { background-color: #1e293b; /* bg-slate-800 */ }
            #contact-list .contact-item.active .contact-item-name { font-weight: 600; }
            .dark #contact-list .contact-item.active .contact-item-name { color: #f1f5f9; }
            .message-bubble-wrapper { max-width: 70%; }
            .message-bubble { padding: 6px 12px; border-radius: 8px; word-wrap: break-word; overflow-wrap: break-word; position: relative; box-shadow: 0 1px 0.5px rgba(11, 20, 26, 0.13); min-width: 60px; @apply group; }
            .message-bubble::before { content: ""; position: absolute; bottom: 0px; height: 12px; width: 12px; background-color: inherit; clip-path: path('M0,0 L12,0 L12,12 L0,12 Z'); }
            .message-received .message-bubble::before { left: -6px; clip-path: path('M6,12 L12,0 L12,12 L6,12 Z'); }
            .message-sent .message-bubble::before { right: -6px; clip-path: path('M0,0 L6,12 L0,12 L0,0 Z'); }
            .message-received .message-bubble { background-color: #ffffff; color: #111b21; }
            .dark .message-received .message-bubble { background-color: #202c33; color: #e9edef; }
            .message-sent .message-bubble { background-color: #dcf8c6; color: #111b21; }
            .dark .message-sent .message-bubble { background-color: #005c4b; color: #e9edef; }
            .message-timestamp-wrapper { float: right; margin-left: 10px; margin-top: 4px; line-height: 1; white-space: nowrap; user-select: none; position: relative; bottom: -2px; clear: right; }
            .message-timestamp { font-size: 0.68rem; color: #667781; }
            .dark .message-timestamp { color: #a0aec0; }
            .message-status-icon { display: inline-block; margin-left: 3px; font-size: 0.8rem; color: #667781; vertical-align: middle; }
            .dark .message-status-icon { color: #a0aec0; }
            .message-status-icon.status-delivered iconify-icon { color: #667781 !important; } /* Delivered gray */
            .dark .message-status-icon.status-delivered iconify-icon { color: #a0aec0 !important; }
            .message-status-icon.status-read iconify-icon { color: #53bdeb !important; /* WhatsApp blue tick color */ }
            .message-bubble img.chat-image, .message-bubble video.chat-video, .message-bubble img.chat-sticker, .message-bubble audio.chat-audio { max-width: 320px; max-height: 320px; border-radius: 6px; display: block; margin-bottom: 2px; }
            .message-bubble img.chat-sticker { max-width: 150px; }
            .message-bubble audio.chat-audio { max-width: 280px; }
            .message-text-content { word-break: break-word; overflow-wrap: break-word; overflow: hidden; padding-right: 55px; min-height: 1.2em; display: block; margin-bottom: 15px; }
            .message-bubble:not(:has(img, video, audio, .p-2.border)) .message-text-content:has(+ .message-timestamp-wrapper) { margin-bottom: 0; } /* No margin if only text+timestamp */
            .message-bubble:has(> img + .message-timestamp-wrapper) img,
            .message-bubble:has(> video + .message-timestamp-wrapper) video,
            .message-bubble:has(> audio + .message-timestamp-wrapper) audio,
            .message-bubble:has(> .p-2.border + .message-timestamp-wrapper) .p-2.border { margin-bottom: 15px; }
            .message-bubble:has(img, video, audio, .p-2.border) .message-text-content { padding-right: 0; } /* No padding if media present */
            .message-link { color: #00a884; text-decoration: underline; cursor: pointer; }
            .dark .message-link { color: #00a884; }
            .message-link:hover { text-decoration: none; }
            #message-input { transition: height 0.2s ease-in-out; }
            /* Swal Emoji Popup */
            .swal2-popup.emoji-popup { width: 370px !important; padding: 0.5rem !important; background: #f0f2f5; }
            .dark .swal2-popup.emoji-popup { background: #111b21; }
            .emoji-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(30px, 1fr)); gap: 8px; max-height: 300px; overflow-y: auto; padding: 0.5rem; }
            .emoji-grid span { font-size: 1.5rem; cursor: pointer; text-align: center; border-radius: 4px; padding: 2px; }
            .emoji-grid span:hover { background-color: rgba(0, 0, 0, 0.1); }
            .dark .emoji-grid span:hover { background-color: rgba(255, 255, 255, 0.1); }
            /* Connection Status Buttons */
            #connection-status-header .btn { @apply w-auto px-3 py-1.5 h-auto text-sm; }
            #connection-status-header .btn.rounded-full { @apply w-9 h-9 p-0; }
            /* Delete Buttons */
            .delete-chat-btn, .delete-message-btn { opacity: 0.6; transition: opacity 0.2s ease; }
            .delete-chat-btn:hover, .delete-message-btn:hover { opacity: 1; }
            /* Swal Auto Response Inputs */
            .swal-autoresponse-label { display: block; text-align: left; margin-bottom: 0.25rem; font-weight: 500; color: #374151; }
            .dark .swal-autoresponse-label { color: #d1d5db; }
            .swal-autoresponse-select, .swal-autoresponse-textarea { display: block !important; width: 100% !important; max-width: 100% !important; padding: 0.5rem 0.75rem !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #ffffff !important; color: #1f2937 !important; box-sizing: border-box !important; font-size: 0.875rem !important; }
            .dark .swal-autoresponse-select, .dark .swal-autoresponse-textarea { background-color: #374151 !important; border-color: #4b5563 !important; color: #f3f4f6 !important; }
            .swal-autoresponse-select:focus, .swal-autoresponse-textarea:focus { outline: none !important; border-color: #2563eb !important; box-shadow: 0 0 0 1px #2563eb !important; }
            .swal-autoresponse-textarea { min-height: 80px; }
            .swal-autoresponse-note { display: block; text-align: left; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
            .dark .swal-autoresponse-note { color: #9ca3af; }
        </style>
    @endpush

    {{-- Scripts --}}
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

            // =======================================================
            // ========== WhatsApp Chat Logic (Adjusted) ==========
            // =======================================================
            var autoResponseConfig = {!! json_encode($autoResponseConfig ?? null) !!};
            const userId = "{{ env('WHATSAPP_ID_SERVER') }}";
            const selectedPhone = "{{ $selectedPhone ?? null }}";
            let activeMessageRequest = null;
            let activeContactRequest = null;
            let messageIntervalId = null;
            let contactIntervalId = null;
            let statusIntervalId = null;
            let isRefreshPaused = false;

            // --- SweetAlert2 Dark Mode & Toast ---
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                    if (document.documentElement.classList.contains('dark')) { toast.classList.add('dark'); }
                },
                customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark swal2-toast-dark' : 'swal2-toast-light' }
            });
            // Observer para cambiar tema de Swal si cambia el tema del HTML
            const swalObserver = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === "class") {
                        const isDark = document.documentElement.classList.contains('dark');
                        document.querySelectorAll('.swal2-popup').forEach(popup => {
                            if (isDark) popup.classList.add('dark'); else popup.classList.remove('dark');
                        });
                         document.querySelectorAll('.swal2-toast').forEach(toast => {
                            if (isDark) toast.classList.add('dark', 'swal2-toast-dark'); else toast.classList.remove('dark', 'swal2-toast-dark');
                        });
                    }
                });
            });
            swalObserver.observe(document.documentElement, { attributes: true });


            // --- Utility Functions ---
            function scrollChatToBottom(force = false) {
                var chatContainer = document.getElementById('chat-container');
                if (chatContainer) {
                    const scrollHeight = chatContainer.scrollHeight;
                    const currentScroll = chatContainer.scrollTop;
                    const containerHeight = chatContainer.clientHeight; // Use clientHeight for visible area
                    const threshold = 100; // Pixels from bottom to trigger auto-scroll

                    // Scroll only if forced or if user was already near the bottom
                    if (force || (currentScroll + containerHeight >= scrollHeight - threshold)) {
                        chatContainer.scrollTop = scrollHeight;
                    }
                 }
            }
            function showZoomModal(url) {
                Swal.fire({
                    imageUrl: url,
                    imageAlt: 'Zoomed Image',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        popup: 'p-0 ' + (document.documentElement.classList.contains('dark') ? 'dark' : ''),
                        image: 'max-w-full max-h-[80vh] object-contain'
                    },
                    backdrop: `rgba(0,0,0,0.7)`
                });
            }
            function showVideoModal(url, mimeType = 'video/mp4') {
                 Swal.fire({
                    html: `<video controls autoplay style="max-width: 100%; max-height: 80vh; border-radius: 8px;"><source src="${url}" type="${mimeType}">Your browser does not support the video tag.</video>`,
                    showConfirmButton: false,
                    showCloseButton: true,
                     customClass: {
                        popup: 'p-4 ' + (document.documentElement.classList.contains('dark') ? 'dark' : ''),
                        htmlContainer: '!p-0' // Remove padding around video
                    },
                    backdrop: `rgba(0,0,0,0.7)`
                 });
            }
            function formatTimestampForList(timestamp) {
                if (!timestamp || timestamp === 0) return '';
                const date = new Date(timestamp * 1000); // Assuming timestamp is in seconds
                const now = new Date();
                const yesterday = new Date(now);
                yesterday.setDate(now.getDate() - 1);

                if (date.toDateString() === now.toDateString()) {
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); // HH:MM
                } else if (date.toDateString() === yesterday.toDateString()) {
                    return '{{ __("Yesterday") }}';
                } else {
                    return date.toLocaleDateString([], { day: '2-digit', month: '2-digit', year: 'numeric' }); // DD/MM/YYYY
                }
            }
             // Helper function to format text and links in JS (similar to Blade helper)
            function formatJsMessageText(text) {
                if (!text) return '';
                // Basic escaping (replace with a more robust library if needed)
                let escapedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");

                // URL detection and linking
                const pattern = /(https?:\/\/[^\s<>"'`]+)|(www\.[^\s<>"'`]+)/gi;
                let linkedText = escapedText.replace(pattern, (match) => {
                    let href = match;
                    if (match.toLowerCase().startsWith('www.')) {
                        href = 'https://' + href;
                    }
                    const displayUrl = match.length > 50 ? match.substring(0, 47) + '...' : match;
                    // Escape href and displayUrl again just in case
                    const safeHref = $('<div>').text(href).html();
                    const safeDisplayUrl = $('<div>').text(displayUrl).html();
                    return `<a href="${safeHref}" target="_blank" rel="noopener noreferrer" class="message-link">${safeDisplayUrl}</a>`;
                });

                // Newline to <br>
                return linkedText.replace(/\n/g, '<br>');
            }


            // --- Auto Refresh Pause/Resume ---
            function pauseAutoRefresh() {
                if (!isRefreshPaused) {
                    console.log("Pausing auto-refresh...");
                    isRefreshPaused = true;
                    if (messageIntervalId) clearInterval(messageIntervalId);
                    if (contactIntervalId) clearInterval(contactIntervalId);
                    if (statusIntervalId) clearInterval(statusIntervalId);
                    messageIntervalId = null; contactIntervalId = null; statusIntervalId = null;
                }
            }
            function resumeAutoRefresh() {
                if (isRefreshPaused) {
                    console.log("Resuming auto-refresh...");
                    isRefreshPaused = false;
                    if (!messageIntervalId && selectedPhone) messageIntervalId = setInterval(refreshChatMessages, 15000); // Only restart if chat selected
                    if (!contactIntervalId) contactIntervalId = setInterval(refreshContactList, 60000);
                    if (!statusIntervalId) statusIntervalId = setInterval(updateConnectionStatus, 45000);
                }
            }
            // Helper function to add pause/resume hooks to Swal options
            function swalOptionsWithPause(options) {
                return {
                    ...options,
                    didOpen: (modal) => {
                        pauseAutoRefresh();
                        if (options.didOpen && typeof options.didOpen === 'function') { options.didOpen(modal); }
                    },
                    willClose: (modal) => {
                        setTimeout(() => { resumeAutoRefresh(); }, 100);
                        if (options.willClose && typeof options.willClose === 'function') { options.willClose(modal); }
                    }
                };
            }


            // --- Connection Status & Actions ---
            function updateConnectionStatus() {
                if (isRefreshPaused) return; // Skip if paused

                fetch('{{ secure_url("/api/whatsapp/check?user_id=") }}' + userId, { method: 'GET' })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Connection Status:", data);
                        const connectionBtnContainer = $('#connection-btn');
                        const actionsContainer = $('#connection-actions');
                        connectionBtnContainer.empty(); actionsContainer.empty();

                        if (data.success) {
                            if (data.connected) {
                                // Connected State
                                connectionBtnContainer.html(`<span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-800/50 px-2 py-0.5 rounded-full">{{ __('Connected') }}</span>`);
                                actionsContainer.html(`
                                    <button id="btnAutoResponse" class="btn btn-icon btn-secondary light rounded-full w-9 h-9 p-0" title="{{ __('Auto Response Settings') }}"><iconify-icon icon="mdi:robot-outline" class="text-lg"></iconify-icon></button>
                                    <button id="sync-contacts-btn" type="button" class="btn btn-icon btn-primary light rounded-full w-9 h-9 p-0" title="{{ __('Import Contacts') }}"><iconify-icon icon="mdi:sync" class="text-lg"></iconify-icon></button>
                                    <button id="btnDisconnect" class="btn btn-icon btn-danger light rounded-full w-9 h-9 p-0" title="{{ __('Disconnect Session') }}"><iconify-icon icon="mdi:logout-variant" class="text-lg"></iconify-icon></button>
                                `);
                                $('#btnDisconnect').on('click', handleDisconnect);
                                $('#btnAutoResponse').on('click', handleAutoResponseEdit);
                                $('#sync-contacts-btn').on('click', handleSyncContacts);
                            } else {
                                // Disconnected State
                                connectionBtnContainer.html(`<button id="btnConnect" class="btn btn-success btn-sm flex items-center gap-1"><iconify-icon icon="mdi:whatsapp" class="text-base"></iconify-icon>{{ __('Connect') }}</button>`);
                                $('#btnConnect').on('click', function() {
                                    Swal.fire(swalOptionsWithPause({ title: '{{ __("Generating QR") }}', text: '{{ __("Please wait...") }}', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }, customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                                    startWhatsAppSession();
                                });
                            }
                        } else {
                             // Error State
                            connectionBtnContainer.html(`<span class="text-xs font-medium text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-800/50 px-2 py-0.5 rounded-full">{{ __('Error') }}</span>`);
                            actionsContainer.html(`<button id="btnReconnect" class="btn btn-icon btn-warning light rounded-full w-9 h-9 p-0" title="{{ __('Retry Connection') }}"><iconify-icon icon="mdi:refresh" class="text-lg"></iconify-icon></button>`);
                            $('#btnReconnect').on('click', updateConnectionStatus);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking connection status:', error);
                         $('#connection-btn').html('<span class="text-red-500 text-xs">{{ __("Status Check Failed") }}</span>');
                    });
            }

            function startWhatsAppSession() { /* ... (same as before, using swalOptionsWithPause) ... */
                 fetch('{{ secure_url("/api/whatsapp/start-session") }}', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }, // Added CSRF
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(apiData => {
                    if (apiData.success && apiData.qr) {
                        Swal.close();
                        Swal.fire(swalOptionsWithPause({
                            title: '{{ __("Scan the QR Code") }}', imageUrl: apiData.qr, imageAlt: 'QR Code',
                            showConfirmButton: true, confirmButtonText: '{{ __("Close") }}',
                            customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                        }));
                        setTimeout(updateConnectionStatus, 5000);
                    } else if (apiData.success && apiData.message === 'Session already started') {
                         Swal.close();
                         Toast.fire({ icon: 'info', title: '{{ __("Session is already starting or connected.") }}' });
                         updateConnectionStatus();
                    } else {
                        console.log("QR not ready, retrying...");
                        setTimeout(startWhatsAppSession, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error starting WhatsApp session:', error);
                     Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Connection Error") }}', text: '{{ __("Could not start session. Please try again.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                });
            }

            function handleDisconnect() { /* ... (same as before, using swalOptionsWithPause) ... */
                Swal.fire(swalOptionsWithPause({
                    title: '{{ __("Disconnect Session?") }}', text: "{{ __('Are you sure you want to log out?') }}", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280', confirmButtonText: '{{ __("Yes, disconnect") }}', cancelButtonText: '{{ __("Cancel") }}',
                    customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                })).then((result) => {
                    if (result.isConfirmed) {
                        const disconnectBtn = $('#btnDisconnect');
                        disconnectBtn.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                        fetch('{{ secure_url("/api/whatsapp/logout?user_id=") }}' + userId, { method: 'GET' }) // Changed to GET if your API expects GET
                            .then(response => response.json())
                            .then(logoutData => {
                                if (logoutData.success) {
                                    Toast.fire({ icon: 'success', title: '{{ __("Disconnected successfully.") }}' });
                                    updateConnectionStatus();
                                    // Redirect to index after successful logout (or just update UI)
                                    window.location.href = "{{ route('whatsapp-business.index') }}";
                                } else {
                                    Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Error") }}', text: logoutData.message || '{{ __("Failed to disconnect.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                                }
                            })
                            .catch(error => {
                                console.error('Error disconnecting:', error);
                                Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Error") }}', text: '{{ __("An error occurred during disconnection.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                            })
                            .finally(() => {
                                disconnectBtn.prop('disabled', false).find('iconify-icon').attr('icon', 'mdi:logout-variant');
                                updateConnectionStatus();
                            });
                    }
                });
            }

            function handleAutoResponseEdit() { /* ... (same as before, using swalOptionsWithPause) ... */
                 $.ajax({
                    url: '/auto-response-whatsapp-get',
                    type: 'GET', dataType: 'json',
                    success: function(response) {
                        if(response.success && response.data) {
                            autoResponseConfig = response.data;
                            showAutoResponseModal();
                        } else {
                            Toast.fire({ icon: 'error', title: '{{ __("Could not load current settings.") }}' });
                            showAutoResponseModal(); // Show with defaults even on error
                        }
                    },
                    error: function() {
                        Toast.fire({ icon: 'error', title: '{{ __("Error fetching settings.") }}' });
                        showAutoResponseModal(); // Show with defaults even on error
                    }
                });
            }

            function showAutoResponseModal() { /* ... (same as before, using swalOptionsWithPause) ... */
                var selectedValue = autoResponseConfig ? autoResponseConfig.whatsapp : '0';
                var promptValue = autoResponseConfig ? autoResponseConfig.whatsapp_prompt : '';

                Swal.fire(swalOptionsWithPause({
                    title: '{{ __("Auto Response Settings") }}',
                    html: `...`, // Same HTML structure as before
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save Changes") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    customClass: { popup: 'w-full max-w-lg ' + (document.documentElement.classList.contains('dark') ? 'dark' : '') },
                    preConfirm: () => { /* ... same validation ... */
                         const responseType = $('#swal-whatsapp-mode').val();
                         const promptText = $('#swal-whatsapp-prompt').val().trim();
                         if (responseType !== '0' && !promptText) {
                             Swal.showValidationMessage('{{ __("The prompt/text field is required for this option.") }}');
                             $('#swal-whatsapp-prompt').addClass('border-red-500').focus(); return false;
                         }
                          $('#swal-whatsapp-prompt').removeClass('border-red-500');
                         return { whatsapp: responseType, whatsapp_prompt: promptText };
                    }
                })).then((result) => {
                    if(result.isConfirmed){
                        const dataToSave = result.value;
                        $.ajax({
                            url: '/auto-response-whatsapp',
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}', whatsapp: dataToSave.whatsapp, whatsapp_prompt: dataToSave.whatsapp_prompt },
                            success: function(response) {
                                if(response.success){
                                    Toast.fire({ icon: 'success', title: response.message || '{{ __("Settings saved!") }}' });
                                    autoResponseConfig = response.data || null;
                                } else {
                                    Toast.fire({ icon: 'error', title: response.message || '{{ __("Could not save settings.") }}' });
                                }
                            },
                            error: function(xhr) {
                                Toast.fire({ icon: 'error', title: '{{ __("An error occurred while saving.") }}' });
                            }
                        });
                    }
                });
            }

            function handleSyncContacts() { /* ... (same as before, using swalOptionsWithPause) ... */
                const syncBtn = $('#sync-contacts-btn');
                const originalIcon = syncBtn.find('iconify-icon').attr('icon');
                syncBtn.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                Toast.fire({ icon: 'info', title: '{{ __("Importing contacts...") }}' });

                $.ajax({
                    url: "{{ route('whatsapp-business.importContacts') }}",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Toast.fire({ icon: 'success', title: response.message || '{{ __("Contacts imported successfully!") }}' });
                            refreshContactList();
                        } else {
                            Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Import Error") }}', text: response.message || '{{ __("Failed to import contacts.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error syncing contacts:', textStatus, errorThrown, jqXHR.responseText);
                        const errorMsg = jqXHR.responseJSON?.message || jqXHR.responseJSON?.error || '{{ __("An error occurred during import.") }}';
                        Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Import Error") }}', text: errorMsg, customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                    },
                    complete: function() {
                        syncBtn.prop('disabled', false).find('iconify-icon').attr('icon', originalIcon);
                    }
                });
            }

            // --- Message & Contact Handling (Using AJAX for Refresh) ---
            function refreshChatMessages() {
                if (isRefreshPaused || !selectedPhone || !document.getElementById('chat-container')) return;

                console.log("Refreshing chat messages via AJAX for:", selectedPhone);
                const chatContainer = $("#chat-container");
                const scrollHeightBefore = chatContainer[0].scrollHeight;
                const currentScroll = chatContainer.scrollTop();
                const containerHeight = chatContainer.innerHeight();
                const threshold = 50;
                const wasNearBottom = currentScroll + containerHeight >= scrollHeightBefore - threshold;

                if (activeMessageRequest) { activeMessageRequest.abort(); }

                const messagesUrl = `/whatsapp-business/messages/json/${selectedPhone}`;

                activeMessageRequest = $.ajax({
                    url: messagesUrl, type: 'GET', dataType: 'json',
                    success: function(response) {
                        if (response.success && response.messages) {
                            let messagesHtml = '';
                            const existingMessageIds = new Set();
                            chatContainer.find('[data-message-id]').each(function() { existingMessageIds.add($(this).data('message-id')); });
                            let newMessagesAdded = false;

                            if (response.messages.length > 0) {
                                // *** NO sorting needed here if Laravel already sorted ***
                                // response.messages.sort((a, b) => (a.messageData?.messageTimestamp || 0) - (b.messageData?.messageTimestamp || 0));

                                response.messages.forEach(messageItem => {
                                    const messageData = messageItem.messageData;
                                    const publicMediaUrl = messageItem.publicMediaUrl;

                                    if (!messageData) return; // Skip if no message data

                                    const messageId = messageData.key?.id ?? null;

                                    // --- Build message HTML only if it doesn't exist ---
                                    if (messageId && !existingMessageIds.has(messageId)) {
                                        newMessagesAdded = true;
                                        const isFromMe = messageData.key?.fromMe ?? false;
                                        const timestamp = messageData.messageTimestamp ?? null;
                                        const formattedTime = timestamp ? new Date(timestamp * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                                        const remoteJid = messageData.key?.remoteJid ?? '';
                                        const messageStatus = messageData.status ?? 1;
                                        let statusIcon = 'mdi:check';
                                        if (messageStatus >= 2) statusIcon = 'mdi:check-all';
                                        const statusClass = messageStatus >= 5 ? 'status-read' : (messageStatus >= 2 ? 'status-delivered' : '');

                                        let mediaHtml = '';
                                        let mediaRendered = false;
                                        let textContent = '';

                                        // Media Rendering Logic (similar to Blade)
                                        const imageMsg = messageData.message?.imageMessage;
                                        const videoMsg = messageData.message?.videoMessage;
                                        const audioMsg = messageData.message?.audioMessage;
                                        const stickerMsg = messageData.message?.stickerMessage;
                                        const docMsg = messageData.message?.documentMessage;

                                        if (imageMsg && publicMediaUrl) {
                                            mediaHtml = `<img src="${publicMediaUrl}" alt="Imagen adjunta" class="chat-image max-w-full h-auto rounded-lg cursor-pointer block mb-1" onclick="showZoomModal('${publicMediaUrl}')">`;
                                            mediaRendered = true;
                                            textContent = imageMsg.caption || '';
                                        } else if (videoMsg && publicMediaUrl) {
                                            mediaHtml = `<video controls class="chat-video max-w-full h-auto rounded-lg block mb-1" style="max-width: 320px;"><source src="${publicMediaUrl}" type="${videoMsg.mimetype || 'video/mp4'}">{{ __('Your browser does not support the video tag.') }} <a href="${publicMediaUrl}">{{ __('Download video') }}</a></video>`;
                                            mediaRendered = true;
                                            textContent = videoMsg.caption || '';
                                        } else if (audioMsg && publicMediaUrl) {
                                            mediaHtml = `<audio controls class="chat-audio block mb-1 w-full" style="max-width: 280px;"><source src="${publicMediaUrl}" type="${audioMsg.mimetype || 'audio/ogg'}">{{ __('Your browser does not support the audio tag.') }} <a href="${publicMediaUrl}">{{ __('Download audio') }}</a></audio>`;
                                            mediaRendered = true;
                                            // Audio caption not typical
                                        } else if (stickerMsg && publicMediaUrl && stickerMsg.mimetype === 'image/webp') {
                                            mediaHtml = `<img src="${publicMediaUrl}" alt="Sticker" class="chat-sticker max-w-[150px] h-auto block mb-1">`;
                                            mediaRendered = true;
                                        } else if (docMsg && publicMediaUrl) {
                                            const fileName = docMsg.fileName || 'documento';
                                            const fileSize = docMsg.fileLength ?? 0;
                                            const formattedSize = fileSize > 0 ? Math.round(fileSize / 1024) + ' KB' : '';
                                            mediaHtml = `<div class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-700/50 mb-1">
                                                            <a href="${publicMediaUrl}" target="_blank" download="${fileName}" class="flex items-center text-sm text-slate-700 dark:text-slate-200 hover:underline">
                                                                <iconify-icon icon="mdi:file-document-outline" class="text-lg mr-2 flex-none"></iconify-icon>
                                                                <span class="truncate">${fileName}</span>
                                                            </a>
                                                            ${formattedSize ? `<span class="text-xs text-slate-500 dark:text-slate-400 block mt-1">${formattedSize}</span>` : ''}
                                                         </div>`;
                                            mediaRendered = true;
                                            textContent = docMsg.caption || '';
                                        }

                                        // Text Content (Conversation or Extended Text)
                                        if (!textContent) { // Only get conversation/extended if no caption was found
                                             textContent = messageData.message?.conversation ?? messageData.message?.extendedTextMessage?.text ?? '';
                                        }
                                        let formattedTextContent = '';
                                        if(textContent) {
                                             formattedTextContent = formatJsMessageText(textContent); // Use JS helper
                                        }

                                        let fallbackContent = '';
                                        if (!mediaRendered && !textContent) {
                                            const messageType = messageData.message ? Object.keys(messageData.message)[0] : 'Mensaje vacío';
                                            fallbackContent = `<p class="text-xs italic text-slate-400 dark:text-slate-500">[${messageType}]</p>`;
                                        }


                                        messagesHtml += `
                                            <div class="flex w-full ${isFromMe ? 'justify-end' : 'justify-start'}" data-message-id="${messageId}">
                                                <div class="relative message-bubble-wrapper ${isFromMe ? 'message-sent ml-auto' : 'message-received mr-auto'}">
                                                    <div class="message-bubble group">
                                                        ${mediaHtml}
                                                        ${formattedTextContent ? `<div class="message-text-content ${mediaRendered ? 'mt-1' : ''}">${formattedTextContent}</div>` : ''}
                                                        ${fallbackContent}
                                                        <div class="message-timestamp-wrapper">
                                                            <span class="message-timestamp">${formattedTime}</span>
                                                            ${!isFromMe ? '' : `<span class="message-status-icon ${statusClass}"><iconify-icon icon="${statusIcon}"></iconify-icon></span>`}
                                                        </div>
                                                        ${messageId ? `
                                                        <button type="button"
                                                                class="absolute top-0 right-0 m-1 delete-message-btn text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 p-0.5 rounded-full bg-white/50 dark:bg-slate-800/50 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                data-message-id="${messageId}"
                                                                data-remote-jid="${remoteJid}"
                                                                data-from-me="${isFromMe ? 'true' : 'false'}"
                                                                title="{{ __('Delete message') }}">
                                                            <iconify-icon icon="mdi:trash-can-outline" class="text-xs"></iconify-icon>
                                                        </button>` : ''}
                                                    </div>
                                                </div>
                                            </div>`;
                                    }
                                    // TODO: Optionally update status of existing messages here
                                });

                                if (newMessagesAdded) {
                                    chatContainer.append(messagesHtml);
                                    if (wasNearBottom) { scrollChatToBottom(true); } // Force scroll if new messages and was at bottom
                                }

                            } else { // If response.messages is empty
                                // Optionally clear chat if needed, but usually better not to
                                // chatContainer.html('<p class="text-center text-slate-500 dark:text-slate-400 py-10">{{ __("No messages found.") }}</p>');
                            }
                        } else {
                             console.error("Error fetching messages via AJAX:", response.message || "Unknown error");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (textStatus !== 'abort') { console.error("AJAX Error fetching messages:", textStatus, errorThrown); }
                    },
                    complete: function() { activeMessageRequest = null; }
                });
            }

            function refreshContactList() {
                 if (isRefreshPaused || !document.getElementById('contacts-panel')) return;
                 if (activeContactRequest) { activeContactRequest.abort(); }

                 console.log("Refreshing contact list via AJAX...");
                 const contactsUrl = "/whatsapp-business/contacts/json";

                 activeContactRequest = $.ajax({
                    url: contactsUrl, type: 'GET', dataType: 'json',
                    success: function(response) {
                        if (response.success && response.contacts) {
                            const contactList = $('#contact-list');
                            let newHtml = '';

                            if (response.contacts.length > 0) {
                                // *** SORT BY LAST MESSAGE TIMESTAMP (DESCENDING) ***
                                response.contacts.sort((a, b) => (b.last_message_timestamp || 0) - (a.last_message_timestamp || 0));

                                response.contacts.forEach(contact => {
                                    const cleanName = (contact.name || contact.phone).replace(/@.*$/, '');
                                    const contactInitial = cleanName.substring(0, 1).toUpperCase();
                                    const contactPhone = contact.phone;
                                    const isActive = selectedPhone === contactPhone;
                                    const conversationUrl = "{{ route('whatsapp-business.conversation', ':phone') }}".replace(':phone', contactPhone);
                                    const deleteUrl = "{{ route('whatsapp-business.chat.destroy', ':phone') }}".replace(':phone', contactPhone);
                                    // const formattedTime = formatTimestampForList(contact.last_message_timestamp); // Optional

                                    newHtml += `
                                        <li class="contact-item flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-700 transition duration-150 ${isActive ? 'active' : ''}" data-phone="${contactPhone}">
                                            <a href="${conversationUrl}" class="flex items-center p-3 w-full">
                                                <div class="flex-none mr-3">
                                                    <div class="contact-avatar-initials w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-300 font-medium">
                                                        ${contactInitial}
                                                    </div>
                                                </div>
                                                <div class="flex-1 overflow-hidden">
                                                    <p class="contact-item-name text-sm font-medium text-slate-800 dark:text-slate-200 truncate">${cleanName}</p>
                                                    <p class="contact-item-detail text-xs text-slate-500 dark:text-slate-400 truncate">${contactPhone}</p>
                                                </div>
                                                {{-- <span class="contact-item-time text-xs text-slate-400 dark:text-slate-500 ml-2 whitespace-nowrap">${formattedTime}</span> --}}
                                            </a>
                                            <form action="${deleteUrl}" method="POST" class="mr-2 flex-none delete-chat-form">
                                                <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="button" class="delete-chat-btn text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 p-1 rounded-full"
                                                        data-contact-name="${cleanName}" title="{{ __('Delete chat') }}">
                                                    <iconify-icon icon="mdi:trash-can-outline" class="text-lg"></iconify-icon>
                                                </button>
                                            </form>
                                        </li>`;
                                });
                            } else {
                                newHtml = '<li class="p-4 text-center text-slate-500 dark:text-slate-400 text-sm">{{ __("No contacts found.") }}</li>';
                            }

                            // Compare before replacing to avoid flicker
                            if (contactList.html() !== newHtml) {
                                console.log("Contact list changed, updating DOM.");
                                contactList.html(newHtml);
                                applyContactSearchFilter();
                            } else {
                                 console.log("Contact list unchanged, skipping DOM update.");
                            }
                        } else {
                             console.error("Error fetching contacts via AJAX:", response.message || "Unknown error");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (textStatus !== 'abort') { console.error("AJAX Error fetching contacts:", textStatus, errorThrown); }
                    },
                    complete: function() { activeContactRequest = null; }
                });
            }


            function applyContactSearchFilter() { /* ... (same as before) ... */
                 const searchInput = document.getElementById('contactSearch');
                 if (!searchInput) return;
                 const filter = searchInput.value.toLowerCase().trim();
                 const contactListItems = document.querySelectorAll('#contact-list li.contact-item');
                 contactListItems.forEach(function(item) {
                     const nameElement = item.querySelector('.contact-item-name');
                     const detailElement = item.querySelector('.contact-item-detail');
                     const name = nameElement ? nameElement.textContent.toLowerCase() : '';
                     const detail = detailElement ? detailElement.textContent.toLowerCase() : '';
                     item.style.display = (name.includes(filter) || detail.includes(filter)) ? '' : 'none';
                 });
            }


            // --- Event Listeners ---
            $(document).ready(function() {
                console.log("WhatsApp Chat Document Ready");
                updateConnectionStatus(); // Initial status check
                scrollChatToBottom(true); // Scroll on initial load (force)

                // Contact Search Filter
                const searchInput = document.getElementById('contactSearch');
                if (searchInput) { searchInput.addEventListener('input', applyContactSearchFilter); }

                // Send Message Button
                $('#sendMessageButton').on('click', function(event) { /* ... (same as before) ... */
                    event.preventDefault();
                    if (!selectedPhone) { Toast.fire({ icon: 'warning', title: '{{ __("No chat selected") }}' }); return; }
                    var messageText = $('#message-input').val().trim();
                    if (!messageText) { Toast.fire({ icon: 'warning', title: '{{ __("Message cannot be empty") }}' }); return; }

                    const sendButton = $(this);
                    const originalIcon = sendButton.find('iconify-icon').attr('icon');
                    sendButton.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');

                    fetch('{{ secure_url("/api/whatsapp/send-message-now") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            token: "{{ env('WHATSAPP_API_TOKEN') }}", sessionId: userId,
                            jid: selectedPhone + '@s.whatsapp.net', message: messageText
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $('#message-input').val('').css('height', 'auto');
                            refreshChatMessages(); // Refresh immediately after sending
                        } else {
                            Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Send Error") }}', text: data.message || '{{ __("Failed to send message.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                        }
                    })
                    .catch(error => {
                        console.error('Error sending message:', error);
                        Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Send Error") }}', text: '{{ __("An error occurred.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                    })
                    .finally(() => {
                        sendButton.prop('disabled', false).find('iconify-icon').attr('icon', originalIcon);
                    });
                });

                // Delete Chat Button Confirmation
                $(document).on('click', '.delete-chat-btn', function(e) { /* ... (same as before, using swalOptionsWithPause) ... */
                    e.preventDefault();
                    const button = $(this);
                    const form = button.closest('form');
                    const contactName = button.data('contact-name') || 'this contact';

                    Swal.fire(swalOptionsWithPause({
                        title: '{{ __("Delete Chat?") }}',
                        text: `{{ __('Are you sure you want to delete all messages for') }} ${contactName}? {{ __('This action cannot be undone.') }}`,
                        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __("Yes, delete it!") }}', cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    })).then((result) => { if (result.isConfirmed) { form.submit(); } });
                });

                // Delete Message Button Confirmation (AJAX)
                $(document).on('click', '.delete-message-btn', function(e) {
                    e.preventDefault();
                    const button = $(this);
                    const messageId = button.data('message-id');
                    const remoteJid = button.data('remote-jid');
                    const fromMe = button.data('from-me'); // Should be 'true' or 'false' string

                    if (!messageId || !remoteJid || fromMe === undefined) {
                        console.error("Missing data attributes for delete button:", button.data());
                        Toast.fire({icon: 'error', title: '{{ __("Cannot delete message: Missing data.") }}'});
                        return;
                    }

                    const messageKey = {
                        remoteJid: remoteJid,
                        fromMe: (fromMe === 'true' || fromMe === true), // Convert to boolean
                        id: messageId
                        // participant: button.data('participant') // Add if needed for groups
                    };

                    Swal.fire(swalOptionsWithPause({
                        title: '{{ __("Delete Message?") }}', text: `{{ __('Are you sure you want to delete this message?') }}`, icon: 'warning',
                        showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __("Yes, delete") }}', cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    })).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/whatsapp-business/message/delete`, // Use a dedicated route for AJAX deletion
                                type: 'POST', // Use POST with _method override or define a DELETE route
                                data: JSON.stringify({ // Send as JSON
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    _method: 'DELETE', // Method override
                                    messageKey: messageKey // Send the constructed key
                                }),
                                contentType: 'application/json', // Set content type
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        Toast.fire({ icon: 'success', title: response.message || '{{ __("Message deleted.") }}' });
                                        button.closest('.flex.w-full').fadeOut('fast', function() { $(this).remove(); }); // Remove bubble
                                    } else {
                                        Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Error") }}', text: response.message || '{{ __("Could not delete message.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                                    }
                                },
                                error: function(xhr) {
                                     const errorMsg = xhr.responseJSON?.message || '{{ __("Could not delete message.") }}';
                                     Swal.fire(swalOptionsWithPause({ icon: 'error', title: '{{ __("Error") }}', text: errorMsg, customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } }));
                                }
                            });
                        }
                    });
                });


                // --- Emoji Picker Logic (Using Swal Popup) ---
                const emojiButton = document.getElementById('emoji-button');
                const messageInput = document.getElementById('message-input');

                if (emojiButton && messageInput) {
                    emojiButton.addEventListener('click', (e) => {
                        e.stopPropagation();
                        openEmojiSwal();
                    });
                } else {
                     console.warn("Emoji button or message input not found.");
                }

                function openEmojiSwal() { /* ... (same as before, using swalOptionsWithPause) ... */
                    const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '🥳', '😎', '🤓', '🧐', '😕', '😟', '🙁', '☹️', '😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨', '😰', '😥', '😢', '😭', '😱', '😖', '😣', '😞', '😓', '😩', '😫', '🥱', '😤', '😡', '😠', '🤬', '😈', '👿', '💀', '☠️', '💩', '🤡', '👹', '👺', '👻', '👽', '👾', '🤖', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾', '🙈', '🙉', '🙊', '💋', '💌', '💘', '💝', '💖', '💗', '💓', '💞', '💕', '💟', '❣️', '💔', '❤️', '🧡', '💛', '💚', '💙', '💜', '🤎', '🖤', '🤍', '💯', '💢', '💥', '💫', '💦', '💨', '🕳️', '💣', '💬', '👁️‍🗨️', '🗨️', '🗯️', '💭', '💤', '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦵', '🦿', '🦶', '👂', '🦻', '👃', '🧠', '🫀', '🫁', '🦷', '🦴', '👀', '👁️', '👅', '👄', '👶', '🧒', '👦', '👧', '🧑', '👱', '👨', '🧔', '👨‍🦰', '👨‍🦱', '👨‍🦳', '👨‍🦲', '👩', '👩‍🦰', '🧑‍🦰', '👩‍🦱', '🧑‍🦱', '👩‍🦳', '🧑‍🦳', '👩‍🦲', '🧑‍🦲', '👱‍♀️', '👱‍♂️', '🧓', '👴', '👵', '🙍', '🙍‍♂️', '🙍‍♀️', '🙎', '🙎‍♂️', '🙎‍♀️', '🙅', '🙅‍♂️', '🙅‍♀️', '🙆', '🙆‍♂️', '🙆‍♀️', '💁', '💁‍♂️', '💁‍♀️', '🙋', '🙋‍♂️', '🙋‍♀️', '🧏', '🧏‍♂️', '🧏‍♀️', '🙇', '🙇‍♂️', '🙇‍♀️', '🤦', '🤦‍♂️', '🤦‍♀️', '🤷', '🤷‍♂️', '🤷‍♀️', '🧑‍⚕️', '👨‍⚕️', '👩‍⚕️', '🧑‍🎓', '👨‍🎓', '👩‍🎓', '🧑‍🏫', '👨‍🏫', '👩‍🏫', '🧑‍⚖️', '👨‍⚖️', '👩‍⚖️', '🧑‍🌾', '👨‍🌾', '👩‍🌾', '🧑‍🍳', '👨‍🍳', '👩‍🍳', '🧑‍🔧', '👨‍🔧', '👩‍🔧', '🧑‍🏭', '👨‍🏭', '👩‍🏭', '🧑‍💼', '👨‍💼', '👩‍💼', '🧑‍🔬', '👨‍🔬', '👩‍🔬', '🧑‍💻', '👨‍💻', '👩‍💻', '🧑‍🎤', '👨‍🎤', '👩‍🎤', '🧑‍🎨', '👨‍🎨', '👩‍🎨', '🧑‍✈️', '👨‍✈️', '👩‍✈️', '🧑‍🚀', '👨‍🚀', '👩‍🚀', '🧑‍🚒', '👨‍🚒', '👩‍🚒', '👮', '👮‍♂️', '👮‍♀️', '🕵️', '🕵️‍♂️', '🕵️‍♀️', '💂', '💂‍♂️', '💂‍♀️', '🥷', '👷', '👷‍♂️', '👷‍♀️', '🤴', '👸', '👳', '👳‍♂️', '👳‍♀️', '👲', '🧕', '🤵', '🤵‍♂️', '🤵‍♀️', '👰', '👰‍♂️', '👰‍♀️', '🤰', '🤱', '👩‍🍼', '👨‍🍼', '🧑‍🍼', '👼', '🎅', '🤶', '🧑‍🎄', '🦸', '🦸‍♂️', '🦸‍♀️', '🦹', '🦹‍♂️', '🦹‍♀️', '🧙', '🧙‍♂️', '🧙‍♀️', '🧚', '🧚‍♂️', '🧚‍♀️', '🧛', '🧛‍♂️', '🧛‍♀️', '🧜', '🧜‍♂️', '🧜‍♀️', '🧝', '🧝‍♂️', '🧝‍♀️', '🧞', '🧞‍♂️', '🧞‍♀️', '🧟', '🧟‍♂️', '🧟‍♀️', '💆', '💆‍♂️', '💆‍♀️', '💇', '💇‍♂️', '💇‍♀️', '🚶', '🚶‍♂️', '🚶‍♀️', '🧍', '🧍‍♂️', '🧍‍♀️', '🧎', '🧎‍♂️', '🧎‍♀️', '🧑‍🦯', '👨‍🦯', '👩‍🦯', '🧑‍🦼', '👨‍🦼', '👩‍🦼', '🧑‍🦽', '👨‍🦽', '👩‍🦽', '🏃', '🏃‍♂️', '🏃‍♀️', '💃', '🕺', '🕴️', '👯', '👯‍♂️', '👯‍♀️', '🧖', '🧖‍♂️', '🧖‍♀️', '🧗', '🧗‍♂️', '🧗‍♀️', '🤺', '🏇', '⛷️', '🏂', '🏌️', '🏌️‍♂️', '🏌️‍♀️', '🏄', '🏄‍♂️', '🏄‍♀️', '🚣', '🚣‍♂️', '🚣‍♀️', '🏊', '🏊‍♂️', '🏊‍♀️', '⛹️', '⛹️‍♂️', '⛹️‍♀️', '🏋️', '🏋️‍♂️', '🏋️‍♀️', '🚴', '🚴‍♂️', '🚴‍♀️', '🚵', '🚵‍♂️', '🚵‍♀️', '🤸', '🤸‍♂️', '🤸‍♀️', '🤼', '🤼‍♂️', '🤼‍♀️', '🤽', '🤽‍♂️', '🤽‍♀️', '🤾', '🤾‍♂️', '🤾‍♀️', '🤹', '🤹‍♂️', '🤹‍♀️', '🧘', '🧘‍♂️', '🧘‍♀️', '🛀', '🛌', '🧑‍🤝‍🧑', '👭', '👫', '👬', '💏', '👩‍❤️‍💋‍👨', '👨‍❤️‍💋‍👨', '👩‍❤️‍💋‍👩', '💑', '👩‍❤️‍👨', '👨‍❤️‍👨', '👩‍❤️‍👩', '👪', '👨‍👩‍👦', '👨‍👩‍👧', '👨‍👩‍👧‍👦', '👨‍👩‍👦‍👦', '👨‍👩‍👧‍👧', '👨‍👨‍👦', '👨‍👨‍👧', '👨‍👨‍👧‍👦', '👨‍👨‍👦‍👦', '👨‍👨‍👧‍👧', '👩‍👩‍👦', '👩‍👩‍👧', '👩‍👩‍👧‍👦', '👩‍👩‍👦‍👦', '👩‍👩‍👧‍👧', '👨‍👦', '👨‍👦‍👦', '👨‍👧', '👨‍👧‍👦', '👨‍👧‍👧', '👩‍👦', '👩‍👦‍👦', '👩‍👧', '👩‍👧‍👦', '👩‍👧‍👧', '🗣️', '👤', '👥', '🫂'];
                    let emojiHtml = '<div class="emoji-grid">';
                    emojis.forEach(emoji => { emojiHtml += `<span class="swal-emoji">${emoji}</span>`; });
                    emojiHtml += '</div>';

                    Swal.fire(swalOptionsWithPause({
                        title: '{{ __("Select Emoji") }}', html: emojiHtml, showConfirmButton: false, focusConfirm: false,
                        customClass: { popup: 'emoji-popup ' + (document.documentElement.classList.contains('dark') ? 'dark' : '') },
                        didOpen: (modal) => {
                            pauseAutoRefresh();
                            modal.querySelectorAll('.swal-emoji').forEach(el => {
                                el.addEventListener('click', () => {
                                    const emoji = el.textContent;
                                    const start = messageInput.selectionStart;
                                    const end = messageInput.selectionEnd;
                                    const text = messageInput.value;
                                    messageInput.value = text.substring(0, start) + emoji + text.substring(end);
                                    messageInput.selectionStart = messageInput.selectionEnd = start + emoji.length;
                                    messageInput.focus();
                                    messageInput.dispatchEvent(new Event('input', { bubbles: true }));
                                    Swal.close();
                                });
                            });
                        },
                        willClose: () => { resumeAutoRefresh(); }
                    }));
                }


                // Periodic Refresh
                if (!isRefreshPaused) { // Start only if not paused initially
                    if (selectedPhone) messageIntervalId = setInterval(refreshChatMessages, 15000);
                    contactIntervalId = setInterval(refreshContactList, 60000);
                    statusIntervalId = setInterval(updateConnectionStatus, 45000);
                }

                 // Dismiss flash messages
                 setTimeout(() => { $('#flash-success, #flash-error, #flash-validation-errors').fadeOut('slow'); }, 5000);

            }); // End document ready

        </script>
    @endpush
</x-app-layout>
