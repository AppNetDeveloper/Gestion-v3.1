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

    <div class="card shadow rounded-lg">
        <header class="card-header border-b-0">
            <div class="flex justify-end gap-3 items-center flex-wrap p-4">
                {{-- Refresh Button --}}
                <a class="btn inline-flex justify-center btn-dark rounded-full items-center p-2" href="{{ route('linkedin.index') }}">
                    <iconify-icon icon="mdi:refresh" class="text-xl"></iconify-icon>
                </a>
            </div>
        </header>

        <div class="card-body px-6 pb-6">
            @if($token)
                <div class="flex items-center justify-between mb-4 p-4">
                    <div class="flex items-center space-x-3">
                        <iconify-icon icon="mdi:check-circle" class="text-green-500 text-lg"></iconify-icon>
                        <p class="text-gray-700 dark:text-gray-300 text-lg">
                            {{ __('Your LinkedIn account is connected successfully.') }}
                        </p>
                    </div>
                    {{-- Botón para desvincular --}}
                    <button id="btnDisconnect" class="btn btn-danger rounded-full px-4 py-2 flex items-center">
                        <iconify-icon icon="mdi:link-off" class="mr-2"></iconify-icon>
                        {{ __('Disconnect') }}
                    </button>
                </div>

                <form action="{{ route('linkedin.post') }}" method="POST" class="mt-6 p-4">
                    @csrf
                    <div class="mb-3">
                        <label for="content" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">
                            {{ __('Write your post') }}
                        </label>
                        <textarea id="content" name="content" rows="4"
                                  class="inputField w-full p-3 border border-slate-300 dark:border-slate-700 rounded-md dark:bg-slate-900"
                                  placeholder="{{ __('Share something on LinkedIn...') }}" required></textarea>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-wrap gap-3 mb-3">
                        <!-- Procesar texto con IA -->
                        <button type="button" id="btnProcessIA" class="btn btn-secondary rounded-full px-5 py-2 flex items-center">
                            <iconify-icon icon="mdi:robot" class="mr-2"></iconify-icon>
                            {{ __('Process text with AI') }}
                        </button>

                        <!-- Programar publicación -->
                        <button type="button" id="btnSchedule" class="btn btn-info rounded-full px-5 py-2 flex items-center">
                            <iconify-icon icon="mdi:calendar-clock" class="mr-2"></iconify-icon>
                            {{ __('Schedule') }}
                        </button>

                        <!-- Publicar en LinkedIn -->
                        <button type="submit" class="btn btn-primary rounded-full px-5 py-2 flex items-center">
                            <iconify-icon icon="bi:send-fill" class="mr-2"></iconify-icon>
                            {{ __('Publish on LinkedIn') }}
                        </button>
                    </div>
                </form>

                <!-- DataTable de tareas programadas -->
                <div class="mt-8 p-4">
                    <h2 class="text-xl font-bold mb-4">{{ __('My Scheduled Tasks') }}</h2>
                    <div class="overflow-x-auto">
                        <table id="tasksTable" class="w-full border-collapse dataTable">
                            <thead class="bg-slate-200 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('ID') }}</th>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('Prompt') }}</th>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('Response') }}</th>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('Status') }}</th>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('Error') }}</th>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('Publish Date') }}</th>
                                    <th class="px-4 py-2 border-r border-gray-300">{{ __('Created At') }}</th>
                                    <th class="px-4 py-2">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800">
                                {{-- Los datos se cargarán vía AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <a href="{{ route('linkedin.auth') }}" class="btn btn-outline-primary rounded-full px-5 py-2 flex items-center">
                        <iconify-icon icon="bi:linkedin"></iconify-icon>
                        <span class="ml-2">{{ __('Connect with LinkedIn') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <!-- DataTables CSS desde CDN -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <!-- FontAwesome para íconos -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
              integrity="sha512-Fo3rlrZj/kTc0N9E2ljSz+6CQ2s5Qd0lfQbDk9IY5j5kEjl+gST9zlE98yFux/NUYg5A28L2lyP9T8HZO8+5mw=="
              crossorigin="anonymous" referrerpolicy="no-referrer" />

        <style>
            /* Bordes entre columnas de la DataTable */
            table.dataTable#tasksTable th,
            table.dataTable#tasksTable td {
                border-right: 1px solid #ddd;
            }
            table.dataTable#tasksTable th:last-child,
            table.dataTable#tasksTable td:last-child {
                border-right: none;
            }
            table.dataTable#tasksTable tr {
                border-bottom: 1px solid #ddd;
            }
            /* Indicadores de ordenamiento (usando FontAwesome) */
            table.dataTable thead th.sorting:after,
            table.dataTable thead th.sorting_asc:after,
            table.dataTable thead th.sorting_desc:after {
                display: inline-block;
                font-family: "FontAwesome";
                margin-left: 5px;
                opacity: 0.5;
            }
            table.dataTable thead th.sorting:after {
                content: "\f0dc";
            }
            table.dataTable thead th.sorting_asc:after {
                content: "\f0de";
            }
            table.dataTable thead th.sorting_desc:after {
                content: "\f0dd";
            }
            /* Personalización para SweetAlert2: modal grande */
            .swal2-modal {
                width: 90% !important;
                max-width: 1200px !important;
            }
            /* Estilos personalizados para los inputs de SweetAlert2 */
            .custom-swal-input,
            .custom-swal-textarea {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
                padding: 0.5rem !important;
                display: block !important;
                border: 1px solid #ccc !important;
                border-radius: 0.375rem !important;
            }
            .custom-swal-input:focus,
            .custom-swal-textarea:focus {
                outline: none !important;
                border-color: #3182ce !important;
                box-shadow: 0 0 0 1px #3182ce !important;
            }
            /* Estilos para los botones de paginación de DataTables */
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                display: inline-block !important;
                padding: 0.5rem 1rem !important;
                margin: 0 0.25rem !important;
                border: 1px solid #ddd !important;
                border-radius: 0.375rem !important;
                background-color: #f7fafc !important;
                color: #2d3748 !important;
                cursor: pointer !important;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background-color: #3182ce !important;
                color: #fff !important;
                border-color: #3182ce !important;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background-color: #e2e8f0 !important;
                border-color: #cbd5e0 !important;
            }
            /* Footer de DataTables */
            .dataTables_footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 1rem;
            }
            /* Forzar que el botón de "Repeat Task" se muestre */
            .repeatTask {
                display: inline-block !important;
                color: #2d3748 !important;
                font-size: 1.5rem !important;
            }
        </style>
    @endpush

    @push('scripts')
        <!-- SweetAlert2 desde CDN -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <!-- 4) DataTables “core” -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

        <!-- 5) Extensión Buttons -->
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>

        <!-- 6) Para exportar a CSV, Excel, PDF, etc. (HTML5 export) -->
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

        <!-- 7) Dependencias opcionales para PDF y Excel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/pdfmake.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/vfs_fonts.js"></script>
        <script>
            // Se define el ancho del modal según la pantalla
            let swalWidth = window.innerWidth < 1200 ? (window.innerWidth * 0.9) + 'px' : '1200px';

            // --- Desvinculación ---
            document.getElementById('btnDisconnect').addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("Are you sure?") }}',
                    text: '{{ __("If you confirm, your LinkedIn account will be disconnected and you'll have to log in again.") }}',
                    icon: 'warning',
                    width: swalWidth,
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
                                Swal.fire({
                                    title: '{{ __("Disconnected") }}',
                                    text: data.success,
                                    icon: 'success',
                                    width: swalWidth
                                }).then(() => window.location.reload());
                            } else {
                                Swal.fire('{{ __("Error") }}', data.error, 'error');
                            }
                        })
                        .catch(error => Swal.fire('{{ __("Error") }}', '{{ __("Unable to complete the action.") }}', 'error'));
                    }
                });
            });

            // --- Procesar texto con IA (Ollama) ---
            document.getElementById('btnProcessIA').addEventListener('click', function () {
                const textarea = document.getElementById('content');
                const prefix = "Crea una publicación profesional y atractiva para LinkedIn, pero sin escribir nada de cabezal sobre te escribo una publicacion o algo parecido, siguiendo estas directrices: ";
                const userPrompt = textarea.value;
                const suffix = " Mantén un tono profesional, cercano y humano. Usa un lenguaje claro, inspirador y persuasivo que motive a la acción. Si no tienes la información para completar tus textos, no pongas la parte que te falta. Pon solo datos concretos y que tienes; no inventes nada ni dejes partes incompletas.";
                const prompt = prefix + userPrompt + suffix;
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

            // --- Programar publicación (Schedule) ---
            document.getElementById('btnSchedule').addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("Schedule Publication") }}',
                    width: swalWidth,
                    html: `
                        <div class="mb-4 text-left">
                            <label class="block font-bold mb-2">{{ __("Select Date and Time") }}</label>
                            <input type="datetime-local" id="publish_date" class="custom-swal-input" placeholder="{{ __("Select date and time") }}">
                        </div>
                        <div class="text-left">
                            <label class="block font-bold mb-2">{{ __("Prompt") }}</label>
                            <textarea id="task_prompt" class="custom-swal-textarea" style="height:150px; width:90%;" placeholder="{{ __("Enter prompt") }}"></textarea>
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
                                Swal.fire({
                                    title: '{{ __("Saved!") }}',
                                    text: resp.success,
                                    icon: 'success',
                                    width: swalWidth
                                });
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

            // --- Inicializar DataTable ---
            $(document).ready(function() {
                $('#tasksTable').DataTable({
                    // Se coloca la tabla y luego el footer debajo
                    dom: 'ft<"dataTables_footer"ip>',
                    ajax: {
                        url: 'tasker-linkedin/data',
                        dataSrc: 'data'
                    },
                    columns: [
                        { data: 'id' },
                        {
                            data: 'prompt',
                            render: function(data, type, row) {
                                return (type === 'display' && data && data.length > 20) ? data.substring(0, 20) + '...' : data;
                            }
                        },
                        {
                            data: 'response',
                            render: function(data, type, row) {
                                return (type === 'display' && data && data.length > 20) ? data.substring(0, 20) + '...' : data;
                            }
                        },
                        { data: 'status' },
                        {
                            data: 'error',
                            render: function(data, type, row) {
                                return (type === 'display' && data && data.length > 20) ? data.substring(0, 20) + '...' : data;
                            }
                        },
                        { data: 'publish_date' },
                        { data: 'created_at' },
                        {
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                return `
                                    <span class="cursor-pointer editTask inline-block mr-2"
                                        data-id="${row.id}"
                                        data-prompt="${encodeURIComponent(row.prompt)}"
                                        data-publish-date="${row.publish_date}"
                                        data-response="${encodeURIComponent(row.response)}"
                                        title="{{ __('Edit Task') }}">
                                        <iconify-icon icon="heroicons:pencil" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                    <span class="cursor-pointer viewDetails inline-block mr-2"
                                        data-prompt="${encodeURIComponent(row.prompt)}"
                                        data-response="${encodeURIComponent(row.response)}" title="{{ __('View Details') }}">
                                        <iconify-icon icon="heroicons:eye" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                    <span class="cursor-pointer deleteTask inline-block mr-2"
                                        data-id="${row.id}" title="{{ __('Delete Task') }}">
                                        <iconify-icon icon="heroicons:trash" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                    <span class="cursor-pointer repeatTask inline-block"
                                        data-prompt="${encodeURIComponent(row.prompt)}"
                                        title="{{ __('Repeat Task') }}">
                                        <iconify-icon icon="material-symbols:repeat" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                `;
                            }
                        }
                    ],
                    order: [[0, "desc"]],
                    responsive: true,
                    autoWidth: false,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json"
                    },
                });

                // --- Handler para "View" ---
                $('#tasksTable').on('click', '.viewDetails', function () {
                    const prompt = decodeURIComponent($(this).data('prompt'));
                    const response = decodeURIComponent($(this).data('response'));
                    Swal.fire({
                        title: '{{ __("Task Details") }}',
                        html: `<strong>{{ __("Prompt") }}:</strong><br>${prompt}<br><br><strong>{{ __("Response") }}:</strong><br>${response}`,
                        width: swalWidth
                    });
                });

                // --- Handler para "Delete" ---
                $('#tasksTable').on('click', '.deleteTask', function () {
                    const taskId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the task permanently.") }}',
                        icon: 'warning',
                        width: swalWidth,
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
                                    Swal.fire({
                                        title: '{{ __("Deleted!") }}',
                                        text: data.success,
                                        icon: 'success',
                                        width: swalWidth
                                    });
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

                // --- Handler para "Edit" ---
                $('#tasksTable').on('click', '.editTask', function () {
                    const taskId = $(this).data('id');
                    const prompt = decodeURIComponent($(this).data('prompt'));
                    const publishDate = $(this).data('publish-date');
                    const responseText = decodeURIComponent($(this).data('response'));
                    const formattedDate = publishDate ? publishDate.replace(' ', 'T') : '';

                    Swal.fire({
                        title: '{{ __("Edit Task") }}',
                        width: swalWidth,
                        html: `
                            <div class="mb-4 text-left">
                                <label class="block font-bold mb-2">{{ __("Select Date and Time") }}</label>
                                <input type="datetime-local" id="edit_publish_date" class="custom-swal-input" value="${formattedDate}" placeholder="{{ __("Select date and time") }}">
                            </div>
                            <div class="text-left">
                                <label class="block font-bold mb-2">{{ __("Prompt") }}</label>
                                <textarea id="edit_task_prompt" class="custom-swal-textarea" style="height:150px; width:90%;">${prompt}</textarea>
                            </div>
                            <div class="mt-4 text-left">
                                <label class="block font-bold mb-2">{{ __("Response") }}</label>
                                <textarea id="edit_task_response" class="custom-swal-textarea" style="height:150px; width:90%;">${responseText}</textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        preConfirm: () => {
                            const publish_date = document.getElementById('edit_publish_date').value;
                            const task_prompt = document.getElementById('edit_task_prompt').value;
                            const task_response = document.getElementById('edit_task_response').value;
                            if (!publish_date) {
                                Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                            }
                            if (!task_prompt) {
                                Swal.showValidationMessage('{{ __("Please enter a prompt") }}');
                            }
                            return { publish_date, task_prompt, task_response };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            fetch(`/linkedin/${taskId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    prompt: data.task_prompt,
                                    publish_date: data.publish_date,
                                    response: data.task_response
                                })
                            })
                            .then(response => response.json())
                            .then(resp => {
                                if (resp.success) {
                                    Swal.fire({
                                        title: '{{ __("Updated!") }}',
                                        text: resp.success,
                                        icon: 'success',
                                        width: swalWidth
                                    });
                                    $('#tasksTable').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while updating the task") }}', 'error');
                            });
                        }
                    });
                });

                // --- Handler para "Reprogramar" (Repeat Task) ---
                $('#tasksTable').on('click', '.repeatTask', function () {
                    const originalPrompt = decodeURIComponent($(this).data('prompt'));
                    Swal.fire({
                        title: '{{ __("Reprogram Task") }}',
                        width: swalWidth,
                        html: `
                            <div class="mb-4 text-left">
                                <label class="block font-bold mb-2">{{ __("Select Date and Time") }}</label>
                                <input type="datetime-local" id="repeat_publish_date" class="custom-swal-input" placeholder="{{ __("Select date and time") }}">
                            </div>
                            <div class="text-left">
                                <label class="block font-bold mb-2">{{ __("Prompt") }}</label>
                                <textarea id="repeat_task_prompt" class="custom-swal-textarea" style="height:150px; width:90%;">${originalPrompt}</textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save") }}',
                        preConfirm: () => {
                            const publish_date = document.getElementById('repeat_publish_date').value;
                            const task_prompt = document.getElementById('repeat_task_prompt').value;
                            if (!publish_date) {
                                Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                            }
                            return { publish_date, task_prompt };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            fetch('tasker-linkedin/store', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    prompt: data.task_prompt,
                                    publish_date: data.publish_date
                                })
                            })
                            .then(response => response.json())
                            .then(resp => {
                                if (resp.success) {
                                    Swal.fire({
                                        title: '{{ __("Saved!") }}',
                                        text: resp.success,
                                        icon: 'success',
                                        width: swalWidth
                                    });
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
            });
        </script>
    @endpush
</x-app-layout>
