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
                                    <!-- Aquí cargamos el estado de la conexión -->
                                </div>
                                <div id="connection-btn2" class="text-center">
                                    <!-- Aquí cargamos el estado de la conexión -->
                                </div>
                            </div>
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
                                    <!-- Los mensajes de la conversación se cargarán aquí -->
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

        <!-- Configuración de AJAX para CSRF -->
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Verificar el estado de la sesión
            function checkSessionStatus() {
                fetch('/telegram/session-status/{{ auth()->id() }}')
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);

                        // Verificar si hay un error (como 'Sesión no encontrada')
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
                            //quitar el button de reset
                            $('#connection-btn2').html(``);

                        } else {
                            // Verificar si la sesión está conectada aqui deberiamos de separas {userId: '1', isValidated: true} si es true o false
                            if (data.isValidated) {
                                    $('#connection-btn').html(`
                                        <button id="logout-btn" class="btn btn-danger">
                                            {{ __('Logout') }}
                                        </button>
                                    `);
                                    $('#logout-btn').click(function() {
                                        logout();
                                    });
                                    //quitar el button de reset
                                    $('#connection-btn2').html(``);
                            } else {
                                // Si no está conectado, mostrar el pin code
                                $('#connection-btn').html(`
                                        <input type="text" id="pin-code" placeholder="Enter pin code" class="w-full p-2">
                                        <button id="start-session-btn" class="btn btn-success">
                                            {{ __('inset pin code') }}
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
                        // Manejar errores en la conexión o en la respuesta
                        console.error('Error al verificar el estado de la sesión:', error);
                        Swal.fire('Error', 'No se pudo verificar el estado de la sesión.', 'error');
                    });
            }


            // Iniciar sesión
            function startSession() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let phone = $('#phone').val();
                $.ajax({
                    url: '/telegram/request-code/{{ auth()->id() }}',
                    type: 'POST',
                    data: { phone: phone, _token: csrfToken },
                    success: function(response) {
                        // Mostrar mensaje de éxito
                        checkSessionStatus();
                        Swal.fire('Success', 'Verification code sent!', 'success');
                    },
                    error: function(error) {
                        // Mostrar mensaje de error
                        checkSessionStatus();
                        Swal.fire('Error', 'Failed to send verification code.', 'error');
                    }
                });
            }


            // Iniciar sesión
            function inputPinCode() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                let pinCode = $('#pin-code').val();
                $.ajax({
                    url: '/telegram/verify-code/{{ auth()->id() }}',
                    type: 'POST',
                    data: { code: pinCode, _token: csrfToken },
                    success: function(response) {
                        // Mostrar mensaje de éxito
                        checkSessionStatus();
                        Swal.fire('Success', 'Code verified!', 'success');
                    },
                    error: function(error) {
                        // Mostrar mensaje de error
                        checkSessionStatus();
                        Swal.fire('Error', 'Failed verify the code.', 'error');
                    }
                });
            }

            // Logout
            function logout() {
                let csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: '/telegram/logout/{{ auth()->id() }}',
                    type: 'POST',
                    data: { _token: csrfToken },
                    success: function(response) {
                        checkSessionStatus();
                        Swal.fire('Success', 'Logged out successfully!', 'success');
                        checkSessionStatus();
                    },
                    error: function(error) {
                        checkSessionStatus();
                        let errorMsg = error.responseJSON && error.responseJSON.message ? error.responseJSON.message : '';
                        Swal.fire('Error', 'Failed to logout. ' + errorMsg, 'error');
                    }
                });
            }
            //ahora creamos otra funcion para sacar los nuevos chats
            


            // Llamar a la función de verificación de sesión al cargar la página
            $(document).ready(function() {
                checkSessionStatus();
            });
        </script>
    @endpush
</x-app-layout>
