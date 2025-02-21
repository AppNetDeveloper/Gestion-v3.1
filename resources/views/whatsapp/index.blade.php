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
                return preg_replace_callback(
                    '/((https?:\/\/|www\.)[^\s]+)/i',
                    function ($matches) {
                        $url = $matches[0];
                        $href = (stripos($url, 'http') === 0) ? $url : 'https://' . $url;
                        return '<a href="' . e($href) . '" target="_blank" class="inline-block px-2 py-1 border border-blue-500 rounded text-blue-500 hover:bg-blue-500 hover:text-white dark:border-blue-300 dark:text-blue-300 dark:hover:bg-blue-300">Link</a>';
                    },
                    $text
                );
            }
        }
        // Lógica para obtener contactos
        $contacts = \App\Models\Contact::where('user_id', auth()->id())->get();
        $contactsWithMessages = $contacts->filter(function($contact) {
            return $contact->whatsappMessages()->where('user_id', auth()->id())->exists();
        });
        // Filtrar únicamente los contactos con un teléfono válido.
        // Se considera válido un teléfono que opcionalmente comience con '+' y contenga entre 8 y 15 dígitos.
        $validContacts = $contactsWithMessages->filter(function($contact) {
            return preg_match('/^\+?[0-9]{8,15}$/', $contact->phone);
        });
        // Ordenar los contactos por la fecha del último mensaje (descendente)
        $sortedContacts = $validContacts->sortByDesc(function($contact) {
            return $contact->whatsappMessages()->where('user_id', auth()->id())->max('created_at');
        });
        // Cargar la configuración de auto respuesta (si existe) para el usuario actual
        $autoResponseConfig = \App\Models\AutoProcess::where('user_id', auth()->id())->first();
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
                                        <a href="{{ route('whatsapp.conversation', $contact->phone) }}"
                                           class="block p-2 rounded bg-blue-500 text-gray-800 dark:bg-blue-700 dark:text-white hover:bg-blue-600 dark:hover:bg-blue-800">
                                            {{ $contact->name ? $contact->name : $contact->phone }}
                                        </a>
                                        <form action="{{ route('whatsapp.chat.destroy', $contact->phone) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="delete-chat-btn text-red-500 px-2 py-1" title="{{ __('Are you sure you want to delete all messages for this contact?') }}" onclick="return confirm('{{ __('Are you sure you want to delete this message?') }}');">
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
                                        $contactSelected = \App\Models\Contact::where('user_id', auth()->id())
                                            ->where('phone', $selectedPhone)
                                            ->first();
                                    @endphp
                                    <div class="border-b p-3">
                                        <h3 class="font-bold">
                                            {{ __("Conversation with") }} {{ $contactSelected ? $contactSelected->name : $selectedPhone }}
                                        </h3>
                                    </div>
                                    <div id="chat-container" class="flex flex-col space-y-4 overflow-y-auto h-96 p-4" style="height: 24rem;">
                                        @forelse($messages->sortBy('created_at') as $message)
                                            @if($message->status === 'send')
                                                <div class="flex w-full justify-end pr-4">
                                            @else
                                                <div class="flex w-full justify-start pl-4">
                                            @endif
                                                    <div class="max-w-xs w-auto relative">
                                                        <div class="shadow-lg rounded-lg overflow-hidden message-bubble
                                                            {{ $message->status === 'send'
                                                                ? 'bg-blue-500 text-gray-800 dark:bg-blue-700 dark:text-white'
                                                                : 'bg-blue-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100' }}">
                                                            <div class="px-4 py-2 relative">
                                                                <form action="/whatsapp/message/{{ $message->id }}" method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="delete-message-btn absolute top-0 right-0 text-red-500 p-1" title="{{ __('Are you sure you want to delete this message?') }}" style="background:none;border:none;" onclick="return confirm('{{ __('Are you sure you want to delete this message?') }}');">
                                                                        <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                                                    </button>
                                                                </form>
                                                                {!! formatMessageText($message->message) !!}
                                                                @if($message->image)
                                                                    @php
                                                                        $imageSrc = $message->image;
                                                                        if (strpos($imageSrc, 'data:image') === 0) {
                                                                            $imageSrc = convertCsvImage($imageSrc);
                                                                        } else {
                                                                            $imageSrc = asset($imageSrc);
                                                                        }
                                                                    @endphp
                                                                    <div class="mt-2">
                                                                        <img src="{{ $imageSrc }}" alt="Image" class="max-w-full h-auto object-contain rounded cursor-pointer" onclick="showImageModal('{{ $imageSrc }}')">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="px-4 py-1 text-right text-xs text-gray-700 dark:text-gray-300">
                                                                <small>{{ $message->created_at->format('d/m/Y H:i') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        @empty
                                            <p class="text-center text-gray-500 dark:text-gray-400">{{ __("No messages found for this contact.") }}</p>
                                        @endforelse
                                    </div>

                                    <!-- Formulario para enviar mensaje vía AJAX usando TinyMCE -->
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
                    <!-- Se elimina el panel derecho de información -->
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
                overflow-x: auto;
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

    {{-- Scripts: se cargan al final para asegurar que todas las librerías estén disponibles --}}
    @push('scripts')
        <!-- Cargar jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Configuración de AJAX para CSRF -->
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>

        <!-- Cargar TinyMCE -->
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
            // Se expone la configuración actual (si existe) a JavaScript
            var autoResponseConfig = {!! json_encode($autoResponseConfig) !!};

            // Función para ajustar el scroll al último mensaje
            function scrollChatToBottom() {
                var chatContainer = document.getElementById('chat-container');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }

            // Actualiza el estado de conexión y muestra los botones (con iconos, tooltips y en línea)
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
                            // Listener para el botón de Auto Response (independientemente del estado)
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

            // Inicia la sesión y obtiene el QR (vía AJAX)
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

            // Auto-refresh del contenedor de chat cada 10 segundos (solo el chat)
            setInterval(function() {
                if (document.getElementById('chat-container')) {
                    $("#chat-container").load(window.location.href + " #chat-container > *", function() {
                        scrollChatToBottom();
                    });
                }
            }, 10000);

            // Envío de mensajes con TinyMCE vía AJAX
            $(document).off('click', '#sendMessageButton').on('click', '#sendMessageButton', function(event) {
    event.preventDefault();
    event.stopImmediatePropagation(); // Evita que se ejecuten otros listeners en cascada

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

            // Escucha del evento DOMContentLoaded para inicializar funciones y búsqueda de contactos
            document.addEventListener('DOMContentLoaded', function() {
                updateConnectionStatus();
                scrollChatToBottom();

                // Búsqueda en tiempo real de contactos
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

            // Mostrar imagen en modal al hacer clic
            function showImageModal(url) {
                Swal.fire({
                    imageUrl: url,
                    imageAlt: '{{ __("Task Details") }}',
                    showConfirmButton: false,
                    background: 'transparent'
                });
            }
        </script>
    @endpush
</x-app-layout>
