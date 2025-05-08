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
    {{-- Mostrar errores de validación del formulario de creación --}}
    @if ($errors->any() && old('_token')) {{-- old('_token') ayuda a asegurar que los errores son de este formulario --}}
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
            <p class="font-semibold mb-2">{{ __('Please correct the following errors:') }}</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- Alert end --}}

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            {{-- Formulario colapsable para crear nueva campaña --}}
            <div class="mb-8 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                {{-- Encabezado para desplegar/colapsar --}}
                <div id="toggleCampaignFormHeader" class="flex justify-between items-center cursor-pointer">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Create New Campaign') }}</h3>
                    <button type="button" aria-expanded="false" aria-controls="campaignFormContainer"
                            class="bg-indigo-100 hover:bg-indigo-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-indigo-600 dark:text-indigo-400 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800
                                   flex items-center justify-center w-8 h-8 transition-colors duration-150">
                        <iconify-icon id="campaignFormToggleIcon" icon="heroicons:plus-circle-20-solid" class="text-2xl transition-transform duration-300 ease-in-out"></iconify-icon>
                    </button>
                </div>

                {{-- Contenedor del formulario --}}
                <div id="campaignFormContainer" class="overflow-hidden"> {{-- mt-6 se maneja con CSS/JS --}}
                    <form action="{{ route('campaigns.store') }}" method="POST" class="space-y-6 pt-6">
                        @csrf
                        <div>
                            <label for="prompt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Campaign Prompt') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea id="prompt" name="prompt" rows="4"
                                      class="inputField w-full p-3 border {{ $errors->has('prompt') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                      placeholder="{{ __('Enter your campaign prompt...') }}" required>{{ old('prompt') }}</textarea>
                            @error('prompt')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="campaign_start" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Campaign Start Date & Time') }}
                            </label>
                            <input type="datetime-local" id="campaign_start" name="campaign_start"
                                   class="inputField w-full p-3 border {{ $errors->has('campaign_start') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition"
                                   placeholder="{{ __('Select date and time') }}" value="{{ old('campaign_start') }}">
                             @error('campaign_start')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="model" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Select Model') }} <span class="text-red-500">*</span>
                            </label>
                            <select id="model" name="model"
                                    class="inputField w-full p-3 border {{ $errors->has('model') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                                <option value="" disabled {{ old('model') ? '' : 'selected' }}>{{ __('Select an option') }}</option>
                                <option value="whatsapp" {{ old('model') == 'whatsapp' ? 'selected' : '' }}>{{ __('WhatsApp') }}</option>
                                <option value="email" {{ old('model') == 'email' ? 'selected' : '' }}>{{ __('Email') }}</option>
                                <option value="sms" {{ old('model') == 'sms' ? 'selected' : '' }}>{{ __('SMS') }}</option>
                                <option value="telegram" {{ old('model') == 'telegram' ? 'selected' : '' }}>{{ __('Telegram') }}</option>
                            </select>
                             @error('model')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Botón de Crear Campaña --}}
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="px-6 py-2.5 bg-green-500 hover:bg-green-600 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-full flex items-center transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                                <iconify-icon icon="bi:send-fill" class="mr-2"></iconify-icon>
                                {{ __('Create Campaign') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('My Scheduled Campaigns') }}</h3>
                <div class="overflow-x-auto">
                    <table id="campaignsTable" class="w-full border-collapse dataTable">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('ID') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Prompt') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Model') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Campaign Start') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Created At') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            {{-- Los datos se cargarán vía AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos adicionales --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> --}}

        <style>
            .inputField:focus { /* Tailwind's focus classes handle this */ }
            table.dataTable#campaignsTable { border-spacing: 0; }
            table.dataTable#campaignsTable th, table.dataTable#campaignsTable td { padding: 0.75rem 1rem; vertical-align: middle; }
            table.dataTable#campaignsTable tbody tr:hover { background-color: #f9fafb; }
            .dark table.dataTable#campaignsTable tbody tr:hover { background-color: #1f2937; }
            table.dataTable thead th.sorting:after, table.dataTable thead th.sorting_asc:after, table.dataTable thead th.sorting_desc:after { display: inline-block; margin-left: 5px; opacity: 0.5; color: inherit; }
            table.dataTable thead th.sorting:after { content: "\\2195"; }
            table.dataTable thead th.sorting_asc:after { content: "\\2191"; }
            table.dataTable thead th.sorting_desc:after { content: "\\2193"; }
            .swal2-popup { width: 90% !important; max-width: 1000px !important; border-radius: 0.5rem !important; }
            .dark .swal2-popup { background: #1f2937 !important; color: #d1d5db !important; }
            .dark .swal2-title { color: #f3f4f6 !important; }
            .dark .swal2-html-container { color: #d1d5db !important; }
            .dark .swal2-actions button.swal2-confirm { background-color: #4f46e5 !important; }
            .dark .swal2-actions button.swal2-cancel { background-color: #4b5563 !important; }
            .custom-swal-input, .custom-swal-textarea { width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; padding: 0.75rem !important; display: block !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #fff !important; color: #111827 !important; }
            .custom-swal-input:focus, .custom-swal-textarea:focus { outline: none !important; border-color: #4f46e5 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3) !important; }
            .dark .custom-swal-input, .dark .custom-swal-textarea { background-color: #374151 !important; border-color: #4b5563 !important; color: #f3f4f6 !important; }
            .dark .custom-swal-input:focus, .dark .custom-swal-textarea:focus { border-color: #6366f1 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.4) !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center !important; justify-content: center !important; padding: 0.5rem 1rem !important; margin: 0 0.125rem !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #f9fafb !important; color: #374151 !important; cursor: pointer !important; transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, color 0.15s ease-in-out; }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background-color: #f3f4f6 !important; border-color: #9ca3af !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button { background-color: #374151 !important; color: #d1d5db !important; border-color: #4b5563 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background-color: #4b5563 !important; border-color: #6b7280 !important; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding-top: 1rem; }
            .dataTables_wrapper .dataTables_filter input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_filter input { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
            .dataTables_wrapper .dataTables_length select { padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background-color: #fff; }
            .dark .dataTables_wrapper .dataTables_length select { background-color: #374151; border-color: #4b5563; color: #f3f4f6; }
            .dataTables_footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
            .dark .dataTables_footer { border-top-color: #374151; }

            /* Estilos para el contenedor colapsable */
            #campaignFormContainer {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out;
                opacity: 0;
                padding-top: 0 !important;
            }
            #campaignFormContainer.expanded {
                max-height: 1000px; /* Ajusta si tu formulario es muy alto */
                opacity: 1;
                padding-top: 1.5rem !important; /* Corresponde a pt-6 en el form */
            }
            #campaignFormToggleIcon.rotated {
                transform: rotate(180deg);
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            $(document).ready(function() {
                // Lógica para el formulario colapsable de Campañas
                const campaignToggleHeader = document.getElementById('toggleCampaignFormHeader');
                const campaignFormContainer = document.getElementById('campaignFormContainer');
                const campaignToggleIconElement = document.getElementById('campaignFormToggleIcon');

                if (campaignToggleHeader && campaignFormContainer && campaignToggleIconElement) {
                    function setCampaignFormState(expand, animate = true) {
                        if (!animate) {
                            campaignFormContainer.style.transition = 'none';
                        } else {
                            campaignFormContainer.style.transition = 'max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out';
                        }

                        if (expand) {
                            campaignFormContainer.classList.add('expanded');
                            campaignToggleIconElement.setAttribute('icon', 'heroicons:minus-circle-20-solid');
                            campaignToggleIconElement.classList.add('rotated');
                            campaignToggleHeader.setAttribute('aria-expanded', 'true');
                        } else {
                            campaignFormContainer.classList.remove('expanded');
                            campaignToggleIconElement.setAttribute('icon', 'heroicons:plus-circle-20-solid');
                            campaignToggleIconElement.classList.remove('rotated');
                            campaignToggleHeader.setAttribute('aria-expanded', 'false');
                        }

                         if (!animate) {
                           requestAnimationFrame(() => {
                                campaignFormContainer.style.transition = 'max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out';
                           });
                        }
                    }

                    // Comprobar si hay errores de validación para el formulario de campañas
                    const campaignHasValidationErrors = {{ ($errors->any() && old('_token')) ? 'true' : 'false' }};
                    setCampaignFormState(campaignHasValidationErrors, false); // Establecer estado inicial sin animación

                    campaignToggleHeader.addEventListener('click', function () {
                        const isExpanded = campaignFormContainer.classList.contains('expanded');
                        setCampaignFormState(!isExpanded); // Alternar estado con animación
                    });
                } else {
                     console.error('Campaign toggle elements not found! Check IDs: toggleCampaignFormHeader, campaignFormContainer, campaignFormToggleIcon');
                }


                // Initialize DataTable and store its instance
                const campaignsDataTable = $('#campaignsTable').DataTable({
                    dom: "<'flex flex-col md:flex-row md:justify-between gap-4 mb-4'<'md:w-1/2'l><'md:w-1/2'f>>" +
                         "<'overflow-x-auto't>" +
                         "<'flex flex-col md:flex-row md:justify-between gap-4 mt-4'<'md:w-1/2'i><'md:w-1/2'p>>",
                    ajax: {
                        url: '{{ secure_url("campaigns/data") }}',
                        dataSrc: 'data',
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("AJAX error: ", textStatus, errorThrown);
                            $('#campaignsTable tbody').html(
                                '<tr><td colspan="7" class="text-center py-10 text-red-500">{{ __("Error loading data. Please try again.") }}</td></tr>'
                            );
                        }
                    },
                    columns: [
                        { data: 'id', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        {
                            data: 'prompt',
                            className: 'text-sm text-slate-700 dark:text-slate-300',
                            render: function(data, type, row) {
                                if (type === 'display' && data && data.length > 30) {
                                    return `<span title="${encodeURIComponent(data)}">${data.substring(0, 30)}...</span>`;
                                }
                                return data;
                            }
                        },
                        { data: 'model', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        {
                            data: 'status',
                            className: 'text-sm',
                            render: function(data, type, row) {
                                let colorClass = 'text-slate-700 dark:text-slate-300';
                                if (data === 'Sent' || data === 'Completed') colorClass = 'text-green-600 dark:text-green-400';
                                if (data === 'Failed') colorClass = 'text-red-600 dark:text-red-400';
                                if (data === 'Pending' || data === 'Scheduled') colorClass = 'text-yellow-600 dark:text-yellow-400';
                                return `<span class="${colorClass}">${data}</span>`;
                            }
                        },
                        { data: 'campaign_start', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        { data: 'created_at', className: 'text-sm text-slate-700 dark:text-slate-300' },
                        {
                            data: null,
                            orderable: false,
                            className: 'text-sm text-center',
                            render: function(data, type, row) {
                                return `
                                    <div class="flex items-center justify-center space-x-2">
                                        <button class="editCampaign text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-150 p-1"
                                            data-id="${row.id}"
                                            data-prompt="${encodeURIComponent(row.prompt)}"
                                            data-campaign-start="${row.campaign_start}"
                                            title="{{ __('Edit Campaign') }}">
                                            <iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon>
                                        </button>
                                        <button class="deleteCampaign text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150 p-1"
                                            data-id="${row.id}" title="{{ __('Delete Campaign') }}">
                                            <iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[0, "desc"]],
                    responsive: true,
                    autoWidth: false,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() === 'es' ? 'Spanish' : 'English' }}.json",
                        search: "_INPUT_",
                        searchPlaceholder: "{{ __('Search records...') }}",
                        lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}"
                    },
                    initComplete: function(settings, json) {
                        $('.dataTables_filter input').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                        $('.dataTables_length select').addClass('inputField px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition');
                    }
                });

                // Auto-refresh DataTable every 1 minute without resetting pagination
                setInterval(function () {
                    if (campaignsDataTable) {
                        if (Swal.isVisible()) {
                            // console.log('SweetAlert2 modal is visible, skipping table refresh.'); // For debugging
                            return;
                        }
                        // console.log('Refreshing campaigns table data...'); // For debugging
                        campaignsDataTable.ajax.reload(null, false); // false to keep current pagination
                    }
                }, 60000); // 60000 milliseconds = 1 minute

                // Handler for deleting a campaign
                $('#campaignsTable').on('click', '.deleteCampaign', function () {
                    const campaignId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the campaign permanently.") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#e11d48', // Red-600 for confirm
                        cancelButtonColor: '#64748b',  // Slate-500 for cancel
                        customClass: {
                            popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '', // For any dark mode specific overrides
                        }
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
                                    });
                                    campaignsDataTable.ajax.reload(null, false); // Reload without resetting pagination
                                } else {
                                    Swal.fire('{{ __("Error") }}', data.error || '{{ __("An error occurred") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Delete error:', error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while deleting the campaign.") }}', 'error');
                            });
                        }
                    });
                });

                // Handler for editing a campaign
                $('#campaignsTable').on('click', '.editCampaign', function () {
                    const campaignId = $(this).data('id');
                    const prompt = decodeURIComponent($(this).data('prompt'));
                    const campaignStart = $(this).data('campaign-start');
                    const formattedDate = campaignStart ? campaignStart.replace(' ', 'T').substring(0, 16) : '';

                    Swal.fire({
                        title: '{{ __("Edit Campaign") }}',
                        html: `
                            <div class="space-y-4 text-left">
                                <div>
                                    <label for="edit_campaign_start" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Campaign Start Date & Time") }}</label>
                                    <input type="datetime-local" id="edit_campaign_start" class="custom-swal-input" value="${formattedDate}" placeholder="{{ __("Select date and time") }}">
                                </div>
                                <div>
                                    <label for="edit_campaign_prompt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Prompt") }}</label>
                                    <textarea id="edit_campaign_prompt" class="custom-swal-textarea" rows="5" style="min-height:100px;">${prompt}</textarea>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#4f46e5', // Indigo-600 for confirm
                        customClass: {
                             popup: $('html').hasClass('dark') ? 'dark-swal-popup' : '', // For any dark mode specific overrides
                        },
                        preConfirm: () => {
                            const newCampaignStart = document.getElementById('edit_campaign_start').value;
                            const newCampaignPrompt = document.getElementById('edit_campaign_prompt').value;
                            let errors = [];
                            if (!newCampaignStart) {
                                errors.push('{{ __("Please select a date and time.") }}');
                            }
                            if (!newCampaignPrompt.trim()) {
                                errors.push('{{ __("Please enter a prompt.") }}');
                            }

                            if (errors.length > 0) {
                                Swal.showValidationMessage(errors.join('<br>'));
                                return false; // Prevent closing
                            }
                            return { campaign_start: newCampaignStart, campaign_prompt: newCampaignPrompt };
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
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
                                    });
                                    campaignsDataTable.ajax.reload(null, false); // Reload without resetting pagination
                                } else {
                                    Swal.fire('{{ __("Error") }}', resp.error || '{{ __("An error occurred while updating.") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Update error:', error);
                                Swal.fire('{{ __("Error") }}', '{{ __("An error occurred while updating the campaign.") }}', 'error');
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
