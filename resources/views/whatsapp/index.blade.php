<x-app-layout>
    {{-- Sección HEAD: meta tags --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Iconify --}}
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    @endpush

    @php
        // Función para convertir CSV a imagen (base64)
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
                // 1. Escapar HTML para seguridad
                $escapedText = e($text);

                // 2. Convertir URLs a enlaces
                $pattern = '/(https?:\/\/[^\s<>"\'`]+)|(www\.[^\s<>"\'`]+)/i';
                $linkedText = preg_replace_callback($pattern, function ($matches) {
                    $url = $matches[0];
                    // Asegurar que la URL tenga http(s)://
                    $href = $url;
                    if (stripos($href, 'www.') === 0) {
                        $href = 'https://' . $href; // Añadir https si empieza con www.
                    }
                    // Limitar longitud mostrada si es muy larga (opcional)
                    $displayUrl = strlen($url) > 50 ? substr($url, 0, 47) . '...' : $url;

                    // Crear el enlace
                    return '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer" class="message-link">' . e($displayUrl) . '</a>';
                }, $escapedText);

                // 3. Convertir saltos de línea a <br>
                return nl2br($linkedText, false); // false para no usar <br /> XHTML
            }
        }
    @endphp

    {{-- Mensajes flash (estilo mejorado como en Telegram) --}}
    @if(session('success'))
        <div class="fixed top-5 right-5 z-[100] max-w-sm p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-lg dark:bg-green-800 dark:text-green-100 dark:border-green-700" role="alert" id="flash-success">
            <div class="flex items-start">
                <iconify-icon icon="mdi:check-circle" class="text-xl mr-2 text-green-500 dark:text-green-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Success!") }}</strong>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-100 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8 dark:bg-green-800 dark:text-green-300 dark:hover:bg-green-700" onclick="document.getElementById('flash-success').style.display='none'" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif
    @if(session('error'))
         <div class="fixed top-5 right-5 z-[100] max-w-sm p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-lg dark:bg-red-800 dark:text-red-100 dark:border-red-700" role="alert" id="flash-error">
            <div class="flex items-start">
                <iconify-icon icon="mdi:alert-circle" class="text-xl mr-2 text-red-500 dark:text-red-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Error!") }}</strong>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
                 <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700" onclick="document.getElementById('flash-error').style.display='none'" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif

    {{-- Contenedor principal del chat --}}
    <div class="flex chat-height overflow-hidden relative bg-white dark:bg-slate-900 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700">

        <div class="w-[320px] flex-none border-r border-slate-200 dark:border-slate-700 flex flex-col h-full bg-slate-50 dark:bg-slate-800">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                 {{-- Contenedor para botones de conexión/acciones --}}
                 <div id="connection-status-header" class="flex items-center justify-between gap-2">
                      <div id="connection-btn" class="flex-1">
                         {{-- Estado de conexión (se carga con JS) --}}
                         <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('Loading status...') }}</span>
                      </div>
                      {{-- Contenedor para botones adicionales (AutoResponse, Sync) --}}
                      <div id="connection-actions" class="flex items-center gap-2">
                          {{-- Botones se cargan con JS --}}
                      </div>
                 </div>
            </div>

            <div class="p-3 border-b border-slate-200 dark:border-slate-700">
                <div class="relative">
                    <input type="text" id="contactSearch" placeholder="{{ __('Search or start new chat') }}" class="w-full bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
                        <iconify-icon icon="heroicons:magnifying-glass-20-solid"></iconify-icon>
                    </div>
                </div>
            </div>

            <div id="contacts-panel" class="flex-1 overflow-y-auto contact-height">
                {{-- data-simplebar (si usas simplebar.js) --}}
                <ul id="contact-list" class="divide-y divide-slate-100 dark:divide-slate-700">
                    {{-- Renderizado con Blade --}}
                    @forelse($sortedContacts as $contact)
                        @php
                            $cleanName = preg_replace('/@.*$/', '', $contact['name'] ?? $contact['phone']);
                            $contactInitial = strtoupper(substr($cleanName, 0, 1));
                            $contactPhone = $contact['phone'];
                            $isActive = isset($selectedPhone) && $selectedPhone === $contactPhone;
                        @endphp
                        <li class="contact-item flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-700 transition duration-150 {{ $isActive ? 'active' : '' }}" data-phone="{{ $contactPhone }}"> {{-- Added data-phone --}}
                            {{-- Enlace al chat --}}
                            <a href="{{ route('whatsapp.conversation', $contactPhone) }}" class="flex items-center p-3 w-full">
                                <div class="flex-none mr-3">
                                    {{-- Avatar Placeholder --}}
                                    <div class="contact-avatar-initials w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-300 font-medium">
                                        {{ $contactInitial }}
                                    </div>
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p class="contact-item-name text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ $cleanName }}</p>
                                    {{-- Opcional: Último mensaje o número --}}
                                    <p class="contact-item-detail text-xs text-slate-500 dark:text-slate-400 truncate">{{ $contactPhone }}</p>
                                </div>
                                {{-- Opcional: Hora último mensaje --}}
                                {{-- <span class="contact-item-time text-xs text-slate-400 dark:text-slate-500 ml-2 whitespace-nowrap">10:30</span> --}}
                            </a>
                            {{-- Botón para eliminar chat (más sutil) --}}
                            <form action="{{ route('whatsapp.chat.destroy', $contactPhone) }}" method="POST" class="mr-2 flex-none delete-chat-form">
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
        <div class="flex-1 flex flex-col h-full bg-slate-100 dark:bg-slate-950 chat-bg-pattern"> {{-- Fondo con patrón --}}

            @if(isset($selectedPhone))
                @php
                    $contactSelected = collect($sortedContacts)->firstWhere('phone', $selectedPhone);
                    $selectedName = $contactSelected ? preg_replace('/@.*$/', '', $contactSelected['name']) : $selectedPhone;
                    $selectedInitial = strtoupper(substr($selectedName, 0, 1));
                    $selectedJid = $selectedPhone . '@s.whatsapp.net'; // JID completo para comparación
                @endphp

                <div id="chat-header" class="flex items-center p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 h-[65px] flex-none">
                     <div class="flex items-center flex-1 overflow-hidden">
                         {{-- Avatar --}}
                         <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-300 font-medium mr-3 flex-none chat-header-avatar">
                             {{ $selectedInitial }}
                         </div>
                         {{-- Nombre y Estado --}}
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

                <div id="chat-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-2">
                    {{-- Renderizado con Blade --}}
                    @forelse($messages->sortBy('created_at') as $message)
                        @php
                            // Determinar si el mensaje es recibido o enviado
                            $isReceived = !($message['key']['fromMe'] ?? false);
                            $timestamp = $message['messageTimestamp'] ?? (isset($message['created_at']) ? \Carbon\Carbon::parse($message['created_at'])->timestamp : null);
                            $formattedTime = $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp)->format('H:i') : '';
                            $messageId = $message['key']['id'] ?? null;
                            $remoteJid = $message['key']['remoteJid'] ?? '';
                            // Determinar el estado del mensaje (ejemplo básico)
                            $messageStatus = $message['status'] ?? 'sent'; // Asume que el backend envía 'sent', 'delivered', 'read'
                            $statusIcon = 'mdi:check'; // Default: sent
                            if ($messageStatus === 'delivered') $statusIcon = 'mdi:check-all';
                            if ($messageStatus === 'read') $statusIcon = 'mdi:check-all'; // Mismo icono, se coloreará con CSS
                            $statusClass = $messageStatus === 'read' ? 'status-read' : ''; // Clase para ticks azules
                        @endphp

                        <div class="flex w-full {{ $isReceived ? 'justify-start' : 'justify-end' }}">
                            <div class="relative message-bubble-wrapper {{ $isReceived ? 'message-received mr-auto' : 'message-sent ml-auto' }}">
                                <div class="message-bubble group"> {{-- Added group class --}}
                                    {{-- Contenido del Mensaje --}}
                                    @php $mediaRendered = false; @endphp

                                    {{-- Imagen --}}
                                    @if(isset($message['message']['imageMessage']) || (isset($message['image']) && $message['image']))
                                        @php
                                            $imageSrc = $message['image'] ?? null;
                                            if (!$imageSrc && isset($message['message']['imageMessage']['url'])) { $imageSrc = null; }
                                            elseif ($imageSrc && strpos($imageSrc, 'data:image') === 0) { $imageSrc = convertCsvImage($imageSrc); }
                                            elseif ($imageSrc) { $imageSrc = asset($imageSrc); }
                                        @endphp
                                        @if($imageSrc)
                                            <img src="{{ $imageSrc }}" alt="Image" class="chat-image max-w-full h-auto rounded-lg cursor-pointer block mb-1"
                                                 onclick="showZoomModal('{{ $imageSrc }}')">
                                            @php $mediaRendered = true; @endphp
                                        @endif
                                    @endif

                                    {{-- Video --}}
                                    @if(isset($message['message']['videoMessage']))
                                        @php $videoUrl = null; @endphp {{-- Deshabilitado por ahora --}}
                                        @if($videoUrl)
                                        <div class="relative video-thumbnail bg-slate-700 rounded-lg flex items-center justify-center cursor-pointer mb-1" onclick="showVideoModal('{{ $videoUrl }}')" style="width: 250px; height: 150px;">
                                            <iconify-icon icon="mdi:play-circle-outline" class="text-4xl text-white z-10"></iconify-icon>
                                        </div>
                                        @php $mediaRendered = true; @endphp
                                        @endif
                                    @endif

                                    {{-- Texto del Mensaje (incluyendo caption de media) --}}
                                    @php
                                        $textContent = $message['message']['conversation']
                                            ?? $message['message']['extendedTextMessage']['text']
                                            ?? $message['message']['imageMessage']['caption']
                                            ?? $message['message']['videoMessage']['caption']
                                            ?? '';
                                    @endphp
                                    @if($textContent)
                                    <div class="message-text-content {{ $mediaRendered ? 'mt-1' : '' }}">
                                        {{-- Llamar a la función formatMessageText para convertir URLs --}}
                                        {!! formatMessageText($textContent) !!}
                                    </div>
                                    @endif

                                    {{-- Timestamp y Estado --}}
                                    <div class="message-timestamp-wrapper">
                                        <span class="message-timestamp">{{ $formattedTime }}</span>
                                        @if(!$isReceived)
                                            <span class="message-status-icon {{ $statusClass }}"><iconify-icon icon="{{ $statusIcon }}"></iconify-icon></span>
                                        @endif
                                    </div>

                                    {{-- Botón Eliminar (posición absoluta, se muestra al hacer hover en la burbuja) --}}
                                    @if($messageId)
                                    <form action="/whatsapp/message/{{ $messageId }}" method="POST" class="absolute top-0 right-0 m-1 delete-message-form opacity-0 group-hover:opacity-100 transition-opacity">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="remoteJid" value="{{ $remoteJid }}">
                                        <input type="hidden" name="fromMe" value="{{ $message['key']['fromMe'] ? 'true' : 'false' }}">
                                        <button type="button" class="delete-message-btn text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 p-0.5 rounded-full bg-white/50 dark:bg-slate-800/50"
                                                data-message-id="{{ $messageId }}"
                                                title="{{ __('Delete message') }}">
                                            <iconify-icon icon="mdi:trash-can-outline" class="text-xs"></iconify-icon>
                                        </button>
                                    </form>
                                    @endif

                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-slate-500 dark:text-slate-400 py-10">{{ __("No messages found for this contact.") }}</p>
                    @endforelse
                </div>

                <div id="send-message-container" class="p-3 border-t border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex-none">
                    <div class="flex items-end space-x-3">
                        {{-- Botones opcionales (Emoji, Adjuntar) --}}
                        {{-- <button class="btn btn-icon btn-light dark:btn-dark rounded-full flex-none w-10 h-10 p-0"><iconify-icon icon="mdi:emoticon-happy-outline" class="text-xl"></iconify-icon></button> --}}
                        {{-- <button class="btn btn-icon btn-light dark:btn-dark rounded-full flex-none w-10 h-10 p-0"><iconify-icon icon="mdi:paperclip" class="text-xl"></iconify-icon></button> --}}

                        {{-- Editor TinyMCE --}}
                        <div class="flex-1">
                            <textarea id="tiny-editor" placeholder="{{ __('Type a message') }}..."></textarea> {{-- Placeholder actualizado --}}
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
                     {{-- <hr class="w-24 border-t border-slate-300 dark:border-slate-700 my-6">
                     <p class="text-xs text-slate-400 dark:text-slate-500 flex items-center">
                         <iconify-icon icon="heroicons:lock-closed-20-solid" class="mr-1"></iconify-icon> End-to-end encrypted
                     </p> --}}
                </div>
            @endif
        </div>
        </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <style>
            .chat-height {
                height: calc(100vh - 100px); /* Ajusta según tu layout general */
                min-height: 550px;
            }
            .contact-height {
                /* No max-height, usa flex-1 para llenar espacio */
            }
            .chat-bg-pattern {
                /* Pattern from https://heropatterns.com/ */
                background-color: #e2e8f0; /* bg-slate-200 */
                /* background-image: url("data:image/svg+xml,%3Csvg width='52' height='26' viewBox='0 0 52 26' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23cbd5e1' fill-opacity='0.4'%3E%3Cpath d='M10 10c0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6h2c0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4v2c-3.314 0-6-2.686-6-6 0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6zm25.464-1.95l8.486 8.486-1.414 1.414-8.486-8.486 1.414-1.414z' /%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); */
            }
            .dark .chat-bg-pattern {
                 background-color: #0f172a; /* dark:bg-slate-900 */
                 /* background-image: url("data:image/svg+xml,%3Csvg width='52' height='26' viewBox='0 0 52 26' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23334155' fill-opacity='0.3'%3E%3Cpath d='M10 10c0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6h2c0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4 3.314 0 6 2.686 6 6 0 2.21 1.79 4 4 4v2c-3.314 0-6-2.686-6-6 0-2.21-1.79-4-4-4-3.314 0-6-2.686-6-6zm25.464-1.95l8.486 8.486-1.414 1.414-8.486-8.486 1.414-1.414z' /%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); */
            }

            /* Contact list styles */
            #contact-list .contact-item.active {
                background-color: #e2e8f0; /* bg-slate-200 */
            }
            .dark #contact-list .contact-item.active {
                background-color: #1e293b; /* bg-slate-800 */
            }
            #contact-list .contact-item.active .contact-item-name {
                 font-weight: 600; /* font-semibold */
            }
            .dark #contact-list .contact-item.active .contact-item-name {
                 color: #f1f5f9; /* dark:text-slate-100 */
            }

            /* Message bubble styles */
            .message-bubble-wrapper { max-width: 70%; } /* Slightly narrower */
            .message-bubble {
                padding: 6px 12px; /* Smaller padding */
                border-radius: 8px; /* Less rounded */
                word-wrap: break-word; /* Ensure text wraps */
                overflow-wrap: break-word; /* Ensure text wraps */
                position: relative;
                box-shadow: 0 1px 0.5px rgba(11, 20, 26, 0.13);
                min-width: 60px; /* Min width for timestamp */
                 /* Group hover to show delete button */
                @apply group;
            }
            /* Triangle / Tail */
            .message-bubble::before {
                content: "";
                position: absolute;
                bottom: 0px;
                height: 12px;
                width: 12px;
                background-color: inherit; /* Match bubble color */
                clip-path: path('M0,0 L12,0 L12,12 L0,12 Z'); /* Default square */
            }
            .message-received .message-bubble::before {
                left: -6px;
                clip-path: path('M6,12 L12,0 L12,12 L6,12 Z'); /* Left tail */
            }
            .message-sent .message-bubble::before {
                right: -6px;
                clip-path: path('M0,0 L6,12 L0,12 L0,0 Z'); /* Right tail */
            }
            /* Colors */
            .message-received .message-bubble {
                background-color: #ffffff; /* White */
                color: #111b21; /* WhatsApp dark text */
            }
            .dark .message-received .message-bubble {
                 background-color: #202c33; /* WhatsApp dark received bubble */
                 color: #e9edef; /* WhatsApp dark text */
            }
            .message-sent .message-bubble {
                background-color: #dcf8c6; /* WhatsApp green */
                color: #111b21;
            }
             .dark .message-sent .message-bubble {
                 background-color: #005c4b; /* WhatsApp dark green */
                 color: #e9edef;
             }

            /* Timestamp and Status Icon Wrapper */
            .message-timestamp-wrapper {
                float: right; /* Position to the bottom right */
                margin-left: 10px; /* Space from text */
                margin-top: 4px; /* Space from text if it wraps */
                line-height: 1;
                white-space: nowrap; /* Prevent wrapping */
                user-select: none;
                position: relative; /* Needed for absolute positioning of status icon? */
                bottom: -2px; /* Align slightly lower */
                /* Clear float to prevent layout issues if bubble is very narrow */
                clear: right;
            }
            .message-timestamp {
                font-size: 0.68rem; /* 11px approx */
                color: #667781; /* WhatsApp timestamp gray */
            }
            .dark .message-timestamp {
                 color: #a0aec0; /* Lighter gray for dark mode */
            }
            .message-status-icon {
                display: inline-block;
                margin-left: 3px;
                font-size: 0.8rem; /* Slightly larger than timestamp */
                color: #667781; /* Default check color */
                vertical-align: middle; /* Align with timestamp */
            }
            .dark .message-status-icon {
                 color: #a0aec0;
            }
            /* Blue ticks (needs class added based on status) */
            .message-status-icon.status-read iconify-icon {
                 color: #53bdeb !important; /* WhatsApp blue tick color */
            }


            .message-bubble img.chat-image,
            .message-bubble .video-thumbnail {
                max-width: 320px; /* Larger max width */
                max-height: 320px;
                border-radius: 6px; /* Match bubble radius */
                cursor: pointer;
                display: block;
                margin-bottom: 2px; /* Reduce space */
            }
             .message-bubble .video-thumbnail::after { /* Play icon */
                 content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                 width: 50px; height: 50px; background-color: rgba(0, 0, 0, 0.6); border-radius: 50%;
                 background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white' width='24px' height='24px'%3E%3Cpath d='M8 5v14l11-7z'/%3E%3Cpath d='M0 0h24v24H0z' fill='none'/%3E%3C/svg%3E");
                 background-repeat: no-repeat; background-position: center; background-size: 24px;
                 opacity: 0.9; transition: opacity 0.2s ease; pointer-events: none;
             }
            .message-bubble .video-thumbnail:hover::after { opacity: 1; }

            /* FIX v2: Text Content Wrapping */
            .message-text-content {
                /* Force break for long words/URLs */
                word-break: break-word; /* More compatible than break-all */
                overflow-wrap: break-word; /* Standard property */
                /* Ensure it doesn't overflow */
                overflow: hidden;
                /* Add padding for timestamp */
                padding-right: 55px;
                min-height: 1.2em;
                /* Allow text to flow correctly */
                display: block; /* Or inline-block if needed, but block is usually fine */
                margin-bottom: 15px; /* Space for timestamp below */
            }
            /* Remove bottom margin if only timestamp follows */
            .message-bubble:not(:has(img.chat-image, div.video-thumbnail)) .message-text-content:has(+ .message-timestamp-wrapper) {
                 margin-bottom: 0;
            }
             /* Adjust margin below media if only timestamp follows */
             .message-bubble:has(> img.chat-image + .message-timestamp-wrapper) img.chat-image,
            .message-bubble:has(> div.video-thumbnail + .message-timestamp-wrapper) div.video-thumbnail {
                 margin-bottom: 15px;
            }
            /* Remove right padding if media is present, timestamp floats below media */
            .message-bubble:has(img.chat-image) .message-text-content,
            .message-bubble:has(div.video-thumbnail) .message-text-content {
                 padding-right: 0;
            }

            /* Style for clickable links within messages */
            .message-link {
                color: #00a884; /* WhatsApp link color */
                text-decoration: underline;
                cursor: pointer;
            }
            .dark .message-link {
                color: #00a884; /* Same color often used in dark mode */
            }
            .message-link:hover {
                text-decoration: none;
            }


            /* TinyMCE adjustments */
            #send-message-container .tox-tinymce {
                 border-radius: 24px !important; /* More rounded */
                 border: none !important; /* Remove border */
                 box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            #send-message-container .tox .tox-edit-area__iframe {
                 background-color: #ffffff !important;
            }
            .dark #send-message-container .tox .tox-edit-area__iframe {
                 background-color: #202c33 !important; /* WA dark input bg */
                 color: #e9edef;
            }
             #send-message-container .tox .tox-toolbar__primary {
                 background: #ffffff !important;
                 border-bottom: 1px solid #e2e8f0 !important;
                 border-top-left-radius: 24px; border-top-right-radius: 24px;
            }
            .dark #send-message-container .tox .tox-toolbar__primary {
                 background: #202c33 !important;
                 border-bottom-color: #374045 !important;
            }
             /* Hide toolbar for simpler look */
             #send-message-container .tox .tox-toolbar__primary {
                  /* display: none !important; */
             }

            /* Connection status buttons */
            #connection-status-header .btn { @apply w-auto px-3 py-1.5 h-auto text-sm; }
            #connection-status-header .btn.rounded-full { @apply w-9 h-9 p-0; } /* Keep icon buttons small */

            /* Delete buttons */
            .delete-chat-btn, .delete-message-btn {
                opacity: 0.6;
                transition: opacity 0.2s ease;
            }
            .delete-chat-btn:hover, .delete-message-btn:hover {
                opacity: 1;
            }
            .delete-message-form {
                /* Position and hide/show handled by group-hover on parent bubble */
            }

        </style>
    @endpush

    {{-- Scripts --}}
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
        </script>

        <script src="https://cdn.tiny.cloud/1/v6bk2mkmbxcn1oybhyu2892lyn9zykthl51xgkrh7ye0f7xv/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
             // TinyMCE Initialization (Simplified Toolbar)
            function initTinyMCE() {
                tinymce.init({
                    selector: '#tiny-editor',
                    plugins: 'autoresize link emoticons', // Removed image, code
                    toolbar: 'bold italic | emoticons | link', // Simplified toolbar
                    menubar: false, statusbar: false,
                    autoresize_min_height: 50, autoresize_max_height: 180, autoresize_bottom_margin: 10,
                    placeholder: "{{ __('Type a message') }}...",
                    readonly: false,
                    content_style: `
                        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.4; }
                        p { margin: 0; }
                    `, // Adjusted font size
                    skin: (document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide'),
                    content_css: (document.documentElement.classList.contains('dark') ? 'dark' : 'default')
                });
            }

            // Observer for theme change
            const tinymceObserver = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'class') {
                        tinymce.editors.forEach(editor => editor.destroy());
                        initTinyMCE(); // Re-initialize with correct skin
                    }
                });
            });
            tinymceObserver.observe(document.documentElement, { attributes: true });

        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // =======================================================
            // ========== WhatsApp Chat Logic (Adjusted) ==========
            // =======================================================
            var autoResponseConfig = {!! json_encode($autoResponseConfig ?? null) !!}; // Load initial config
            const userId = "{{ auth()->id() }}";
            const selectedPhone = "{{ $selectedPhone ?? null }}"; // Get selected phone from Blade

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
            const swalObserver = new MutationObserver(mutations => { /* ... (same as before) ... */ });
            swalObserver.observe(document.documentElement, { attributes: true });


            // --- Utility Functions ---
            function scrollChatToBottom() {
                var chatContainer = document.getElementById('chat-container');
                if (chatContainer) { chatContainer.scrollTop = chatContainer.scrollHeight; }
            }
            function showZoomModal(url) { /* ... (same as before) ... */ }
            function showVideoModal(url) { /* ... (same as before) ... */ }

            // --- Connection Status & Actions ---
            function updateConnectionStatus() {
                fetch('{{ secure_url("/api/whatsapp/check?user_id=") }}' + userId, { method: 'GET' })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Connection Status:", data);
                        const connectionBtnContainer = $('#connection-btn');
                        const actionsContainer = $('#connection-actions');
                        connectionBtnContainer.empty();
                        actionsContainer.empty();

                        if (data.success) {
                            if (data.connected) {
                                // Connected State: Show disconnect, auto-response, sync buttons
                                connectionBtnContainer.html(`
                                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-800/50 px-2 py-0.5 rounded-full">{{ __('Connected') }}</span>
                                `);
                                actionsContainer.html(`
                                    <button id="btnAutoResponse" class="btn btn-icon btn-secondary light rounded-full w-9 h-9 p-0" title="{{ __('Auto Response Settings') }}">
                                        <iconify-icon icon="mdi:robot-outline" class="text-lg"></iconify-icon>
                                    </button>
                                    <form action="{{ route('whatsapp.importContacts') }}" method="POST" class="m-0">
                                        @csrf
                                        <button id="sync-contacts" type="submit" class="btn btn-icon btn-primary light rounded-full w-9 h-9 p-0" title="{{ __('Import Contacts') }}">
                                            <iconify-icon icon="mdi:sync" class="text-lg"></iconify-icon>
                                        </button>
                                    </form>
                                    <button id="btnDisconnect" class="btn btn-icon btn-danger light rounded-full w-9 h-9 p-0" title="{{ __('Disconnect Session') }}">
                                        <iconify-icon icon="mdi:logout-variant" class="text-lg"></iconify-icon>
                                    </button>
                                `);
                                // Add event listeners
                                $('#btnDisconnect').on('click', handleDisconnect);
                                $('#btnAutoResponse').on('click', handleAutoResponseEdit);
                                // Sync button uses standard form submission

                            } else {
                                // Disconnected State: Show connect button
                                connectionBtnContainer.html(`
                                    <button id="btnConnect" class="btn btn-success btn-sm flex items-center gap-1">
                                        <iconify-icon icon="mdi:whatsapp" class="text-base"></iconify-icon>
                                        {{ __('Connect') }}
                                    </button>
                                `);
                                $('#btnConnect').on('click', function() {
                                    Swal.fire({
                                        title: '{{ __("Generating QR") }}', text: '{{ __("Please wait...") }}',
                                        allowOutsideClick: false, didOpen: () => { Swal.showLoading(); },
                                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } // Dark mode for Swal
                                    });
                                    startWhatsAppSession();
                                });
                            }
                        } else {
                             // Error State
                            connectionBtnContainer.html(`
                                 <span class="text-xs font-medium text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-800/50 px-2 py-0.5 rounded-full">{{ __('Error') }}</span>
                            `);
                             actionsContainer.html(`
                                <button id="btnReconnect" class="btn btn-icon btn-warning light rounded-full w-9 h-9 p-0" title="{{ __('Retry Connection') }}">
                                    <iconify-icon icon="mdi:refresh" class="text-lg"></iconify-icon>
                                </button>
                            `);
                             $('#btnReconnect').on('click', updateConnectionStatus); // Simple retry
                        }
                    })
                    .catch(error => {
                        console.error('Error checking connection status:', error);
                         $('#connection-btn').html('<span class="text-red-500 text-xs">{{ __("Status Check Failed") }}</span>');
                    });
            }

            function startWhatsAppSession() {
                fetch('{{ secure_url("/api/whatsapp/start-session") }}', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(apiData => {
                    if (apiData.success && apiData.qr) {
                        Swal.close();
                        Swal.fire({
                            title: '{{ __("Scan the QR Code") }}', imageUrl: apiData.qr, imageAlt: 'QR Code',
                            showConfirmButton: true, confirmButtonText: '{{ __("Close") }}',
                            customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                        });
                        // Start polling status shortly after showing QR
                        setTimeout(updateConnectionStatus, 5000); // Check after 5s
                    } else if (apiData.success && apiData.message === 'Session already started') {
                         // Already connected or starting, update status
                         Swal.close();
                         Toast.fire({ icon: 'info', title: '{{ __("Session is already starting or connected.") }}' });
                         updateConnectionStatus();
                    } else {
                        // Keep trying if QR not ready yet (maybe add a limit)
                        console.log("QR not ready, retrying...");
                        setTimeout(startWhatsAppSession, 2000); // Retry after 2s
                    }
                })
                .catch(error => {
                    console.error('Error starting WhatsApp session:', error);
                    Swal.fire({ icon: 'error', title: '{{ __("Connection Error") }}', text: '{{ __("Could not start session. Please try again.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                });
            }

            function handleDisconnect() {
                 Swal.fire({
                    title: '{{ __("Disconnect Session?") }}', text: "{{ __('Are you sure you want to log out?') }}", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280', confirmButtonText: '{{ __("Yes, disconnect") }}', cancelButtonText: '{{ __("Cancel") }}',
                    customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const disconnectBtn = $('#btnDisconnect');
                        disconnectBtn.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                        fetch('{{ secure_url("/api/whatsapp/logout?user_id=") }}' + userId, { method: 'GET' })
                            .then(response => response.json())
                            .then(logoutData => {
                                if (logoutData.success) {
                                    Toast.fire({ icon: 'success', title: '{{ __("Disconnected successfully.") }}' });
                                    updateConnectionStatus(); // Update UI
                                    // Optionally reload the page or clear chat area
                                    window.location.href = "{{ route('whatsapp.index') }}"; // Redirect to index after logout
                                } else {
                                    Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: logoutData.message || '{{ __("Failed to disconnect.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error('Error disconnecting:', error);
                                Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: '{{ __("An error occurred during disconnection.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            })
                            .finally(() => {
                                // Re-enable button (status update will handle correct state)
                                disconnectBtn.prop('disabled', false).find('iconify-icon').attr('icon', 'mdi:logout-variant');
                                updateConnectionStatus(); // Ensure UI reflects final state
                            });
                    }
                });
            }

            function handleAutoResponseEdit() {
                // Fetch current config first (assuming autoResponseConfig might be stale)
                $.ajax({
                    url: '/auto-response-whatsapp-get', // Need a GET endpoint
                    type: 'GET', dataType: 'json',
                    success: function(response) {
                        if(response.success && response.data) {
                            autoResponseConfig = response.data; // Update local config
                            showAutoResponseModal(); // Show modal with fresh data
                        } else {
                             Toast.fire({ icon: 'error', title: '{{ __("Could not load current settings.") }}' });
                             showAutoResponseModal(); // Show modal with potentially stale data
                        }
                    },
                    error: function() {
                         Toast.fire({ icon: 'error', title: '{{ __("Error fetching settings.") }}' });
                         showAutoResponseModal(); // Show modal with potentially stale data
                    }
                });
            }

            function showAutoResponseModal() {
                var selectedValue = autoResponseConfig ? autoResponseConfig.whatsapp : '0';
                var promptValue = autoResponseConfig ? autoResponseConfig.whatsapp_prompt : '';

                Swal.fire({
                    title: '{{ __("Auto Response Settings") }}',
                    html: `
                        <div class="space-y-4 text-left p-2">
                            <div>
                                <label for="swal-whatsapp-mode" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Mode") }}</label>
                                <select id="swal-whatsapp-mode" class="swal2-select w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-md focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="0" ${selectedValue == '0' ? 'selected' : ''}>{{ __("Disabled") }}</option>
                                    <option value="1" ${selectedValue == '1' ? 'selected' : ''}>{{ __("Auto Response") }}</option>
                                    <option value="2" ${selectedValue == '2' ? 'selected' : ''}>{{ __("AI Response") }}</option>
                                    <option value="3" ${selectedValue == '3' ? 'selected' : ''}>{{ __("Create Ticket") }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="swal-whatsapp-prompt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Prompt / Auto Text") }}</label>
                                <textarea id="swal-whatsapp-prompt" class="swal2-textarea w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-md focus:border-emerald-500 focus:ring-emerald-500" rows="4" placeholder="{{ __('Enter text or prompt...') }}">${promptValue}</textarea>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __("Required if mode is not 'Disabled'.") }}</p>
                            </div>
                        </div>`,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save Changes") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' },
                    preConfirm: () => {
                        const responseType = $('#swal-whatsapp-mode').val();
                        const promptText = $('#swal-whatsapp-prompt').val().trim();
                        if (responseType !== '0' && !promptText) {
                            Swal.showValidationMessage('{{ __("The prompt/text field is required for this option.") }}');
                            $('#swal-whatsapp-prompt').addClass('border-red-500').focus(); return false;
                        }
                         $('#swal-whatsapp-prompt').removeClass('border-red-500');
                        return { whatsapp: responseType, whatsapp_prompt: promptText };
                    }
                }).then((result) => {
                    if(result.isConfirmed){
                        const dataToSave = result.value;
                        $.ajax({
                            url: '/auto-response-whatsapp', // POST endpoint
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                whatsapp: dataToSave.whatsapp,
                                whatsapp_prompt: dataToSave.whatsapp_prompt,
                            },
                            success: function(response) {
                                if(response.success){
                                    Toast.fire({ icon: 'success', title: response.message || '{{ __("Settings saved!") }}' });
                                    autoResponseConfig = response.data || null; // Update local cache
                                } else {
                                    Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: response.message || '{{ __("Could not save settings.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: '{{ __("An error occurred while saving.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            }
                        });
                    }
                });
            }

            // --- Message & Contact Handling ---
            function refreshChatMessages() {
                if (selectedPhone && document.getElementById('chat-container')) {
                    console.log("Refreshing chat messages for:", selectedPhone);
                    const chatContainer = $("#chat-container");
                    const scrollHeightBefore = chatContainer[0].scrollHeight;
                    const currentScroll = chatContainer.scrollTop();
                    const containerHeight = chatContainer.innerHeight();
                    const threshold = 50; // Threshold to stick to bottom
                    const wasNearBottom = currentScroll + containerHeight >= scrollHeightBefore - threshold;

                    chatContainer.load(window.location.href + " #chat-container > *", function(response, status, xhr) {
                        if (status == "error") {
                            console.error("Error loading chat messages:", xhr.status, xhr.statusText);
                        } else {
                            // Scroll only if user was near the bottom before refresh
                            if(wasNearBottom) {
                                scrollChatToBottom();
                            } else {
                                // Try to maintain scroll position relative to the top
                                const newScrollHeight = chatContainer[0].scrollHeight;
                                const heightDiff = newScrollHeight - scrollHeightBefore;
                                if (heightDiff > 0) {
                                     chatContainer.scrollTop(currentScroll + heightDiff);
                                }
                                console.log("Maintained scroll position");
                            }
                        }
                    });
                }
            }


            function refreshContactList() {
                 if (document.getElementById('contacts-panel')) {
                    console.log("Refreshing contact list...");
                     $("#contacts-panel").load(window.location.href + " #contacts-panel > *", function(response, status, xhr) {
                         if (status == "error") {
                             console.error("Error loading contacts:", xhr.status, xhr.statusText);
                         } else {
                             if(selectedPhone) {
                                 // Use attribute selector for data-phone
                                 $(`#contact-list .contact-item[data-phone="${selectedPhone}"]`).addClass('active');
                             }
                             applyContactSearchFilter();
                         }
                     });
                 }
            }

            function applyContactSearchFilter() {
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
                initTinyMCE(); // Initialize TinyMCE
                updateConnectionStatus(); // Initial status check
                scrollChatToBottom(); // Scroll on initial load

                // Contact Search Filter
                const searchInput = document.getElementById('contactSearch');
                if (searchInput) {
                    searchInput.addEventListener('input', applyContactSearchFilter);
                }

                // Send Message Button
                $('#sendMessageButton').on('click', function(event) {
                    event.preventDefault();
                    if (!selectedPhone) { Toast.fire({ icon: 'warning', title: '{{ __("No chat selected") }}' }); return; }

                    var messageContent = tinymce.get('tiny-editor').getContent(); // Get HTML content
                    var messageText = tinymce.get('tiny-editor').getContent({ format: 'text' }).trim(); // For validation

                    if (!messageText) { Toast.fire({ icon: 'warning', title: '{{ __("Message cannot be empty") }}' }); return; }

                    const sendButton = $(this);
                    const originalIcon = sendButton.find('iconify-icon').attr('icon');
                    sendButton.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');

                    // AJAX sending logic
                    fetch('{{ secure_url("/api/whatsapp/send-message-now") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            token: "{{ env('WHATSAPP_API_TOKEN') }}",
                            sessionId: userId,
                            jid: selectedPhone + '@s.whatsapp.net',
                            message: messageContent
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            tinymce.get('tiny-editor').setContent('');
                            refreshChatMessages(); // Refresh chat view immediately
                        } else {
                            Swal.fire({ icon: 'error', title: '{{ __("Send Error") }}', text: data.message || '{{ __("Failed to send message.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                        }
                    })
                    .catch(error => {
                        console.error('Error sending message:', error);
                        Swal.fire({ icon: 'error', title: '{{ __("Send Error") }}', text: '{{ __("An error occurred.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                    })
                    .finally(() => {
                        sendButton.prop('disabled', false).find('iconify-icon').attr('icon', originalIcon);
                    });
                });

                // Delete Chat Button Confirmation
                $(document).on('click', '.delete-chat-btn', function(e) {
                    e.preventDefault();
                    const button = $(this);
                    const form = button.closest('form');
                    const contactName = button.data('contact-name') || 'this contact';

                    Swal.fire({
                        title: '{{ __("Delete Chat?") }}',
                        text: `{{ __('Are you sure you want to delete all messages for') }} ${contactName}? {{ __('This action cannot be undone.') }}`,
                        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __("Yes, delete it!") }}', cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    }).then((result) => { if (result.isConfirmed) { form.submit(); } });
                });

                 // Delete Message Button Confirmation
                $(document).on('click', '.delete-message-btn', function(e) {
                    e.preventDefault();
                    const button = $(this);
                    const form = button.closest('form');
                    const messageId = button.data('message-id');

                     Swal.fire({
                        title: '{{ __("Delete Message?") }}', text: `{{ __('Are you sure you want to delete this message?') }}`, icon: 'warning',
                        showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __("Yes, delete") }}', cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: form.attr('action'), type: 'POST', data: form.serialize(),
                                success: function(response) {
                                    Toast.fire({ icon: 'success', title: '{{ __("Message deleted.") }}' });
                                    button.closest('.flex.w-full').remove(); // Remove bubble
                                },
                                error: function(xhr) {
                                     Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: '{{ __("Could not delete message.") }}', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            });
                        }
                    });
                });


                // Periodic Refresh (ACTIVATED)
                // Note: Consider performance and use WebSockets/SSE for better real-time updates if possible.
                setInterval(refreshChatMessages, 15000); // Refresh messages every 15s
                setInterval(refreshContactList, 60000); // Refresh contacts every 60s
                setInterval(updateConnectionStatus, 45000); // Check connection every 45s

                 // Dismiss flash messages after a delay
                setTimeout(() => {
                    $('#flash-success, #flash-error').fadeOut('slow');
                }, 5000); // 5 seconds

            }); // End document ready

        </script>
    @endpush
</x-app-layout>
