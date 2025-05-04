<x-app-layout>
    {{-- Sección HEAD: meta tags --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    @php
        // Función para convertir CSV a imagen (base64) - Mantenida como estaba
        if (! function_exists('convertCsvImage')) {
            function convertCsvImage($imageString) {
                $parts = explode(',', $imageString, 2);
                if (!isset($parts[1])) {
                    return $imageString;
                }
                $dataAfterComma = trim($parts[1]);
                if (ctype_digit(str_replace([',', ' '], '', $dataAfterComma))) {
                    $numbers = explode(',', $dataAfterComma);
                    $binaryData = '';
                    foreach ($numbers as $num) {
                        $binaryData .= chr((int)$num);
                    }
                    return $parts[0] . ',' . base64_encode($binaryData);
                }
                return $imageString;
            }
        }
    @endphp

    {{-- Mensajes flash (estilo mejorado) --}}
    @if(session('success'))
        <div class="fixed top-5 right-5 z-50 max-w-sm p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-lg dark:bg-green-800 dark:text-green-100 dark:border-green-700" role="alert">
            <div class="flex items-start">
                <iconify-icon icon="mdi:check-circle" class="text-xl mr-2 text-green-500 dark:text-green-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Success!") }}</strong>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-100 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8 dark:bg-green-800 dark:text-green-300 dark:hover:bg-green-700" data-dismiss-target="alert" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif
    @if(session('error'))
         <div class="fixed top-5 right-5 z-50 max-w-sm p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-lg dark:bg-red-800 dark:text-red-100 dark:border-red-700" role="alert">
            <div class="flex items-start">
                <iconify-icon icon="mdi:alert-circle" class="text-xl mr-2 text-red-500 dark:text-red-300"></iconify-icon>
                <div>
                    <strong class="font-semibold">{{ __("Error!") }}</strong>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
                 <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700" data-dismiss-target="alert" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <iconify-icon icon="mdi:close" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>
    @endif

    {{-- Contenedor principal del chat --}}
    <div class="flex chat-height overflow-hidden relative bg-white dark:bg-slate-900 rounded-lg shadow-xl">

        {{-- Sidebar (Lista de Chats) --}}
        <div class="w-[300px] flex-none border-r border-slate-200 dark:border-slate-700 flex flex-col h-full">
            {{-- Header Sidebar --}}
            <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div id="connection-btn" class="flex-1">
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('Loading status...') }}</span>
                    </div>
                     <div id="connection-btn3" class="flex-none">
                        {{-- Botón Editar Auto Respuestas --}}
                    </div>
                </div>
                 <div id="connection-btn2" class="flex justify-center">
                     {{-- Botón Sincronizar Contactos --}}
                </div>
            </div>

            {{-- Buscador --}}
            <div class="p-3 border-b border-slate-100 dark:border-slate-700">
                <div class="relative">
                    <input type="text" id="contactSearch" placeholder="{{ __('Search chats...') }}" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-full py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
                        <iconify-icon icon="heroicons:magnifying-glass"></iconify-icon>
                    </div>
                </div>
            </div>

            {{-- Lista de Chats --}}
            <div id="chat-list-container" class="flex-1 overflow-y-auto">
                <ul id="chat-list" class="divide-y divide-slate-100 dark:divide-slate-700">
                    <li class="p-4 text-center text-slate-500 dark:text-slate-400 text-sm">{{ __('Loading chats...') }}</li>
                </ul>
            </div>
        </div>

        {{-- Área Principal del Chat --}}
        <div class="flex-1 flex flex-col h-full">
            {{-- Header Chat Activo --}}
            <div id="chat-header" class="flex items-center p-4 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 h-[73px]">
                 <div class="flex items-center">
                    <iconify-icon icon="heroicons:user-circle" class="w-10 h-10 text-slate-400 dark:text-slate-500 mr-3 chat-header-avatar"></iconify-icon> {{-- Added class --}}
                    <div>
                        <h2 id="chat-name" class="text-base font-medium text-slate-800 dark:text-slate-100">{{ __('Select a chat') }}</h2>
                        <p id="chat-status" class="text-xs text-slate-500 dark:text-slate-400">{{ __('to start messaging') }}</p>
                    </div>
                 </div>
            </div>

            {{-- Contenedor de Mensajes --}}
            <div id="chat-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4 bg-slate-100/50 dark:bg-slate-900/50">
                <div class="flex justify-center items-center h-full">
                    <div class="text-center text-slate-500 dark:text-slate-400">
                        <iconify-icon icon="mdi:message-text-outline" class="text-6xl mb-4"></iconify-icon>
                        <p>{{ __('Messages will appear here.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Área de Envío --}}
            <div id="send-message-container" class="p-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <textarea id="tiny-editor" placeholder="{{ __('Write a message...') }}"></textarea>
                    </div>
                    <button id="sendMessageButton" class="btn btn-primary rounded-full flex-none inline-flex items-center justify-center w-12 h-12 p-0" title="{{ __('Send') }}">
                        <iconify-icon icon="mdi:send" class="text-xl"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <style>
            .chat-height {
                height: calc(100vh - 120px);
                min-height: 500px;
            }

            #chat-list .chat-item.active {
                background-color: #3b82f6;
                color: white;
            }
             #chat-list .chat-item.active .chat-item-name,
             #chat-list .chat-item.active .chat-item-lastmsg,
             #chat-list .chat-item.active .chat-item-time { /* Added time */
                 color: white !important; /* Use important to override default text colors */
             }
             /* Style for avatar text in active state */
             #chat-list .chat-item.active .chat-avatar-initials {
                  background-color: rgba(255, 255, 255, 0.3); /* Lighter background for initials */
                  color: white;
             }

            .dark #chat-list .chat-item.active {
                background-color: #2563eb;
            }

            #chat-list .chat-item:hover {
                background-color: #f1f5f9;
            }
            .dark #chat-list .chat-item:hover {
                 background-color: #1e293b;
            }

            .message-bubble-wrapper { max-width: 75%; }
            .message-bubble {
                padding: 8px 12px;
                border-radius: 18px;
                word-wrap: break-word;
                position: relative;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
                min-width: 50px; /* Minimum width for small bubbles */
            }

            .message-received .message-bubble {
                background-color: #ffffff;
                border-bottom-left-radius: 4px;
                color: #1e293b;
            }
            .dark .message-received .message-bubble {
                 background-color: #334155;
                 color: #f1f5f9;
            }

            .message-sent .message-bubble {
                background-color: #dbeafe; /* Light blue */
                border-bottom-right-radius: 4px;
                color: #1e293b;
            }
             .dark .message-sent .message-bubble {
                 background-color: #1e40af; /* Darker blue */
                 color: #ffffff;
             }
             /* Add timestamp styles */
            .message-timestamp {
                font-size: 0.65rem; /* Smaller font size */
                line-height: 1;
                margin-top: 4px; /* Space above timestamp */
                user-select: none; /* Prevent selection */
                /* Float right inside the bubble */
                float: right;
                clear: both; /* Ensure it doesn't wrap around floated elements like images */
                margin-left: 8px; /* Space to the left if text is short */
            }
            /* Colors for timestamp */
            .message-received .message-timestamp { color: #94a3b8; } /* slate-400 */
            .dark .message-received .message-timestamp { color: #94a3b8; } /* slate-400 */
            .message-sent .message-timestamp { color: #60a5fa; } /* blue-400 */
            .dark .message-sent .message-timestamp { color: #93c5fd; } /* blue-300 */


            .message-bubble img.chat-image {
                max-width: 300px;
                max-height: 300px;
                border-radius: 12px;
                cursor: pointer;
                display: block;
                margin-bottom: 4px; /* Add space below image if text follows */
            }
            .message-bubble .video-thumbnail {
                 max-width: 300px;
                 max-height: 300px;
                 border-radius: 12px;
                 cursor: pointer;
                 display: block;
                 position: relative;
                 margin-bottom: 4px;
            }
             .message-bubble .video-thumbnail::after {
                 content: '';
                 position: absolute;
                 top: 50%; left: 50%;
                 transform: translate(-50%, -50%);
                 width: 50px; height: 50px;
                 background-color: rgba(0, 0, 0, 0.5);
                 border-radius: 50%;
                 background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white' width='24px' height='24px'%3E%3Cpath d='M8 5v14l11-7z'/%3E%3Cpath d='M0 0h24v24H0z' fill='none'/%3E%3C/svg%3E");
                 background-repeat: no-repeat; background-position: center; background-size: 24px;
                 opacity: 0.9; transition: opacity 0.2s ease;
             }
            .message-bubble .video-thumbnail:hover::after { opacity: 1; }
            /* Ensure text below media clears the media element */
            .message-text-content {
                clear: both;
            }

            #send-message-container .tox-tinymce { border-radius: 20px !important; border: 1px solid #e2e8f0 !important; }
             .dark #send-message-container .tox-tinymce { border-color: #334155 !important; }
            #send-message-container .tox .tox-edit-area__iframe { background-color: #f8fafc !important; }
            .dark #send-message-container .tox .tox-edit-area__iframe { background-color: #1e293b !important; }
            #send-message-container .tox .tox-statusbar { display: none !important; }
            #send-message-container .tox .tox-toolbar__primary {
                 background: #f8fafc !important; border-bottom: 1px solid #e2e8f0 !important;
                 border-top-left-radius: 20px; border-top-right-radius: 20px;
            }
            .dark #send-message-container .tox .tox-toolbar__primary {
                 background: #1e293b !important; border-bottom-color: #334155 !important;
            }

            #chat-list-container::-webkit-scrollbar,
            #chat-container::-webkit-scrollbar { width: 6px; }
            #chat-list-container::-webkit-scrollbar-track,
            #chat-container::-webkit-scrollbar-track { background: transparent; }
            #chat-list-container::-webkit-scrollbar-thumb,
            #chat-container::-webkit-scrollbar-thumb { background-color: rgba(0, 0, 0, 0.2); border-radius: 3px; }
            .dark #chat-list-container::-webkit-scrollbar-thumb,
            .dark #chat-container::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.2); }

            #connection-btn input[type="text"] { @apply w-full p-2 border border-slate-300 dark:border-slate-600 rounded-md text-sm dark:bg-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-blue-500 focus:border-blue-500 mb-2; }
            #connection-btn button, #connection-btn2 button, #connection-btn3 button { @apply w-full; }
             #connection-btn button.rounded-full,
             #connection-btn2 button.rounded-full,
             #connection-btn3 button.rounded-full { @apply w-10 h-10 p-0 inline-flex items-center justify-center; }

            .swal2-container.swal2-backdrop-show { background: rgba(0,0,0,0.6) !important; }
            .swal2-popup .swal2-html-container video { max-width: 100%; border-radius: 8px; }
            .swal2-popup .swal2-image { border-radius: 8px; }

            /* Style for chat item time */
            .chat-item-time {
                font-size: 0.7rem; /* text-xs */
                color: #94a3b8; /* text-slate-400 */
                white-space: nowrap; /* Prevent wrapping */
            }
            .dark .chat-item-time {
                color: #64748b; /* dark:text-slate-500 */
            }

        </style>
    @endpush

    {{-- Scripts --}}
    @push('scripts')
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>

        <script src="https://cdn.tiny.cloud/1/v6bk2mkmbxcn1oybhyu2892lyn9zykthl51xgkrh7ye0f7xv/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#tiny-editor',
                plugins: 'autoresize link image code emoticons',
                toolbar: 'bold italic | bullist numlist | link image emoticons | code',
                menubar: false,
                statusbar: false,
                autoresize_min_height: 50,
                autoresize_max_height: 200,
                autoresize_bottom_margin: 10,
                placeholder: "{{ __('Write a message...') }}",
                readonly: false,
                content_style: `
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; }
                    p { margin: 0 0 5px 0; }
                `,
                skin: (document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide'),
                content_css: (document.documentElement.classList.contains('dark') ? 'dark' : 'default')
            });

            const tinymceObserver = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        tinymce.editors.forEach(editor => {
                            editor.destroy();
                        });
                         tinymce.init({
                            selector: '#tiny-editor',
                            plugins: 'autoresize link image code emoticons',
                            toolbar: 'bold italic | bullist numlist | link image emoticons | code',
                            menubar: false, statusbar: false,
                            autoresize_min_height: 50, autoresize_max_height: 200, autoresize_bottom_margin: 10,
                            placeholder: "{{ __('Write a message...') }}", readonly: false,
                            content_style: `
                                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; }
                                p { margin: 0 0 5px 0; }
                            `,
                            skin: isDark ? 'oxide-dark' : 'oxide',
                            content_css: isDark ? 'dark' : 'default'
                        });
                    }
                });
            });
            tinymceObserver.observe(document.documentElement, { attributes: true });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // =======================================================
            // ========== Lógica JavaScript (Modificada) ==========
            // =======================================================
            var autoResponseConfig = {!! json_encode($autoResponseConfig ?? null) !!};
            let currentPeer = null;
            let messagesIntervalId = null;
            let currentMessageRequest = null;
            const userId = "{{ auth()->id() }}";

            /* ───────────────────────────────────────────────
            *  SweetAlert2 Toast sin parámetros incompatibles
            * ─────────────────────────────────────────────── */
            function buildToast () {                            // NUEVO
                const isDark = document.documentElement.classList.contains('dark');

                return Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,

                    /* Anular opciones que no acepta un toast */
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

                    customClass: {
                        popup: isDark ? 'dark swal2-toast-dark' : 'swal2-toast-light'
                    },
                    didOpen: toast => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            }

            let Toast = buildToast();                           // NUEVO

            const swalObserver = new MutationObserver(() => {   // REEMPLAZA TU OBSERVER
                const isDark = document.documentElement.classList.contains('dark');

                /* si hay alertas abiertas, ajusta su clase */
                document.querySelectorAll('.swal2-popup')
                        .forEach(p => p.classList.toggle('dark', isDark));
                document.querySelectorAll('.swal2-toast')
                        .forEach(t => {
                            t.classList.toggle('dark', isDark);
                            t.classList.toggle('swal2-toast-dark', isDark);
                            t.classList.toggle('swal2-toast-light', !isDark);
                        });

                /* recrea el mixin para siguientes Toast */
                Toast = buildToast();
            });

            swalObserver.observe(document.documentElement, { attributes: true });


            // --- Funciones de Utilidad ---
            function scrollChatToBottom(force = false) {
                const chatContainer = $('#chat-container');
                if (chatContainer.length) {
                    const scrollHeight = chatContainer[0].scrollHeight;
                    const currentScroll = chatContainer.scrollTop();
                    const containerHeight = chatContainer.innerHeight();
                    const threshold = 150;
                    if (force || (currentScroll + containerHeight >= scrollHeight - threshold)) {
                        chatContainer.stop().animate({ scrollTop: scrollHeight }, 300);
                    }
                }
            }

            function openZoomModal(src) {
                Swal.fire({
                    imageUrl: src, imageAlt: '{{ __("Image") }}', width: 'auto', heightAuto: false, padding: '1em',
                    showConfirmButton: false, showCloseButton: true, background: 'rgba(0,0,0,0.8)',
                    customClass: { popup: 'swal2-zoom-popup', image: 'swal2-zoom-image', closeButton: 'swal2-zoom-close-button' }
                });
            }

            function openVideoModal(videoSrc) {
                Swal.fire({
                    html: `<video controls autoplay style="width:100%; max-height:80vh; border-radius: 8px;"><source src="${videoSrc}" type="video/mp4">{{ __("Your browser does not support the video tag.") }}</video>`,
                    width: '80%', padding: '1em', showConfirmButton: false, showCloseButton: true, background: 'rgba(0,0,0,0.8)',
                    customClass: { popup: 'swal2-zoom-popup', closeButton: 'swal2-zoom-close-button' }
                });
            }

            // Función para formatear timestamp (ej: 10:30, Ayer, 25/12)
            function formatTimestampForList(timestamp) {
                if (!timestamp) return '';
                const date = new Date(timestamp * 1000);
                const now = new Date();
                const yesterday = new Date(now);
                yesterday.setDate(now.getDate() - 1);

                const isToday = date.toDateString() === now.toDateString();
                const isYesterday = date.toDateString() === yesterday.toDateString();

                if (isToday) {
                    return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
                } else if (isYesterday) {
                    return '{{ __("Yesterday") }}';
                } else {
                    // Formato dd/mm para otras fechas
                    const day = date.getDate().toString().padStart(2, '0');
                    const month = (date.getMonth() + 1).toString().padStart(2, '0'); // Meses son 0-indexados
                    return `${day}/${month}`;
                }
            }
             // Función para formatear timestamp para burbuja de mensaje (HH:MM)
            function formatTimestampForBubble(timestamp) {
                if (!timestamp) return '';
                const date = new Date(timestamp * 1000);
                return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
            }


            // --- Funciones Principales del Chat ---

            // Función para actualizar la lista de chats (MODIFICADA PARA ORDENAR POR FECHA)
            function fetchChats() {
                $.ajax({
                    url: `/telegram/get-chat/${userId}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log("Chats fetched:", response);
                        let html = '';
                        const chatList = $('#chat-list');
                        chatList.empty();

                        if (response.chats && response.chats.length > 0) {
                             // *** ORDENAR POR FECHA DESCENDENTE ***
                             // Asume que cada chat tiene 'last_message_timestamp'
                             response.chats.sort((a, b) => {
                                 const dateA = a.last_message_timestamp || 0;
                                 const dateB = b.last_message_timestamp || 0;
                                 return dateB - dateA; // Descendente (más nuevo primero)
                             });

                            response.chats.forEach(chat => {
                                const isActive = chat.id === currentPeer;
                                const chatName = $('<div>').text(chat.name || 'Unknown Chat').html();
                                const chatInitial = chatName.substring(0, 1).toUpperCase();
                                // Formatear timestamp para la lista
                                const formattedTime = formatTimestampForList(chat.last_message_timestamp);

                                html = `
                                    <li class="chat-item flex items-center p-3 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition duration-150 ${isActive ? 'active' : ''}" data-peer="${chat.id}">
                                        <div class="flex-none mr-3">
                                            <div class="chat-avatar-initials w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 font-medium">
                                                ${chatInitial}
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-hidden">
                                            <p class="chat-item-name text-sm font-medium text-slate-800 dark:text-slate-200 truncate">${chatName}</p>
                                            {{-- Puedes añadir el último mensaje si lo devuelve la API --}}
                                            {{-- <p class="chat-item-lastmsg text-xs text-slate-500 dark:text-slate-400 truncate">${chat.last_message_text || ''}</p> --}}
                                        </div>
                                        {{-- Mostrar hora formateada --}}
                                        <span class="chat-item-time text-xs text-slate-400 dark:text-slate-500 ml-2 whitespace-nowrap">${formattedTime}</span>
                                    </li>
                                `;
                                chatList.append(html);
                            });
                        } else {
                            chatList.html('<li class="p-4 text-center text-slate-500 dark:text-slate-400 text-sm">{{ __("No chats found") }}</li>');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error fetching chats:', textStatus, errorThrown);
                         $('#chat-list').html('<li class="p-4 text-center text-red-500 dark:text-red-400 text-sm">{{ __("Error loading chats") }}</li>');
                    }
                });
            }

            // Función para obtener los mensajes de un chat específico (MODIFICADA PARA MOSTRAR HORA)
            function fetchMessages(peer) {
                if (currentMessageRequest) { currentMessageRequest.abort(); console.log("Previous message request aborted"); }

                const chatContainer = $('#chat-container');
                const scrollHeightBefore = chatContainer.length ? chatContainer[0].scrollHeight : 0;
                const currentScroll = chatContainer.length ? chatContainer.scrollTop() : 0;
                const containerHeight = chatContainer.length ? chatContainer.innerHeight() : 0;
                const threshold = 150;
                const wasNearBottom = currentScroll + containerHeight >= scrollHeightBefore - threshold;

                console.log(`Fetching messages for peer: ${peer}`);

                currentMessageRequest = $.ajax({
                    url: `/telegram/get-messages/${userId}/${peer}`,
                    type: 'GET', dataType: 'json', timeout: 45000,
                    success: function(response) {
                        console.log("Messages received:", response);
                        let messageHtml = '';
                        const chatHeaderName = $('#chat-name');
                        const chatHeaderStatus = $('#chat-status');
                        const chatHeaderAvatar = $('.chat-header-avatar'); // Selector for avatar icon

                        // Actualizar header del chat
                        const currentChatData = response.chat_info || {}; // Assuming backend sends chat info
                        const currentChatListItem = $(`#chat-list .chat-item[data-peer="${peer}"]`);

                        const chatName = currentChatData.name || (currentChatListItem.length ? currentChatListItem.find('.chat-item-name').text() : `Chat ${peer}`);
                        const chatStatusText = currentChatData.status || ''; // e.g., 'online', 'last seen...'

                        chatHeaderName.text(chatName);
                        chatHeaderStatus.text(chatStatusText);
                        // Update avatar placeholder in header
                        chatHeaderAvatar.replaceWith(
                            `<div class="chat-header-avatar w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 font-medium mr-3">
                                ${chatName.substring(0, 1).toUpperCase()}
                             </div>`
                        );


                        if (response.messages && response.messages.length > 0) {
                            let sortedMessages = response.messages.sort((a, b) => a.date - b.date);

                            sortedMessages.forEach(msg => {
                                const isSent = msg.status === 'sent';
                                const alignmentClass = isSent ? 'justify-end' : 'justify-start';
                                const bubbleWrapperClass = isSent ? 'message-sent ml-auto' : 'message-received mr-auto';
                                let mediaHtml = '';
                                const formattedTime = formatTimestampForBubble(msg.date); // Formatear hora para burbuja

                                // Manejo de Media (igual que antes)
                                if (msg.hasMedia) {
                                    const downloadUrl = `/telegram/download-media/${userId}/${msg.chatPeer}/${msg.messageId}`;
                                    if (msg.mediaType === 'image') {
                                        mediaHtml = `<img src="${downloadUrl}" alt="{{ __('Image') }}" class="chat-image max-w-full h-auto rounded-lg cursor-pointer" onclick="openZoomModal('${downloadUrl}')">`;
                                    } else if (msg.mediaType === 'video') {
                                        mediaHtml = `<div class="relative video-thumbnail bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center cursor-pointer" onclick="openVideoModal('${downloadUrl}')" style="width: 250px; height: 150px;"><iconify-icon icon="mdi:play-circle-outline" class="text-4xl text-white z-10"></iconify-icon></div>`;
                                    } else if (msg.mediaType === 'audio') {
                                        mediaHtml = `<audio controls class="w-full max-w-xs"><source src="${downloadUrl}" type="audio/mpeg">{{ __("Your browser does not support the audio tag.") }}</audio>`;
                                    } else if (msg.mediaType === 'location') {
                                        const mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(msg.message || 'Location')}`;
                                        mediaHtml = `<a href="${mapUrl}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md"><iconify-icon icon="mdi:map-marker-outline" class="mr-1"></iconify-icon>{{ __('View Location') }}</a>`;
                                    } else {
                                        const fileName = msg.fileName || 'Download File';
                                        mediaHtml = `<a href="${downloadUrl}" download="${fileName}" class="inline-flex items-center px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-md"><iconify-icon icon="mdi:download-outline" class="mr-1"></iconify-icon>${$('<div>').text(fileName).html()}</a>`;
                                    }
                                }

                                // Mensaje de texto
                                const messageText = msg.message ? $('<div>').text(msg.message).html().replace(/\n/g, '<br>') : '';

                                // Construir HTML del mensaje con hora
                                messageHtml += `
                                    <div class="flex ${alignmentClass} w-full">
                                        <div class="message-bubble-wrapper ${bubbleWrapperClass}">
                                            <div class="message-bubble">
                                                ${mediaHtml}
                                                ${messageText ? `<div class="message-text-content ${mediaHtml ? 'mt-1' : ''}">${messageText}</div>` : ''}
                                                {{-- Añadir timestamp flotando a la derecha --}}
                                                <span class="message-timestamp">${formattedTime}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            messageHtml = `<div class="flex justify-center items-center h-full"><div class="text-center text-slate-500 dark:text-slate-400"><iconify-icon icon="mdi:message-text-outline" class="text-6xl mb-4"></iconify-icon><p>{{ __('No messages in this chat yet.') }}</p></div></div>`;
                        }

                        chatContainer.html(messageHtml);

                        setTimeout(() => {
                             const newScrollHeight = chatContainer[0].scrollHeight;
                             if (wasNearBottom) {
                                 console.log("Scrolling to bottom because user was near bottom.");
                                 chatContainer.scrollTop(newScrollHeight);
                             } else {
                                 const heightDiff = newScrollHeight - scrollHeightBefore;
                                 if(heightDiff > 0) {
                                     console.log(`Maintaining scroll position. Height diff: ${heightDiff}`);
                                     chatContainer.scrollTop(currentScroll + heightDiff);
                                 }
                             }
                        }, 100);

                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (textStatus !== 'abort') {
                            console.error('Error fetching messages:', textStatus, errorThrown, jqXHR.responseText);
                            chatContainer.html('<div class="p-4 text-center text-red-500 dark:text-red-400">{{ __("Error loading messages.") }}</div>');
                            chatHeaderName.text('{{ __("Error") }}');
                            chatHeaderStatus.text('{{ __("Could not load chat") }}');
                        }
                    },
                    complete: function() {
                        currentMessageRequest = null;
                        console.log("Message fetch request completed.");
                    }
                });
            }

            // --- Event Handlers ---
            $(document).on('keyup', '#contactSearch', function() {
                let searchTerm = $(this).val().toLowerCase().trim();
                $('#chat-list li.chat-item').each(function() {
                    let chatName = $(this).find('.chat-item-name').text().toLowerCase();
                    $(this).toggle(chatName.includes(searchTerm));
                });
            });

            function startMessagesAutoRefresh(peer) {
                if (messagesIntervalId) { clearInterval(messagesIntervalId); console.log("Cleared previous message refresh interval"); }
                 if (peer) {
                    console.log(`Starting auto-refresh for peer ${peer} every 30s`);
                    messagesIntervalId = setInterval(() => { console.log(`Auto-refreshing messages for peer ${peer}`); fetchMessages(peer); }, 30000);
                }
            }

            $(document).on('click', '#chat-list .chat-item', function() {
                const peer = $(this).data('peer');
                if (peer && peer !== currentPeer) {
                    console.log(`Chat item clicked, peer: ${peer}`);
                    currentPeer = peer;
                    $('#chat-list .chat-item').removeClass('active');
                    $(this).addClass('active');
                    $('#chat-container').html(`<div class="flex justify-center items-center h-full"><iconify-icon icon="line-md:loading-loop" class="text-4xl text-slate-500 dark:text-slate-400"></iconify-icon></div>`);
                    $('#chat-name').text('{{ __("Loading...") }}');
                    $('#chat-status').text('');
                    fetchMessages(peer);
                    startMessagesAutoRefresh(peer);
                } else {
                     console.log("Clicked on the already active chat or invalid peer.");
                }
            });

            // --- Session Management Functions (No changes needed in logic) ---
            function checkSessionStatus() {
                console.log("Checking session status...");
                fetch(`/telegram/session-status/${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log("Session status response:", data);
                        const connectionBtn = $('#connection-btn');
                        const connectionBtn2 = $('#connection-btn2');
                        const connectionBtn3 = $('#connection-btn3');
                        connectionBtn.empty(); connectionBtn2.empty(); connectionBtn3.empty();

                        if (data.error) {
                            connectionBtn.html(`<input type="text" id="phone" placeholder="{{ __('Enter phone number') }}" class="input-session"><button id="start-session-btn" class="btn btn-success btn-sm w-full mt-1">{{ __('Start Session') }}</button>`);
                            $('#start-session-btn').on('click', startSession);
                        } else if (!data.isValidated) {
                             connectionBtn.html(`<input type="text" id="pin-code" placeholder="{{ __('Enter code') }}" class="input-session"><button id="verify-code-btn" class="btn btn-success btn-sm w-full mt-1">{{ __('Verify Code') }}</button>`);
                             connectionBtn2.html(`<button id="logout-btn" class="btn btn-danger btn-sm light" title="{{ __('Reset Session') }}"><iconify-icon icon="mdi:logout-variant" class="mr-1"></iconify-icon> {{ __('Reset') }}</button>`);
                            $('#verify-code-btn').on('click', inputPinCode);
                            $('#logout-btn').on('click', logout);
                        } else {
                            connectionBtn.html(`<button id="logout-btn" class="btn btn-danger light rounded-full w-10 h-10 p-0 inline-flex items-center justify-center" title="{{ __('Logout') }}"><iconify-icon icon="mdi:logout-variant" class="text-xl"></iconify-icon></button>`);
                            connectionBtn2.html(`<button id="sync-contacts" class="btn btn-primary light rounded-full w-10 h-10 p-0 inline-flex items-center justify-center" title="{{ __('Sync Contacts') }}"><iconify-icon icon="mdi:sync" class="text-xl"></iconify-icon></button>`);
                            connectionBtn3.html(`<button id="edit-auto-responses-btn" class="btn btn-secondary light rounded-full w-10 h-10 p-0 inline-flex items-center justify-center" title="{{ __('Auto Responses') }}"><iconify-icon icon="mdi:robot-outline" class="text-xl"></iconify-icon></button>`);
                            $('#logout-btn').on('click', logout);
                            $('#sync-contacts').on('click', syncContactsFromTelegramToDatabase);
                            $('#edit-auto-responses-btn').on('click', handleEditAutoResponses);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking session status:', error);
                        $('#connection-btn').html('<span class="text-red-500 text-xs">{{ __("Error checking status") }}</span>');
                    });
            }

            function startSession() {
                let phone = $('#phone').val().trim();
                if (!phone) { Toast.fire({ icon: 'warning', title: '{{ __("Please enter a phone number") }}' }); return; }
                console.log(`Starting session for phone: ${phone}`);
                $('#start-session-btn').prop('disabled', true).html('<iconify-icon icon="line-md:loading-loop"></iconify-icon>');
                $.ajax({
                    url: `/telegram/request-code/${userId}`, type: 'POST', data: { phone: phone, _token: '{{ csrf_token() }}' },
                    success: function(response) { Toast.fire({ icon: 'success', title: '{{ __("Verification code sent!") }}' }); checkSessionStatus(); },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorMsg = jqXHR.responseJSON?.error || '{{ __("Failed to send verification code.") }}';
                        Swal.fire({ title: '{{ __("Error") }}', text: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                        checkSessionStatus();
                    }
                });
            }

            function inputPinCode() {
                let pinCode = $('#pin-code').val().trim();
                 if (!pinCode) { Toast.fire({ icon: 'warning', title: '{{ __("Please enter the verification code") }}' }); return; }
                console.log(`Verifying code: ${pinCode}`);
                 $('#verify-code-btn').prop('disabled', true).html('<iconify-icon icon="line-md:loading-loop"></iconify-icon>');
                $.ajax({
                    url: `/telegram/verify-code/${userId}`, type: 'POST', data: { code: pinCode, _token: '{{ csrf_token() }}' },
                    success: function(response) { Toast.fire({ icon: 'success', title: '{{ __("Code verified successfully!") }}' }); checkSessionStatus(); fetchChats(); },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorMsg = jqXHR.responseJSON?.error || '{{ __("Failed to verify the code.") }}';
                        Swal.fire({ title: '{{ __("Error") }}', text: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                        checkSessionStatus();
                    }
                });
            }

            function logout() {
                 console.log("Logging out...");
                 Swal.fire({
                    title: '{{ __("Are you sure?") }}', text: "{{ __('This will log out your Telegram session.') }}", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6b7280', confirmButtonText: '{{ __("Yes, log out") }}', cancelButtonText: '{{ __("Cancel") }}',
                    customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#logout-btn').prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                        $.ajax({
                            url: `/telegram/logout/${userId}`, type: 'POST', data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Toast.fire({ icon: 'success', title: '{{ __("Logged out successfully!") }}' });
                                currentPeer = null; $('#chat-list').empty();
                                $('#chat-container').html(`<div class="flex justify-center items-center h-full"><div class="text-center text-slate-500 dark:text-slate-400"><p>{{ __('Session closed. Connect again to see chats.') }}</p></div></div>`);
                                $('#chat-name').text('{{ __("Select a chat") }}'); $('#chat-status').text('{{ __("to start messaging") }}');
                                if (messagesIntervalId) clearInterval(messagesIntervalId);
                                checkSessionStatus();
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                const errorMsg = jqXHR.responseJSON?.error || '{{ __("Failed to log out.") }}';
                                Swal.fire({ title: '{{ __("Error") }}', text: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                checkSessionStatus();
                            }
                        });
                    }
                });
            }

            function syncContactsFromTelegramToDatabase() {
                 console.log("Syncing contacts...");
                 const syncBtn = $('#sync-contacts'); const originalIcon = syncBtn.find('iconify-icon').attr('icon');
                 syncBtn.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                $.ajax({
                    url: `/telegram/sync-contacts/${userId}`, type: 'GET', data: { _token: '{{ csrf_token() }}' },
                    success: function(response) { Toast.fire({ icon: 'success', title: response.message || '{{ __("Contacts synced successfully!") }}' }); fetchChats(); },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorMsg = jqXHR.responseJSON?.error || jqXHR.responseJSON?.message || '{{ __("Failed to sync contacts.") }}';
                        Swal.fire({ title: '{{ __("Error") }}', text: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                    },
                    complete: function() { syncBtn.prop('disabled', false).find('iconify-icon').attr('icon', originalIcon); }
                });
            }

            // --- Auto Response Handler ---
            function handleEditAutoResponses() {
                console.log("Opening auto-response settings...");
                const editBtn = $('#edit-auto-responses-btn'); const originalIcon = editBtn.find('iconify-icon').attr('icon');
                editBtn.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                $.ajax({
                    url: '/auto-response-telegram-get', type: 'GET', dataType: 'json', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success && response.data) {
                            const config = response.data;
                            const currentTelegram = config.telegram !== undefined ? String(config.telegram) : '0';
                            const currentPrompt = config.telegram_prompt || '';
                            Swal.fire({
                                title: '{{ __("Edit Telegram Auto Responses") }}',
                                html: `
                                    <div class="space-y-4 text-left p-2">
                                        <div>
                                            <label for="swal-telegram" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Mode") }}</label>
                                            <select id="swal-telegram" class="swal2-select w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-md focus:border-blue-500 focus:ring-blue-500">
                                                <option value="0" ${currentTelegram === '0' ? 'selected' : ''}>{{ __("Deactivated") }}</option>
                                                <option value="1" ${currentTelegram === '1' ? 'selected' : ''}>{{ __("Auto text") }}</option>
                                                <option value="2" ${currentTelegram === '2' ? 'selected' : ''}>{{ __("With AI") }}</option>
                                                <option value="3" ${currentTelegram === '3' ? 'selected' : ''}>{{ __("With ticket") }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="swal-telegram-prompt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Prompt / Auto Text") }}</label>
                                            <textarea id="swal-telegram-prompt" class="swal2-textarea w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 rounded-md focus:border-blue-500 focus:ring-blue-500" rows="4" placeholder="{{ __('Enter the text or AI prompt here...') }}">${currentPrompt}</textarea>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __("Required if mode is not 'Deactivated'.") }}</p>
                                        </div>
                                    </div>`,
                                focusConfirm: false, showCancelButton: true, confirmButtonText: '{{ __("Save Changes") }}', cancelButtonText: '{{ __("Cancel") }}',
                                customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' },
                                preConfirm: () => {
                                    const telegramMode = $('#swal-telegram').val(); const promptText = $('#swal-telegram-prompt').val().trim();
                                    if (telegramMode !== '0' && !promptText) {
                                        Swal.showValidationMessage('{{ __("The prompt/text is required for the selected mode.") }}');
                                        $('#swal-telegram-prompt').addClass('border-red-500').focus(); return false;
                                    }
                                    $('#swal-telegram-prompt').removeClass('border-red-500');
                                    return { telegram: telegramMode, telegram_prompt: promptText };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const dataToSave = result.value;
                                    $.ajax({
                                        url: '/auto-response-telegram', type: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, data: dataToSave,
                                        success: function(saveResponse) { Toast.fire({ icon: 'success', title: saveResponse.message || '{{ __("Configuration updated successfully!") }}' }); },
                                        error: function(jqXHR, textStatus, errorThrown) {
                                            const errorMsg = jqXHR.responseJSON?.error || jqXHR.responseJSON?.message || '{{ __("Error updating configuration.") }}';
                                            Swal.fire({ title: '{{ __("Error") }}', text: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                        }
                                    });
                                }
                            });
                        } else { throw new Error(response.error || '{{ __("Could not load configuration data.") }}'); }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("Unable to retrieve current configuration.") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                    },
                    complete: function() { editBtn.prop('disabled', false).find('iconify-icon').attr('icon', originalIcon); }
                });
            }

            // --- Send Message Handler ---
            $(document).on('click', '#sendMessageButton', function(e) {
                e.preventDefault();
                if (!currentPeer) { Toast.fire({ icon: 'warning', title: '{{ __("Please select a chat first") }}' }); return; }
                let messageContent = tinymce.get('tiny-editor').getContent();
                let messageText = tinymce.get('tiny-editor').getContent({ format: 'text' }).trim();
                if (!messageText) { Toast.fire({ icon: 'warning', title: '{{ __("Cannot send an empty message") }}' }); return; }

                const sendButton = $(this); const originalIcon = sendButton.find('iconify-icon').attr('icon');
                sendButton.prop('disabled', true).find('iconify-icon').attr('icon', 'line-md:loading-loop');
                $.ajax({
                    url: `/telegram/send-message/${userId}/${currentPeer}`, type: 'POST', data: { message: messageContent, _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            tinymce.get('tiny-editor').setContent('');
                            fetchMessages(currentPeer);
                            setTimeout(() => scrollChatToBottom(true), 200);
                        } else {
                            Swal.fire({ title: '{{ __("Error") }}', text: response.error || '{{ __("Failed to send message.") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorMsg = jqXHR.responseJSON?.error || jqXHR.responseJSON?.message || '{{ __("Failed to send message.") }}';
                         Swal.fire({ title: '{{ __("Error") }}', text: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                    },
                    complete: function() { sendButton.prop('disabled', false).find('iconify-icon').attr('icon', originalIcon); }
                });
            });


            // --- Initialization ---
            $(document).ready(function() {
                console.log("Document ready, initializing chat interface...");
                checkSessionStatus();
                fetchChats(); // Cargar lista inicial (se ordenará por fecha)
                setInterval(() => { console.log("Auto-refreshing chat list"); fetchChats(); }, 30000); // Auto-refresh lista de chats
            });

        </script>
    @endpush
</x-app-layout>
