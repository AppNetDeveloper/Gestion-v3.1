<x-app-layout>
    {{-- Sección HEAD: meta tags --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    @php
        // Función para convertir CSV a imagen (base64)
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

    {{-- Mensajes flash --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">{{ __("Success!") }}</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">{{ __("Error!") }}</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="space-y-8">
        <div class="flex lg:space-x-5 chat-height overflow-hidden relative rtl:space-x-reverse">
            <!-- Sidebar: Contactos y Conexión -->
            <div class="chat-contact-bar">
                <div class="h-full card">
                    <div class="card-body relative p-0 h-full overflow-hidden">
                        <!-- Perfil y Conexión/Desconexión -->
                        <div class="border-b border-slate-100 dark:border-slate-700 pb-4">
                            <div class="p-3">
                                <div id="connection-btn" class="text-center">
                                    <!-- Aquí se muestra el estado de la conexión -->
                                </div>
                                <div id="connection-btn2" class="text-center"></div>
                            </div>
                        </div>
                        <!-- Buscador de Contactos -->
                        <div class="border-b border-slate-100 dark:border-slate-700 py-1">
                            <div class="search px-3 mx-6 rounded flex items-center space-x-3 rtl:space-x-reverse">
                                <div class="flex-none text-base text-slate-900 dark:text-slate-400">
                                    <iconify-icon icon="bytesize:search"></iconify-icon>
                                </div>
                                <input type="text" id="contactSearch" placeholder="{{ __('Search...') }}" class="w-full flex-1 block bg-transparent placeholder:font-normal placeholder:text-slate-400 py-2 focus:ring-0 focus:outline-none dark:text-slate-200 dark:placeholder:text-slate-400">
                            </div>
                        </div>
                        <!-- Lista de Chats -->
                        <div id="chat-list-container" class="py-3 px-6" style="max-height: 70%; overflow-y: auto;">
                            <h3 class="font-bold mb-4">{{ __("Chats") }}</h3>
                            <ul id="chat-list" class="list-group space-y-2">
                                <!-- Se cargarán dinámicamente -->
                            </ul>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Área principal del chat -->
            <div class="flex-1">
                <div class="parent flex flex-col h-full rtl:space-x-reverse">
                    <div class="flex-1">
                        <div class="h-full card">
                            <div class="p-0 h-full body-class">
                                <div id="chat-container" class="flex flex-col space-y-4 overflow-y-auto h-96 p-4">
                                    <!-- Los mensajes del chat seleccionado se cargarán aquí -->
                                </div>

                                <!-- Formulario para enviar mensaje vía AJAX con TinyMCE -->
                                <div id="send-message-container" class="mt-4 p-4 border-t">
                                    <textarea id="tiny-editor" placeholder="{{ __('Type your message...') }}" class="border rounded p-2 w-full" style="height: 150px;"></textarea>
                                    <button id="sendMessageButton" class="btn btn-primary mt-2">{{ __('Send') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <style>
            .chat-height {
                height: calc(100vh - 100px);
            }
            .message-bubble {
                padding: 10px;
                border-radius: 10px;
                max-width: 80%;
                word-break: break-word;
                overflow-wrap: break-word;
            }
        </style>
    @endpush

    {{-- Scripts --}}
    @push('scripts')
        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <!-- Configuración de AJAX para CSRF -->
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>

        <!-- TinyMCE -->
        <script src="https://cdn.tiny.cloud/1/v6bk2mkmbxcn1oybhyu2892lyn9zykthl51xgkrh7ye0f7xv/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#tiny-editor',
                plugins: 'autoresize link image code',
                toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | code',
                autoresize_min_height: 150,
                autoresize_max_height: 500,
                autoresize_bottom_margin: 16,
                readonly: false
            });
        </script>

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            var autoResponseConfig = {!! json_encode($autoResponseConfig ?? null) !!};

            // Función para desplazar el chat hacia abajo
            function scrollChatToBottom() {
                var chatContainer = document.getElementById('chat-container');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }

            // Función para abrir modal de imagen
            function openZoomModal(src) {
                Swal.fire({
                    imageUrl: src,
                    imageAlt: 'Imagen',
                    width: '80%',
                    showConfirmButton: false,
                    background: 'transparent'
                });
            }

            // Función para abrir modal de video
            function openVideoModal(videoSrc) {
                Swal.fire({
                    html: `<video controls style="width:100%; max-height:80vh;">
                                <source src="${videoSrc}" type="video/mp4">
                                Tu navegador no soporta el elemento de video.
                           </video>`,
                    width: '80%',
                    showConfirmButton: false,
                    background: 'transparent'
                });
            }

            // Función para actualizar la lista de chats
            function fetchChats() {
                $.ajax({
                    url: '/telegram/get-chat/{{ auth()->id() }}',
                    type: 'GET',
                    success: function(response) {
                        console.log("Chats:", response);
                        let html = '';
                        if (response.chats && response.chats.length > 0) {
                            response.chats.forEach(chat => {
                                html += `<li class="chat-item list-group-item cursor-pointer" data-peer="${chat.id}">
                                            ${chat.name}
                                        </li>`;
                            });
                        } else {
                            html = '<li class="list-group-item">No chats found</li>';
                        }
                        $('#chat-list').html(html);
                    },
                    error: function(error) {
                        console.error('Error fetching chats:', error);
                    }
                });
            }

            // Variables globales para chat activo y actualización de mensajes
            let currentPeer = null;
            let messagesIntervalId = null;
            let currentMessageRequest = null;

            // Función para obtener los mensajes de un chat específico
            function fetchMessages(peer) {
                // Si hay una petición en curso, abortarla
                if (currentMessageRequest) {
                    currentMessageRequest.abort();
                }

                // Guardar información del scroll antes de actualizar
                const chatContainer = $('#chat-container');
                const currentScroll = chatContainer.scrollTop();
                const scrollHeightBefore = chatContainer[0].scrollHeight;
                const containerHeight = chatContainer.innerHeight();
                const threshold = 100; // píxeles

                currentMessageRequest = $.ajax({
                    url: '/telegram/get-messages/{{ auth()->id() }}/' + peer,
                    type: 'GET',
                    timeout: 30000, // 30 segundos
                    success: function(response) {
                        console.log("Messages:", response);
                        let html = '';
                        if (response.messages && response.messages.length > 0) {
                            // Ordenar mensajes por date (ascendente: los más antiguos primero)
                            let sortedMessages = response.messages.sort((a, b) => a.date - b.date);

                            sortedMessages.forEach(msg => {
                                // Alineación: "sent" a la derecha, "received" a la izquierda
                                let alignmentClass = msg.status === 'sent' ? 'justify-end' : 'justify-start';

                                if (msg.hasMedia) {
                                    if (msg.mediaType === 'image') {
                                        let downloadUrl = `/telegram/download-media/{{ auth()->id() }}/${msg.chatPeer}/${msg.messageId}`;
                                        html += `<div class="flex ${alignmentClass} mb-2">
                                                    <div class="message-bubble">
                                                        <img src="${downloadUrl}"
                                                            alt="Imagen"
                                                            class="chat-image cursor-pointer"
                                                            onclick="openZoomModal('${downloadUrl}')">
                                                    </div>
                                                </div>`;
                                    } else if (msg.mediaType === 'video') {
                                        let downloadUrl = `/telegram/download-media/{{ auth()->id() }}/${msg.chatPeer}/${msg.messageId}`;
                                        html += `<div class="flex ${alignmentClass} mb-2">
                                                    <div class="message-bubble">
                                                        <button class="btn btn-sm btn-primary" onclick="openVideoModal('${downloadUrl}')">
                                                            Ver Video
                                                        </button>
                                                    </div>
                                                </div>`;
                                    } else if (msg.mediaType === 'audio') {
                                        let downloadUrl = `/telegram/download-media/{{ auth()->id() }}/${msg.chatPeer}/${msg.messageId}`;
                                        html += `<div class="flex ${alignmentClass} mb-2">
                                                    <div class="message-bubble">
                                                        <audio controls>
                                                            <source src="${downloadUrl}" type="audio/mpeg">
                                                            Tu navegador no soporta el elemento de audio.
                                                        </audio>
                                                    </div>
                                                </div>`;
                                    } else if (msg.mediaType === 'location') {
                                        html += `<div class="flex ${alignmentClass} mb-2">
                                                    <div class="message-bubble">
                                                        <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(msg.message)}" target="_blank" class="btn btn-info">
                                                            Ver Ubicación
                                                        </a>
                                                    </div>
                                                </div>`;
                                    } else {
                                        // Para documentos u otro tipo de media
                                        let downloadUrl = `/telegram/download-media/{{ auth()->id() }}/${msg.chatPeer}/${msg.messageId}`;
                                        html += `<div class="flex ${alignmentClass} mb-2">
                                                    <div class="message-bubble">
                                                        <a href="${downloadUrl}" download class="btn btn-secondary">
                                                            Descargar Archivo
                                                        </a>
                                                    </div>
                                                </div>`;
                                    }
                                } else {
                                    // Mensaje de texto simple
                                    html += `<div class="flex ${alignmentClass} mb-2">
                                                <div class="message-bubble">
                                                    ${msg.message}
                                                </div>
                                            </div>`;
                                }
                            });
                        } else {
                            html = '<p>No messages found for this chat</p>';
                        }

                        // Actualizar el contenedor de mensajes
                        chatContainer.html(html);

                        // Calcular la nueva altura del scroll
                        const newScrollHeight = chatContainer[0].scrollHeight;

                        // Si el usuario estaba cerca del final, bajar hasta el final
                        if (currentScroll + containerHeight >= scrollHeightBefore - threshold) {
                            chatContainer.scrollTop(newScrollHeight);
                        } else {
                            // Si no, mantener la posición actual ajustada con la diferencia
                            const heightDiff = newScrollHeight - scrollHeightBefore;
                            chatContainer.scrollTop(currentScroll + heightDiff);
                        }
                    },
                    error: function(error) {
                        if (error.statusText !== 'abort') { // Ignorar si fue abortada
                            console.error('Error fetching messages:', error);
                        }
                    },
                    complete: function() {
                        currentMessageRequest = null;
                    }
                });
            }

            // Evento para filtrar los chats al escribir en el buscador
            $(document).on('keyup', '#contactSearch', function() {
                let searchTerm = $(this).val().toLowerCase();

                // Iteramos sobre cada elemento de la lista de chats
                $('#chat-list li').each(function() {
                    let chatName = $(this).text().toLowerCase();

                    // Si el nombre del chat incluye el término de búsqueda, lo mostramos, de lo contrario se oculta.
                    if(chatName.indexOf(searchTerm) !== -1){
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Función para iniciar actualización automática de mensajes cada 30 segundos
            function startMessagesAutoRefresh(peer) {
                if (messagesIntervalId) {
                    clearInterval(messagesIntervalId);
                }
                messagesIntervalId = setInterval(() => {
                    fetchMessages(peer);
                }, 30000);
            }

            // Delegación de evento para cargar mensajes al hacer clic en un chat
            $(document).on('click', '.chat-item', function() {
                let peer = $(this).data('peer');
                currentPeer = peer;
                fetchMessages(peer);
                startMessagesAutoRefresh(peer);
            });

            // Actualizar lista de chats cada 30 segundos
            setInterval(fetchChats, 30000);

            // Funciones de sesión
            function checkSessionStatus() {
                fetch('/telegram/session-status/{{ auth()->id() }}')
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.error) {
                            $('#connection-btn').html(`
                                <input type="text" id="phone" placeholder="Enter phone number" class="w-full p-2">
                                <button id="start-session-btn" class="btn btn-success">
                                    {{ __('Start Session') }}
                                </button>
                            `);
                            $('#start-session-btn').click(function() {
                                startSession();
                            });
                            $('#connection-btn2').html(``);
                        } else {
                            if (data.isValidated) {
                                $('#connection-btn').html(`
                                    <button id="logout-btn" class="btn btn-danger">
                                        {{ __('Logout') }}
                                    </button>
                                `);
                                $('#logout-btn').click(function() {
                                    logout();
                                });
                                $('#connection-btn2').html(`
                                    <button id="sync-contacts" class="btn btn-primary">
                                        {{ __('SYNC CONTACTS FROM TELEGRAM TO DATABASE') }}
                                    </button>
                                `);
                                $('#sync-contacts').click(function() {
                                    syncContactsFromTelegramToDatabase();
                                });
                            } else {
                                $('#connection-btn').html(`
                                    <input type="text" id="pin-code" placeholder="Enter pin code" class="w-full p-2">
                                    <button id="start-session-btn" class="btn btn-success">
                                        {{ __('Insert pin code') }}
                                    </button>
                                `);
                                $('#start-session-btn').click(function() {
                                    inputPinCode();
                                });
                                $('#connection-btn2').html(`
                                    <button id="logout-btn" class="btn btn-danger">
                                        {{ __('RESET SESSION') }}
                                    </button>
                                `);
                                $('#logout-btn').click(function() {
                                    logout();
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error al verificar el estado de la sesión:', error);
                        Swal.fire('Error', 'No se pudo verificar el estado de la sesión.', 'error');
                    });
            }

            function startSession() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let phone = $('#phone').val();
                $.ajax({
                    url: '/telegram/request-code/{{ auth()->id() }}',
                    type: 'POST',
                    data: { phone: phone, _token: csrfToken },
                    success: function(response) {
                        checkSessionStatus();
                        Swal.fire('Success', 'Verification code sent!', 'success');
                    },
                    error: function(error) {
                        checkSessionStatus();
                        Swal.fire('Error', 'Failed to send verification code.', 'error');
                    }
                });
            }

            function inputPinCode() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let pinCode = $('#pin-code').val();
                $.ajax({
                    url: '/telegram/verify-code/{{ auth()->id() }}',
                    type: 'POST',
                    data: { code: pinCode, _token: csrfToken },
                    success: function(response) {
                        checkSessionStatus();
                        Swal.fire('Success', 'Code verified!', 'success');
                    },
                    error: function(error) {
                        checkSessionStatus();
                        Swal.fire('Error', 'Failed verify the code.', 'error');
                    }
                });
            }

            function logout() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: '/telegram/logout/{{ auth()->id() }}',
                    type: 'POST',
                    data: { _token: csrfToken },
                    success: function(response) {
                        checkSessionStatus();
                        Swal.fire('Success', 'Logged out successfully!', 'success');
                    },
                    error: function(error) {
                        checkSessionStatus();
                        let errorMsg = error.responseJSON && error.responseJSON.message ? error.responseJSON.message : '';
                        Swal.fire('Error', 'Failed to logout. ' + errorMsg, 'error');
                    }
                });
            }

            function syncContactsFromTelegramToDatabase() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: '/telegram/sync-contacts/{{ auth()->id() }}',
                    type: 'GET',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        checkSessionStatus();
                        Swal.fire('Success', 'Contacts synced successfully!', 'success');
                    },
                    error: function(error) {
                        checkSessionStatus();
                        let errorMsg = error.responseJSON && error.responseJSON.message ? error.responseJSON.message : '';
                        Swal.fire('Error', 'Failed to sync contacts. ' + errorMsg, 'error');
                    }
                });
            }

            $(document).ready(function() {
                checkSessionStatus();
                fetchChats(); // Llamada inicial para chats
            });

            // Evento para enviar mensaje al chat activo
            $(document).on('click', '#sendMessageButton', function(e) {
                e.preventDefault();
                let messageText = tinymce.get('tiny-editor').getContent({ format: 'text' });
                if (!messageText || messageText.trim() === '') {
                    Swal.fire('Error', 'Please enter a message.', 'error');
                    return;
                }
                if (!currentPeer) {
                    Swal.fire('Error', 'No chat selected.', 'error');
                    return;
                }
                let userId = "{{ auth()->id() }}";
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: '/telegram/send-message/' + userId + '/' + currentPeer,
                    type: 'POST',
                    data: {
                        message: messageText,
                        _token: csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success', 'Message sent!', 'success');
                            tinymce.get('tiny-editor').setContent('');
                            fetchMessages(currentPeer);
                        } else {
                            Swal.fire('Error', 'Failed to send message.', 'error');
                        }
                    },
                    error: function(error) {
                        let errorMsg = error.responseJSON && error.responseJSON.message ? error.responseJSON.message : '';
                        Swal.fire('Error', 'Failed to send message. ' + errorMsg, 'error');
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
