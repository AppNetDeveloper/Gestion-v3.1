<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Campaigns')" />
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
                <a class="btn inline-flex justify-center btn-dark rounded-full items-center p-2" href="{{ route('campaigns.index') }}">
                    <iconify-icon icon="mdi:refresh" class="text-xl"></iconify-icon>
                </a>
            </div>
        </header>

        <div class="card-body px-6 pb-6">
            {{-- Formulario para crear nueva campaña --}}
            <form action="{{ route('campaigns.store') }}" method="POST" class="mt-6 p-4">
                @csrf
                <div class="mb-3">
                    <label for="prompt" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">
                        {{ __('Campaign Prompt') }}
                    </label>
                    <textarea id="prompt" name="prompt" rows="4"
                              class="inputField w-full p-3 border border-slate-300 dark:border-slate-700 rounded-md dark:bg-slate-900"
                              placeholder="{{ __('Enter your campaign prompt...') }}" required>{{ old('prompt') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="campaign_start" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">
                        {{ __('Campaign Start Date & Time') }}
                    </label>
                    <input type="datetime-local" id="campaign_start" name="campaign_start"
                           class="inputField w-full p-3 border border-slate-300 dark:border-slate-700 rounded-md dark:bg-slate-900"
                           placeholder="{{ __('Select date and time') }}" value="{{ old('campaign_start') }}">
                </div>

                <div class="mb-3">
                    <label for="model" class="block text-gray-700 dark:text-gray-300 font-medium mb-2">
                        {{ __('Select Model') }}
                    </label>
                    <select id="model" name="model"
                            class="inputField w-full p-3 border border-slate-300 dark:border-slate-700 rounded-md dark:bg-slate-900">
                        <option value="whatsapp" {{ old('model') == 'whatsapp' ? 'selected' : '' }}>{{ __('WhatsApp') }}</option>
                        <option value="email" {{ old('model') == 'email' ? 'selected' : '' }}>{{ __('Email') }}</option>
                        <option value="sms" {{ old('model') == 'sms' ? 'selected' : '' }}>{{ __('SMS') }}</option>
                        <option value="telegram" {{ old('model') == 'telegram' ? 'selected' : '' }}>{{ __('Telegram') }}</option>
                    </select>
                </div>

                <!-- Botón de acción para crear la campaña -->
                <div class="flex flex-wrap gap-3 mb-3">
                    <button type="submit" class="btn btn-primary rounded-full px-5 py-2 flex items-center">
                        <iconify-icon icon="bi:send-fill" class="mr-2"></iconify-icon>
                        {{ __('Create Campaign') }}
                    </button>
                </div>
            </form>

            <!-- DataTable de campañas programadas -->
            <div class="mt-8 p-4">
                <h2 class="text-xl font-bold mb-4">{{ __('My Scheduled Campaigns') }}</h2>
                <div class="overflow-x-auto">
                    <table id="campaignsTable" class="w-full border-collapse dataTable">
                        <thead class="bg-slate-200 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-2 border-r border-gray-300">{{ __('ID') }}</th>
                                <th class="px-4 py-2 border-r border-gray-300">{{ __('Prompt') }}</th>
                                <th class="px-4 py-2 border-r border-gray-300">{{ __('Model') }}</th>
                                <th class="px-4 py-2 border-r border-gray-300">{{ __('Status') }}</th>
                                <th class="px-4 py-2 border-r border-gray-300">{{ __('Campaign Start') }}</th>
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
            table.dataTable#campaignsTable th,
            table.dataTable#campaignsTable td {
                border-right: 1px solid #ddd;
            }
            table.dataTable#campaignsTable th:last-child,
            table.dataTable#campaignsTable td:last-child {
                border-right: none;
            }
            table.dataTable#campaignsTable tr {
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
        </style>
    @endpush

    @push('scripts')
        <!-- jQuery y SweetAlert2 desde CDN -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <!-- DataTables core -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <!-- DataTables Buttons Extension -->
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
        <!-- Exportación a CSV, Excel, PDF, etc. -->
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
        <!-- Dependencias opcionales para PDF y Excel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/pdfmake.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/vfs_fonts.js"></script>
        <script>
            // Definir el ancho del modal según la pantalla
            let swalWidth = window.innerWidth < 1200 ? (window.innerWidth * 0.9) + 'px' : '1200px';

            // Inicializar DataTable para campañas
            $(document).ready(function() {
                $('#campaignsTable').DataTable({
                    dom: 'ft<"dataTables_footer"ip>',
                    ajax: {
                        // Se utiliza secure_url para forzar HTTPS
                        url: '{{ secure_url("campaigns/data") }}',
                        dataSrc: 'data'
                    },
                    columns: [
                        { data: 'id' },
                        {
                            data: 'prompt',
                            render: function(data, type, row) {
                                return (type === 'display' && data && data.length > 20)
                                    ? data.substring(0, 20) + '...'
                                    : data;
                            }
                        },
                        { data: 'model' },
                        { data: 'status' },
                        { data: 'campaign_start' },
                        { data: 'created_at' },
                        {
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                return `
                                    <span class="cursor-pointer editCampaign inline-block mr-2"
                                        data-id="${row.id}"
                                        data-prompt="${encodeURIComponent(row.prompt)}"
                                        data-campaign-start="${row.campaign_start}"
                                        title="{{ __('Edit Campaign') }}">
                                        <iconify-icon icon="heroicons:pencil" style="font-size: 1.5rem;"></iconify-icon>
                                    </span>
                                    <span class="cursor-pointer deleteCampaign inline-block"
                                        data-id="${row.id}" title="{{ __('Delete Campaign') }}">
                                        <iconify-icon icon="heroicons:trash" style="font-size: 1.5rem;"></iconify-icon>
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

                // Handler para eliminar campaña
                $('#campaignsTable').on('click', '.deleteCampaign', function () {
                    const campaignId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the campaign permanently.") }}',
                        icon: 'warning',
                        width: swalWidth,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/campaigns/${campaignId}`, {
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
                                    $('#campaignsTable').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the campaign") }}', 'error');
                            });
                        }
                    });
                });

                // Handler para editar campaña
                $('#campaignsTable').on('click', '.editCampaign', function () {
                    const campaignId = $(this).data('id');
                    const prompt = decodeURIComponent($(this).data('prompt'));
                    const campaignStart = $(this).data('campaign-start');
                    const formattedDate = campaignStart ? campaignStart.replace(' ', 'T') : '';

                    Swal.fire({
                        title: '{{ __("Edit Campaign") }}',
                        width: swalWidth,
                        html: `
                            <div class="mb-4 text-left">
                                <label class="block font-bold mb-2">{{ __("Select Date and Time") }}</label>
                                <input type="datetime-local" id="edit_campaign_start" class="custom-swal-input" value="${formattedDate}" placeholder="{{ __("Select date and time") }}">
                            </div>
                            <div class="text-left">
                                <label class="block font-bold mb-2">{{ __("Prompt") }}</label>
                                <textarea id="edit_campaign_prompt" class="custom-swal-textarea" style="height:150px; width:90%;">${prompt}</textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        preConfirm: () => {
                            const campaign_start = document.getElementById('edit_campaign_start').value;
                            const campaign_prompt = document.getElementById('edit_campaign_prompt').value;
                            if (!campaign_start) {
                                Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                            }
                            if (!campaign_prompt) {
                                Swal.showValidationMessage('{{ __("Please enter a prompt") }}');
                            }
                            return { campaign_start, campaign_prompt };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            fetch(`/campaigns/${campaignId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    prompt: data.campaign_prompt,
                                    campaign_start: data.campaign_start
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
                                    $('#campaignsTable').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while updating the campaign") }}', 'error');
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
