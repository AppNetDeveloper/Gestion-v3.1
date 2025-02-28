<x-app-layout>
    {{-- Sección HEAD: meta tags --}}
    @push('head')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Puedes dejar otros enlaces CSS acá si los necesitas -->
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
        // Función para convertir URLs a links
        if (! function_exists('convertUrlsToLinks')) {
            function convertUrlsToLinks($text) {
                // Capturamos cualquier URL que empiece con http(s):// o www.
                $pattern = '/((?:https?:\/\/|www\.)[^\s]+)/i';

                return preg_replace_callback($pattern, function ($matches) {
                    // 1. Extraemos la URL tal como la capturó la regex
                    $url = $matches[0];

                    // 2. Removemos puntuación final (.,!?;:) que no suele formar parte de la URL
                    //    (esto evita que algo como "https://beta.appnet.dev." se convierta en "https://beta.appnet.dev.")
                    $url = rtrim($url, '.,!?;:');

                    // 3. Aseguramos que el href empiece con http(s)://
                    if (! preg_match('/^https?:\/\//i', $url) && ! preg_match('/^www\./i', $url)) {
                        // Si no empieza con http o www, lo dejamos tal cual o lo convertimos
                        // en "https://...". Depende de tu preferencia.
                        // Pero normalmente, si no empieza con "http" o "www", no lo tratamos como URL
                        // (Este if en realidad casi nunca se ejecutaría con la regex anterior).
                    } elseif (preg_match('/^www\./i', $url)) {
                        // Si empieza con www., agregamos https:// al principio
                        $url = 'https://' . $url;
                    }

                    // 4. Construimos el enlace <a> con el texto "Link"
                    return '<a href="' . e($url) . '" target="_blank" class="inline-block px-2 py-1 border border-blue-500 rounded text-blue-500 hover:bg-blue-500 hover:text-white dark:border-blue-300 dark:text-blue-300 dark:hover:bg-blue-300">Link</a>';
                }, $text);
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
                                <div class="p-3 border-t border-slate-100 dark:border-slate-700">
                                    <div id="connection-btn" class="text-center"></div>
                                </div>
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
                        <!-- Lista de Contactos -->
                        <div id="contacts-panel" class="contact-height overflow-y-auto" data-simplebar>
                            <h3 class="font-bold mb-4 px-3">{{ __("Contacts") }}</h3>
                            <ul class="space-y-2">
                                @foreach($sortedContacts as $contact)
                                    <li class="flex items-center justify-between">
                                        <a href="{{ route('whatsapp.conversation', $contact['phone']) }}"
                                           class="block p-2 rounded bg-blue-500 text-gray-800 dark:bg-blue-700 dark:text-white hover:bg-blue-600 dark:hover:bg-blue-800">
                                            {{ $contact['name'] }}
                                        </a>
                                        <!-- Botón para eliminar chat -->
                                        <form action="{{ route('whatsapp.chat.destroy', $contact['phone']) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="delete-chat-btn text-red-500 px-2 py-1"
                                                title="{{ __('Are you sure you want to delete all messages for this contact?') }}"
                                                onclick="return confirm('{{ __('Are you sure you want to delete this chat?') }}');">
                                                <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área principal del chat -->
            <div class="flex-1">
                <div class="parent flex flex-col h-full rtl:space-x-reverse">
                    <!-- Panel de mensajes (conversación) -->
                    <div class="flex-1">
                        <div class="h-full card">
                            <div class="p-0 h-full body-class">
                                @if(isset($selectedPhone))
                                    @php
                                        $contactSelected = collect($sortedContacts)->firstWhere('phone', $selectedPhone);
                                        $selectedJid = $selectedPhone . '@s.whatsapp.net';
                                    @endphp
                                    <div class="border-b p-3">
                                        <h3 class="font-bold">
                                            {{ __("Conversation with") }} {{ $contactSelected ? $contactSelected['name'] : $selectedPhone }}
                                        </h3>
                                    </div>
                                    <div id="chat-container" class="flex flex-col space-y-4 overflow-y-auto h-96 p-4" style="height: 24rem;">
                                        @forelse($messages->sortBy('created_at') as $message)
                                            @php
                                                // Determinar si el mensaje es recibido (si remoteJid coincide con el del chat) o enviado
                                                $remoteJid = $message['key']['remoteJid'] ?? '';
                                                $isReceived = ($remoteJid === $selectedJid);
                                            @endphp

                                            <div class="flex w-full {{ $isReceived ? 'justify-start pl-4' : 'justify-end pr-4' }}">
                                                <div class="relative md:max-w-2xl max-w-lg shadow-lg rounded-lg overflow-hidden message-bubble
                                                    {{ $isReceived
                                                        ? 'bg-blue-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100'
                                                        : 'bg-blue-500 text-gray-800 dark:bg-blue-700 dark:text-white'
                                                    }}">
                                                    <div class="px-4 py-2 relative">
                                                        <!-- Botón para eliminar mensaje -->
                                                        <form action="/whatsapp/message/{{ $message['key']['id'] }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="remoteJid" value="{{ $message['key']['remoteJid'] }}">
                                                            <input type="hidden" name="fromMe" value="{{ $message['key']['fromMe'] }}">
                                                            <button type="submit" class="delete-message-btn"
                                                                title="{{ __('Are you sure you want to delete this message?') }}"
                                                                onclick="return confirm('{{ __('Are you sure you want to delete this message?') }}');">
                                                                <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                                            </button>
                                                        </form>


                                                        {{-- Mostrar contenido del mensaje --}}
                                                        {!! formatMessageText($message['message'] ?? '') !!}

                                                        {{-- Si existe imagen (mensaje de imagen) --}}
                                                        @if(isset($message['image']) && $message['image'])
                                                            @php
                                                                $imageSrc = $message['image'];
                                                                if (strpos($imageSrc, 'data:image') === 0) {
                                                                    $imageSrc = convertCsvImage($imageSrc);
                                                                } else {
                                                                    $imageSrc = asset($imageSrc);
                                                                }
                                                            @endphp
                                                            <div class="mt-2">
                                                                <img src="{{ $imageSrc }}" alt="Image" class="max-w-full h-auto object-contain rounded cursor-pointer"
                                                                     onclick="showZoomModal('{{ $imageSrc }}')">
                                                            </div>
                                                        @endif

                                                        {{-- Si es un mensaje de video --}}
                                                        @if(isset($message['videoMessage']['url']))
                                                            @php
                                                                $videoUrl = $message['videoMessage']['url'];
                                                            @endphp
                                                            <div class="mt-2">
                                                                <button class="btn btn-sm btn-primary" onclick="showVideoModal('{{ $videoUrl }}')">
                                                                    {{ __('Video') }}
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="px-4 py-1 text-right text-xs text-gray-700 dark:text-gray-300">
                                                        <small>{{ isset($message['created_at']) ? \Carbon\Carbon::parse($message['created_at'])->format('d/m/Y H:i') : '' }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-center text-gray-500 dark:text-gray-400">{{ __("No messages found for this contact.") }}</p>
                                        @endforelse
                                    </div>

                                    <!-- Formulario para enviar mensaje vía AJAX con TinyMCE -->
                                    <div id="send-message-container" class="mt-4 p-4 border-t">
                                        <textarea id="tiny-editor" placeholder="{{ __('Type your message...') }}" class="border rounded p-2 w-full" style="height: 150px;"></textarea>
                                        <button id="sendMessageButton" class="btn btn-primary mt-2">{{ __('Send') }}</button>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <p class="text-gray-500 dark:text-gray-400">{{ __("Please select a contact to view the conversation.") }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- No se muestra panel derecho -->
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
            .contact-height {
                max-height: 60vh;
            }
            .body-class {
                position: relative;
                height: 100%;
            }
            .message-bubble {
                padding: 10px;
                border-radius: 10px;
                max-width: 80%;
                word-break: break-word;
                overflow-wrap: break-word;
            }
            .message-bubble p {
                white-space: normal;
                word-break: break-all;
                overflow-wrap: break-word;
            }
            .message-bubble pre {
                white-space: pre;
                overflow-x: auto;
                margin: 0;
                font-family: monospace;
            }
        </style>
    @endpush

    {{-- Scripts: se cargan al final --}}
    @push('scripts')
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            var autoResponseConfig = {!! json_encode($autoResponseConfig) !!};

            function scrollChatToBottom() {
                var chatContainer = document.getElementById('chat-container');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }

            function updateConnectionStatus() {
                fetch('{{ secure_url("/api/whatsapp/check?user_id=" . auth()->id()) }}', { method: 'GET' })
                    .then(response => response.json())
                    .then(data => {
                        var container = document.getElementById('connection-btn');
                        if (data.success) {
                            if (data.connected) {
                                container.innerHTML = `
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <button id="btnDisconnect" class="btn btn-danger rounded-full px-4 py-2" title="{{ __('Disconnect: Click to log out') }}">
                                            <iconify-icon icon="mdi:logout-variant" style="font-size: 1.5rem;"></iconify-icon>
                                        </button>
                                        <button id="btnAutoResponse" class="btn btn-secondary rounded-full px-4 py-2" title="{{ __('Auto Response: Configure automatic responses') }}">
                                            <iconify-icon icon="mdi:robot" style="font-size: 1.5rem;"></iconify-icon>
                                        </button>
                                    </div>`;
                                document.getElementById('btnDisconnect').addEventListener('click', function() {
                                    fetch('{{ secure_url("/api/whatsapp/logout?user_id=" . auth()->id()) }}', { method: 'GET' })
                                        .then(response => response.json())
                                        .then(logoutData => {
                                            if (logoutData.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: '{{ __("Logged out successfully.") }}',
                                                    text: '{{ __("Logged out successfully.") }}'
                                                });
                                                updateConnectionStatus();
                                            } else {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: '{{ __("An error occurred") }}',
                                                    text: '{{ __("Error while logging out:") }} ' + logoutData.message
                                                });
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error disconnecting:', error);
                                            Swal.fire({
                                                icon: 'error',
                                                title: '{{ __("An error occurred") }}',
                                                text: '{{ __("Error while logging out.") }}'
                                            });
                                        });
                                });
                            } else {
                                container.innerHTML = `
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <button id="btnConnect" class="btn btn-success rounded-full px-4 py-2" title="{{ __('Connect: Click to start session') }}">
                                            <iconify-icon icon="mdi:login" style="font-size: 1.5rem;"></iconify-icon>
                                        </button>
                                        <button id="btnAutoResponse" class="btn btn-secondary rounded-full px-4 py-2" title="{{ __('Auto Response: Configure automatic responses') }}">
                                            <iconify-icon icon="mdi:robot" style="font-size: 1.5rem;"></iconify-icon>
                                        </button>
                                    </div>`;
                                document.getElementById('btnConnect').addEventListener('click', function() {
                                    Swal.fire({
                                        title: '{{ __("Generating QR") }}',
                                        text: '{{ __("Please wait while the QR code is being generated...") }}',
                                        allowOutsideClick: false,
                                        didOpen: () => { Swal.showLoading(); }
                                    });
                                    startWhatsAppSession();
                                });
                            }
                            document.getElementById('btnAutoResponse').addEventListener('click', function() {
                                var selectedValue = autoResponseConfig ? autoResponseConfig.whatsapp : 0;
                                var promptValue = autoResponseConfig ? autoResponseConfig.whatsapp_prompt : '';
                                Swal.fire({
                                    title: '{{ __("Auto Response Settings") }}',
                                    html:
                                        '<select id="autoResponseSelect" class="swal2-select" style="margin-bottom: 1rem;">' +
                                            '<option value="0" ' + (selectedValue == 0 ? 'selected' : '') + '>{{ __("Disabled") }}</option>' +
                                            '<option value="1" ' + (selectedValue == 1 ? 'selected' : '') + '>{{ __("Auto Response") }}</option>' +
                                            '<option value="2" ' + (selectedValue == 2 ? 'selected' : '') + '>{{ __("AI Response") }}</option>' +
                                            '<option value="3" ' + (selectedValue == 3 ? 'selected' : '') + '>{{ __("Create Ticket") }}</option>' +
                                        '</select>' +
                                        '<input id="autoResponsePrompt" class="swal2-input" placeholder="{{ __("Enter prompt") }}" value="' + promptValue + '">',
                                    focusConfirm: false,
                                    preConfirm: () => {
                                        const responseType = document.getElementById('autoResponseSelect').value;
                                        const promptText = document.getElementById('autoResponsePrompt').value;
                                        if (parseInt(responseType) > 0 && (!promptText || promptText.trim() === '')) {
                                            Swal.showValidationMessage('{{ __("The prompt field is required for this option.") }}');
                                        }
                                        return {
                                            whatsapp: responseType,
                                            whatsapp_prompt: promptText
                                        };
                                    }
                                }).then((result) => {
                                    if(result.isConfirmed){
                                        const data = result.value;
                                        $.ajax({
                                            url: '/auto-response',
                                            method: 'POST',
                                            data: {
                                                _token: $('meta[name="csrf-token"]').attr('content'),
                                                whatsapp: data.whatsapp,
                                                whatsapp_prompt: data.whatsapp_prompt,
                                            },
                                            success: function(response) {
                                                if(response.success){
                                                    Swal.fire('{{ __("Saved!") }}', response.message, 'success');
                                                    autoResponseConfig = response.data || null;
                                                } else {
                                                    Swal.fire('{{ __("An error occurred") }}', response.message, 'error');
                                                }
                                            },
                                            error: function(xhr) {
                                                Swal.fire('{{ __("An error occurred") }}', '{{ __("An error occurred while updating the configuration.") }}', 'error');
                                            }
                                        });
                                    }
                                });
                            });
                        } else {
                            container.innerHTML = `
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <button class="btn btn-warning rounded-full px-4 py-2" title="{{ __('Error: Connection issue') }}">
                                        <iconify-icon icon="mdi:alert-circle-outline" style="font-size: 1.5rem;"></iconify-icon>
                                    </button>
                                </div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error checking connection status:', error);
                    });
            }

            function startWhatsAppSession() {
                fetch('{{ secure_url("/api/whatsapp/start-session") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: {{ auth()->id() }} })
                })
                    .then(response => response.json())
                    .then(apiData => {
                        if (apiData.success && apiData.qr) {
                            Swal.close();
                            Swal.fire({
                                title: '{{ __("Scan the QR Code") }}',
                                imageUrl: apiData.qr,
                                imageAlt: 'QR Code',
                                showConfirmButton: true,
                                confirmButtonText: '{{ __("Close") }}'
                            });
                            updateConnectionStatus();
                        } else {
                            setTimeout(startWhatsAppSession, 1000);
                        }
                    })
                    .catch(error => {
                        console.error('Error starting WhatsApp session:', error);
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("An error occurred") }}',
                            text: '{{ __("Error connecting. Please try again.") }}'
                        });
                    });
            }

            setInterval(function() {
                // Refrescar los mensajes
                if (document.getElementById('chat-container')) {
                    $("#chat-container").load(window.location.href + " #chat-container > *", function() {
                        scrollChatToBottom();
                    });
                }

                // Refrescar los contactos
                if (document.getElementById('contacts-panel')) {
                    $("#contacts-panel").load(window.location.href + " #contacts-panel > *", function() {
                        // Actualizar los contactos si es necesario
                    });
                }
            }, 10000);

            $(document).off('click', '#sendMessageButton').on('click', '#sendMessageButton', function(event) {
                event.preventDefault();
                event.stopImmediatePropagation();

                var messageText = tinymce.get('tiny-editor').getContent({ format: 'text' });
                var sessionId = "{{ auth()->id() }}";
                var jid = "{{ $selectedPhone ?? '' }}";
                var token = "{{ env('WHATSAPP_API_TOKEN') }}";

                if (!jid) {
                    Swal.fire('{{ __("An error occurred") }}', '{{ __("No contact selected.") }}', 'error');
                    return;
                }
                if (!messageText || messageText.trim() === '') {
                    Swal.fire('{{ __("An error occurred") }}', '{{ __("Please enter a message.") }}', 'error');
                    return;
                }

                fetch('{{ secure_url("/api/whatsapp/send-message-now") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        token: token,
                        sessionId: sessionId,
                        jid: jid,
                        message: messageText
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Saved!") }}',
                            text: data.message,
                            timer: 3000,
                            showConfirmButton: false
                        }).then(() => {
                            $("#chat-container").load(window.location.href + " #chat-container > *", function() {
                                scrollChatToBottom();
                            });
                        });
                        tinymce.get('tiny-editor').setContent('');
                    } else {
                        Swal.fire('{{ __("An error occurred") }}', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    Swal.fire('{{ __("An error occurred") }}', '{{ __("An error occurred while sending the message.") }}', 'error');
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                updateConnectionStatus();
                scrollChatToBottom();

                const searchInput = document.getElementById('contactSearch');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const filter = searchInput.value.toLowerCase();
                        const contactListItems = document.querySelectorAll('#contacts-panel ul li');
                        contactListItems.forEach(function(item) {
                            if (item.textContent.toLowerCase().indexOf(filter) > -1) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                }
            });

            // Función para mostrar la imagen en modal (zoom) con SweetAlert2
            function showZoomModal(url) {
                Swal.fire({
                    imageUrl: url,
                    imageAlt: 'Zoomed Image',
                    width: '80%',
                    showConfirmButton: false,
                    customClass: { popup: 'swal2-no-border' },
                    background: 'transparent'
                });
            }

            // Función para mostrar video en modal (HTML5 video)
            function showVideoModal(url) {
                Swal.fire({
                    html: '<video controls style="width:100%; max-height:80vh;"><source src="' + url + '" type="video/mp4">Your browser does not support the video tag.</video>',
                    width: '80%',
                    showConfirmButton: false,
                    customClass: { popup: 'swal2-no-border' },
                    background: 'transparent'
                });
            }
        </script>
    @endpush
</x-app-layout>
