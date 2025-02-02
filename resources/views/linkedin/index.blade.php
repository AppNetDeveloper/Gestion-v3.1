<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Publish on LinkedIn')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif

    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    {{-- Alert end --}}

    <div class="card">
        <header class="card-header noborder">
            <div class="flex justify-end gap-3 items-center flex-wrap">
                {{-- Refresh Button --}}
                <a class="btn inline-flex justify-center btn-dark rounded-[25px] items-center !p-2.5" href="{{ route('linkedin.index') }}">
                    <iconify-icon icon="mdi:refresh" class="text-xl"></iconify-icon>
                </a>
            </div>
        </header>

        <div class="card-body px-6 pb-6">
            @if($token)
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <iconify-icon icon="mdi:check-circle" class="text-green-500 text-lg"></iconify-icon>
                        <p class="text-gray-700 dark:text-gray-300 text-lg">
                            {{ __('Your LinkedIn account is connected successfully.') }}
                        </p>
                    </div>
                    {{-- Botón para desvincular --}}
                    <button id="btnDisconnect" class="btn btn-danger rounded-[25px] px-4 py-2 flex items-center">
                        <iconify-icon icon="mdi:link-off" class="mr-2"></iconify-icon>
                        {{ __('Disconnect') }}
                    </button>
                </div>

                <form action="{{ route('linkedin.post') }}" method="POST" class="mt-6">
                    @csrf
                    <div class="mb-3">
                        <label for="content" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">
                            {{ __('Write your post') }}
                        </label>
                        <textarea id="content" name="content" rows="4"
                                  class="inputField w-full p-3 border border-slate-300 dark:border-slate-700 rounded-md dark:bg-slate-900"
                                  placeholder="{{ __('Share something on LinkedIn...') }}" required></textarea>
                    </div>

                    <!-- Contenedor para los botones en línea -->
                    <div class="flex flex-row gap-3 mb-3">
                        <!-- Botón para procesar el texto con IA -->
                        <button type="button" id="btnProcessIA" class="btn btn-secondary rounded-[25px] px-5 py-2 flex items-center">
                            <iconify-icon icon="mdi:robot" class="mr-2"></iconify-icon>
                            {{ __('Process text with AI') }}
                        </button>

                        <!-- Botón para programar publicación -->
                        <button type="button" id="btnSchedule" class="btn btn-info rounded-[25px] px-5 py-2 flex items-center">
                            <iconify-icon icon="mdi:calendar-clock" class="mr-2"></iconify-icon>
                            {{ __('Schedule') }}
                        </button>

                        <!-- Botón para publicar en LinkedIn -->
                        <button type="submit" class="btn btn-primary rounded-[25px] px-5 py-2 flex items-center">
                            <iconify-icon icon="bi:send-fill" class="mr-2"></iconify-icon>
                            {{ __('Publish on LinkedIn') }}
                        </button>
                    </div>
                </form>

                <!-- Sección para el DataTable de tareas programadas -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-4">{{ __('My Scheduled Tasks') }}</h2>
                    <div class="overflow-x-auto">
                        <table id="tasksTable" class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                            <thead class="bg-slate-200 dark:bg-slate-700">
                                <tr>
                                    <th class="table-th">{{ __('ID') }}</th>
                                    <th class="table-th">{{ __('Prompt') }}</th>
                                    <th class="table-th">{{ __('Response') }}</th>
                                    <th class="table-th">{{ __('Status') }}</th>
                                    <th class="table-th">{{ __('Error') }}</th>
                                    <th class="table-th">{{ __('Publish Date') }}</th>
                                    <th class="table-th">{{ __('Created At') }}</th>
                                    <th class="table-th w-20">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                {{-- Los datos se cargarán vía AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <a href="{{ route('linkedin.auth') }}" class="btn btn-outline-primary rounded-[25px] px-5 py-2 flex items-center">
                        <iconify-icon icon="bi:linkedin"></iconify-icon>
                        <span class="ml-2">{{ __('Connect with LinkedIn') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    @endpush

    @push('scripts')
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- jQuery (para DataTables) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script>
            // Desvinculación
            document.getElementById('btnDisconnect').addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("Are you sure?") }}',
                    text: '{{ __("If you confirm, your LinkedIn account will be disconnected and you'll have to log in again.") }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '{{ __("Yes, disconnect") }}',
                    cancelButtonText: '{{ __("Cancel") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ rtrim(config('app.url'), '/') }}{{ route('linkedin.disconnect', [], false) }}", {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Content-Type": "application/json"
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('{{ __("Disconnected") }}', data.success, 'success')
                                    .then(() => window.location.reload());
                            } else {
                                Swal.fire('{{ __("Error") }}', data.error, 'error');
                            }
                        })
                        .catch(error => Swal.fire('{{ __("Error") }}', '{{ __("Unable to complete the action.") }}', 'error'));
                    }
                });
            });

            // Procesar texto con IA (Ollama)
            document.getElementById('btnProcessIA').addEventListener('click', function () {
                const textarea = document.getElementById('content');
                // Variable de prefijo (antes del contenido del textarea)
                const prefix = "Crea una publicación profesional y atractiva para LinkedIn, pero sin escribir nada de cabezal sobre te escribo una publicacion o algo parecido, siguiendo estas directrices: ";
                // Contenido ingresado por el usuario en el textarea
                const userPrompt = textarea.value;
                // Variable de sufijo (después del contenido del textarea)
                const suffix = " Mantén un tono profesional, cercano y humano. Usa un lenguaje claro, inspirador y persuasivo que motive a la acción. SI no tienes las informaciones para completar tus textos no pongas la parte que te falta. Pon solo datos concretos y que tienes; no inventes nada y tampoco dejes partes para que el usuario las complete. Si no existen los datos como nombre, usuario, empresa, etc., no uses esto.";
                
                // Concatenar las tres variables para formar el prompt final
                const prompt = prefix + userPrompt + suffix;
                //modificamos el model al valor del env en el archivo .env
                const model = '{{ env('OLLAMA_MODEL_MINI') }}';

                fetch('ollama/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ prompt: prompt, model: model })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.text) {
                        textarea.value = data.text;
                    } else if (data.error) {
                        alert(data.error);
                    } else {
                        alert('{{ __("An error occurred while processing the text.") }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __("Error in the request.") }}');
                });
            });

            // Programar publicación
            document.getElementById('btnSchedule').addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("Schedule Publication") }}',
                    width: '800px',
                    html: `
                        <div style="margin-bottom: 15px; text-align: left;">
                            <label style="display: block; font-weight: bold; margin-bottom: 5px;">{{ __("Select Date and Time") }}</label>
                            <input type="datetime-local" id="publish_date" class="swal2-input" placeholder="{{ __("Select date and time") }}">
                        </div>
                        <div style="text-align: left;">
                            <label style="display: block; font-weight: bold; margin-bottom: 5px;">{{ __("Prompt") }}</label>
                            <textarea id="task_prompt" class="swal2-textarea" style="width:92%; height:150px;" placeholder="{{ __("Enter prompt") }}"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save") }}',
                    preConfirm: () => {
                        const publish_date = document.getElementById('publish_date').value;
                        const task_prompt = document.getElementById('task_prompt').value;
                        if (!publish_date) {
                            Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                        }
                        return { publish_date, task_prompt };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = result.value;
                        const promptValue = data.task_prompt || document.getElementById('content').value;
                        
                        fetch('tasker-linkedin/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                prompt: promptValue,
                                publish_date: data.publish_date
                            })
                        })
                        .then(response => response.json())
                        .then(resp => {
                            if (resp.success) {
                                Swal.fire('{{ __("Saved!") }}', resp.success, 'success');
                                // Recargar la DataTable para mostrar la nueva tarea
                                $('#tasksTable').DataTable().ajax.reload();
                            } else {
                                Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred") }}', 'error');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while saving the task") }}', 'error');
                        });
                    }
                });
            });

            // Inicializar DataTable para las tareas programadas
            $(document).ready(function() {
                $('#tasksTable').DataTable({
                    ajax: {
                        url: 'tasker-linkedin/data',
                        dataSrc: 'data'
                    },
                    columns: [
                        { data: 'id' },
                        { 
                            data: 'prompt',
                            render: function(data, type, row) {
                                if (type === 'display' && data && data.length > 20) {
                                    return data.substring(0, 20) + '...';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'response',
                            render: function(data, type, row) {
                                if (type === 'display' && data && data.length > 20) {
                                    return data.substring(0, 20) + '...';
                                }
                                return data;
                            }
                        },
                        { data: 'status' },
                        { 
                            data: 'error',
                            render: function(data, type, row) {
                                if (type === 'display' && data && data.length > 20) {
                                    return data.substring(0, 20) + '...';
                                }
                                return data;
                            }
                        },
                        { data: 'publish_date' },
                        { data: 'created_at' },
                        {
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                return `
                                    <span class="cursor-pointer viewDetails" style="display: inline-block; vertical-align: middle;" 
                                        data-prompt="${encodeURIComponent(row.prompt)}" 
                                        data-response="${encodeURIComponent(row.response)}" title="{{ __('View Details') }}">
                                        <iconify-icon icon="heroicons:eye" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                    <span class="cursor-pointer deleteTask" style="display: inline-block; vertical-align: middle; margin-left: 10px;" 
                                        data-id="${row.id}" title="{{ __('Delete Task') }}">
                                        <iconify-icon icon="heroicons:trash" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                `;
                            }
                        }
                    ],
                    order: [[0, "desc"]],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json"
                    }
                });

                // Handler para el botón "View" del DataTable
                $('#tasksTable').on('click', '.viewDetails', function () {
                    const prompt = decodeURIComponent($(this).data('prompt'));
                    const response = decodeURIComponent($(this).data('response'));
                    Swal.fire({
                        title: '{{ __("Task Details") }}',
                        html: `<strong>{{ __("Prompt") }}:</strong><br>${prompt}<br><br><strong>{{ __("Response") }}:</strong><br>${response}`,
                        width: '600px'
                    });
                });

                // Handler para el botón "Delete" del DataTable
                $('#tasksTable').on('click', '.deleteTask', function () {
                    const taskId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the task permanently.") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`tasker-linkedin/${taskId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('{{ __("Deleted!") }}', data.success, 'success');
                                    $('#tasksTable').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the task") }}', 'error');
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
