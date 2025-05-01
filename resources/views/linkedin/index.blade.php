<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Publish on LinkedIn')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" class="mb-5" />
    @endif

    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" class="mb-5" />
    @endif
    {{-- Alert end --}}

    <div class="card shadow rounded-lg overflow-hidden">
        {{-- Card Header --}}
        <div class="bg-white dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
                <h4 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                    {{ __('LinkedIn Publisher') }}
                </h4>
                {{-- Refresh Button --}}
                <a class="btn inline-flex justify-center btn-dark dark:btn-secondary rounded-full items-center p-2 h-10 w-10" href="{{ route('linkedin.index') }}" title="{{ __('Refresh') }}">
                    <iconify-icon icon="mdi:refresh" class="text-xl"></iconify-icon>
                </a>
            </div>
        </div>

        {{-- Card Body --}}
        <div class="p-6 bg-white dark:bg-slate-800">
            @if($token)
                {{-- Connection Status --}}
                <div class="flex flex-col sm:flex-row items-center justify-between mb-6 p-4 bg-green-100 dark:bg-green-800/30 border border-green-200 dark:border-green-700 rounded-md">
                    <div class="flex items-center space-x-3 mb-3 sm:mb-0">
                        <iconify-icon icon="mdi:check-circle" class="text-green-500 text-2xl"></iconify-icon>
                        <p class="text-green-700 dark:text-green-300 text-base font-medium">
                            {{ __('Your LinkedIn account is connected successfully.') }}
                        </p>
                    </div>
                    {{-- Disconnect Button --}}
                    <button id="btnDisconnect" class="btn btn-danger light rounded-full px-4 py-2 flex items-center text-sm">
                        <iconify-icon icon="mdi:link-off" class="mr-2 text-lg"></iconify-icon>
                        {{ __('Disconnect') }}
                    </button>
                </div>

                {{-- Post Form --}}
                <form action="{{ route('linkedin.post') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="content" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            {{ __('Write your post') }}
                        </label>
                        <textarea id="content" name="content" rows="5"
                                  class="inputField w-full p-3 border border-slate-300 dark:border-slate-700 rounded-md dark:bg-slate-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition duration-150"
                                  placeholder="{{ __('Share something inspiring or informative on LinkedIn...') }}" required></textarea>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap gap-3 items-center border-t border-slate-200 dark:border-slate-700 pt-6">
                        {{-- Process with AI --}}
                        <button type="button" id="btnProcessIA" class="btn btn-secondary dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 rounded-full px-5 py-2 flex items-center text-sm font-medium transition duration-150">
                            <iconify-icon icon="mdi:robot-outline" class="mr-2 text-lg"></iconify-icon>
                            {{ __('Process text with AI') }}
                        </button>

                        {{-- Schedule --}}
                        <button type="button" id="btnSchedule" class="btn btn-info rounded-full px-5 py-2 flex items-center text-sm font-medium transition duration-150">
                            <iconify-icon icon="mdi:calendar-clock-outline" class="mr-2 text-lg"></iconify-icon>
                            {{ __('Schedule') }}
                        </button>

                        {{-- Publish Now --}}
                        <button type="submit" class="btn btn-primary rounded-full px-5 py-2 flex items-center text-sm font-medium transition duration-150 ml-auto">
                            <iconify-icon icon="bi:send" class="mr-2 text-lg"></iconify-icon> {{-- Changed icon for consistency --}}
                            {{ __('Publish on LinkedIn') }}
                        </button>
                    </div>
                </form>

                {{-- Scheduled Tasks DataTable --}}
                <div class="mt-10 pt-6 border-t border-slate-200 dark:border-slate-700">
                    <h2 class="text-xl font-semibold mb-5 text-slate-900 dark:text-slate-100">{{ __('My Scheduled Tasks') }}</h2>
                    <div class="overflow-x-auto -mx-6 px-6">
                        {{-- Ensure table ID matches the one used in JavaScript --}}
                        <table id="tasksTable" class="w-full min-w-[800px] dataTable">
                            <thead class="bg-slate-100 dark:bg-slate-700 text-xs uppercase text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('ID') }}</th>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('Prompt') }}</th>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('Response') }}</th>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('Error') }}</th>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('Publish Date') }}</th>
                                    <th class="px-4 py-3 font-medium text-left">{{ __('Created At') }}</th>
                                    <th class="px-4 py-3 font-medium text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-300">
                                {{-- Data loaded via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- Connect Button --}}
                <div class="text-center py-12">
                     <iconify-icon icon="bi:linkedin" class="text-5xl text-primary-500 mb-4"></iconify-icon>
                     <h5 class="text-lg font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Connect Your Account') }}</h5>
                     <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">{{ __('Connect your LinkedIn account to start publishing and scheduling posts.') }}</p>
                    <a href="{{ route('linkedin.auth') }}" class="btn btn-primary rounded-full px-6 py-2.5 inline-flex items-center text-sm font-medium">
                        <iconify-icon icon="bi:linkedin" class="mr-2 text-lg"></iconify-icon>
                        {{ __('Connect with LinkedIn') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Additional Styles --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css"> {{-- Using Tailwind adapter --}}
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> --}}

        <style>
            /* --- DataTables Styling Adjustments --- */

            /* General table adjustments */
            #tasksTable {
                border-collapse: separate; /* Use separate borders */
                border-spacing: 0;
                border: 1px solid #e2e8f0; /* light: slate-200 */
                border-radius: 0.5rem; /* rounded-lg */
                overflow: hidden; /* Clip content to rounded corners */
            }
            .dark #tasksTable {
                border-color: #334155; /* dark: slate-700 */
            }

            /* Header styling */
            #tasksTable thead th {
                border-bottom: 1px solid #e2e8f0; /* light: slate-200 */
                padding-left: 1rem; /* px-4 */
                padding-right: 1rem; /* px-4 */
                padding-top: 0.75rem; /* py-3 */
                padding-bottom: 0.75rem; /* py-3 */
            }
             .dark #tasksTable thead th {
                 border-bottom-color: #334155; /* dark: slate-700 */
             }

            /* Body cell styling */
            #tasksTable tbody td {
                padding: 0.75rem 1rem; /* p-3 px-4 */
                vertical-align: middle;
            }

            /* Row borders */
            #tasksTable tbody tr {
                border-bottom: 1px solid #e2e8f0; /* light: slate-200 */
            }
            .dark #tasksTable tbody tr {
                 border-bottom-color: #334155; /* dark: slate-700 */
            }
            #tasksTable tbody tr:last-child {
                border-bottom: none; /* No border for the last row */
            }

            /* Action buttons alignment */
            #tasksTable td:last-child {
                text-align: center;
            }
            #tasksTable .actions-wrapper {
                display: inline-flex;
                gap: 0.5rem; /* space-x-2 */
                align-items: center;
            }
            #tasksTable .action-icon {
                display: inline-block;
                color: #64748b; /* slate-500 */
                transition: color 0.15s ease-in-out;
                font-size: 1.25rem; /* text-xl */
                cursor: pointer;
            }
            .dark #tasksTable .action-icon {
                color: #94a3b8; /* slate-400 */
            }
            #tasksTable .action-icon:hover {
                color: #1e293b; /* slate-800 */
            }
            .dark #tasksTable .action-icon:hover {
                color: #f1f5f9; /* slate-100 */
            }
            #tasksTable .action-icon.deleteTask:hover {
                 color: #ef4444; /* red-500 */
            }
             #tasksTable .action-icon.editTask:hover {
                 color: #3b82f6; /* blue-500 */
            }
             #tasksTable .action-icon.viewDetails:hover {
                 color: #10b981; /* emerald-500 */
            }
             #tasksTable .action-icon.repeatTask:hover {
                 color: #f59e0b; /* amber-500 */
            }


            /* DataTables Controls (Search, Pagination) */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                margin-bottom: 1.5rem; /* mb-6 */
            }
            .dataTables_wrapper .dataTables_filter input {
                border: 1px solid #cbd5e1; /* slate-300 */
                border-radius: 0.375rem; /* rounded-md */
                padding: 0.5rem 0.75rem; /* py-2 px-3 */
                background-color: white;
            }
            .dark .dataTables_wrapper .dataTables_filter input {
                background-color: #1e293b; /* slate-800 */
                border-color: #334155; /* slate-700 */
                color: #e2e8f0; /* slate-200 */
            }
            .dataTables_wrapper .dataTables_length select {
                 border: 1px solid #cbd5e1; /* slate-300 */
                 border-radius: 0.375rem; /* rounded-md */
                 padding: 0.5rem 2rem 0.5rem 0.75rem; /* py-2 pl-3 pr-8 */
                 background-color: white;
                 background-position: right 0.5rem center;
                 background-size: 1.5em 1.5em;
                 background-repeat: no-repeat;
            }
            .dark .dataTables_wrapper .dataTables_length select {
                 background-color: #1e293b; /* slate-800 */
                 border-color: #334155; /* slate-700 */
                 color: #e2e8f0; /* slate-200 */
            }

            /* Pagination Buttons */
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                padding: 0.5rem 1rem !important; /* px-4 py-2 */
                margin: 0 0.125rem !important; /* mx-0.5 */
                border: 1px solid #e2e8f0 !important; /* slate-200 */
                border-radius: 0.375rem !important; /* rounded-md */
                background-color: #ffffff !important; /* white */
                color: #334155 !important; /* slate-700 */
                cursor: pointer !important;
                transition: all 0.15s ease-in-out;
                font-size: 0.875rem; /* text-sm */
                font-weight: 500; /* font-medium */
            }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button {
                background-color: #1e293b !important; /* slate-800 */
                border-color: #334155 !important; /* slate-700 */
                color: #cbd5e1 !important; /* slate-300 */
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background-color: #f1f5f9 !important; /* slate-100 */
                border-color: #cbd5e1 !important; /* slate-300 */
            }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                 background-color: #334155 !important; /* slate-700 */
                 border-color: #475569 !important; /* slate-600 */
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background-color: #3b82f6 !important; /* blue-500 */
                color: #ffffff !important; /* white */
                border-color: #3b82f6 !important; /* blue-500 */
            }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background-color: #3b82f6 !important; /* blue-500 */
                color: #ffffff !important; /* white */
                border-color: #3b82f6 !important; /* blue-500 */
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
            .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
                 opacity: 0.5;
                 cursor: not-allowed;
            }

            /* DataTables Info */
            .dataTables_wrapper .dataTables_info {
                padding-top: 0.5rem; /* pt-2 */
                font-size: 0.875rem; /* text-sm */
                color: #64748b; /* slate-500 */
            }
            .dark .dataTables_wrapper .dataTables_info {
                color: #94a3b8; /* slate-400 */
            }

            /* --- SweetAlert2 Customization --- */
            /* Modal size */
            .swal2-popup {
                border-radius: 0.5rem; /* rounded-lg */
                background-color: #ffffff;
            }
            .dark .swal2-popup {
                background-color: #1e293b; /* slate-800 */
                color: #e2e8f0; /* slate-200 */
            }
            .dark .swal2-title {
                color: #f1f5f9; /* slate-100 */
            }
            .dark .swal2-html-container {
                color: #cbd5e1; /* slate-300 */
            }
            .dark .swal2-label {
                color: #cbd5e1 !important; /* slate-300 */
            }

            /* Custom input/textarea styles for SweetAlert2 to match Tailwind */
            .custom-swal-input,
            .custom-swal-textarea {
                display: block !important;
                width: 100% !important;
                padding: 0.75rem !important; /* p-3 */
                border: 1px solid #cbd5e1 !important; /* slate-300 */
                border-radius: 0.375rem !important; /* rounded-md */
                background-color: #ffffff !important;
                color: #1e293b !important; /* slate-800 */
                box-sizing: border-box !important;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
            }
            .dark .custom-swal-input,
            .dark .custom-swal-textarea {
                background-color: #0f172a !important; /* slate-900 */
                border-color: #334155 !important; /* slate-700 */
                color: #e2e8f0 !important; /* slate-200 */
            }
            .custom-swal-input:focus,
            .custom-swal-textarea:focus {
                outline: none !important;
                border-color: #3b82f6 !important; /* blue-500 */
                box-shadow: 0 0 0 1px #3b82f6 !important;
            }
            .swal2-textarea.custom-swal-textarea {
                min-height: 120px; /* Ensure textarea has a decent height */
            }
            .swal2-label { /* Style the labels inside Swal */
                display: block;
                margin-bottom: 0.5rem; /* mb-2 */
                font-weight: 500; /* font-medium */
                color: #374151; /* gray-700 */
                text-align: left;
            }

            /* Adjust Swal button styles if needed */
            .swal2-confirm {
                /* Example: Apply Tailwind button styles */
                /* @apply btn btn-primary; */
            }
            .swal2-cancel {
                 /* Example: Apply Tailwind button styles */
                 /* @apply btn btn-secondary; */
            }

        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.tailwindcss.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/pdfmake.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/vfs_fonts.js"></script>

        <script>
            $(document).ready(function() {

                /* ──────────────────────────────────────────────────────────────
                * TOAST SweetAlert2 sin parámetros incompatibles
                * ────────────────────────────────────────────────────────────── */

                // 1) Generador del mixin limpio
                function buildToast () {
                    const isDark = document.documentElement.classList.contains('dark');

                    return Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
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
                        customClass: { popup: isDark ? 'dark' : '' },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });
                }

                // 2) Instancia inicial
                let Toast = buildToast();

                /* ──────────────────────────────────────────────────────────────
                * Utilidades comunes
                * ────────────────────────────────────────────────────────────── */

                function getSwalWidth () {
                    return window.innerWidth < 768
                        ? '95%'
                        : (window.innerWidth < 1200 ? '700px' : '800px');
                }

                /* Observamos el cambio de tema sin usar Toast.update() */
                const observer = new MutationObserver(() => {
                    const isDark = document.documentElement.classList.contains('dark');
                    document.querySelectorAll('.swal2-popup')
                            .forEach(p => p.classList.toggle('dark', isDark));
                    Toast = buildToast();
                });
                observer.observe(document.documentElement, { attributes: true });


                /* ──────────────────────────────────────────────────────────────
                * RESTO DE TU CÓDIGO
                * ────────────────────────────────────────────────────────────── */

                // --- Disconnect ---
                $('#btnDisconnect').on('click', function () {
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("If you confirm, your LinkedIn account will be disconnected and you\\'ll have to log in again.") }}',
                        icon: 'warning',
                        width: getSwalWidth(),
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: '{{ __("Yes, disconnect") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ rtrim(config('app.url'), '/') }}{{ route('linkedin.disconnect', [], false) }}", {
                                method: "DELETE",
                                headers: {
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                    "Content-Type": "application/json",
                                    "Accept": "application/json"
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    Toast.fire({ icon: 'success', title: data.success || '{{ __("Disconnected successfully") }}' })
                                        .then(() => window.location.reload());
                                } else {
                                    Swal.fire({
                                        title: '{{ __("Error") }}',
                                        text: data.error || '{{ __("Could not disconnect account.") }}',
                                        icon: 'error',
                                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                                    });
                                }
                            })
                            .catch(err => {
                                console.error("Disconnect Error:", err);
                                Swal.fire({
                                    title: '{{ __("Error") }}',
                                    text: '{{ __("Unable to complete the action. Check console for details.") }}',
                                    icon: 'error',
                                    customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                                });
                            });
                        }
                    });
                });

                // --- Process text with AI (Ollama) ---
                $('#btnProcessIA').on('click', function () {
                    const textarea = document.getElementById('content');
                    const userPrompt = textarea.value.trim();

                    if (!userPrompt) {
                        Toast.fire({ icon: 'warning', title: '{{ __("Please write something first.") }}' });
                        return;
                    }

                    const originalButtonText = $(this).html();
                    $(this).prop('disabled', true).html(`<iconify-icon icon="line-md:loading-loop" class="mr-2 text-lg"></iconify-icon> {{ __('Processing...') }}`);

                    const prefix = "Crea una publicación profesional y atractiva para LinkedIn, pero sin escribir nada de cabezal sobre 'te escribo una publicación' o algo parecido, siguiendo estas directrices: ";
                    const suffix = " Mantén un tono profesional, cercano y humano. Usa un lenguaje claro, inspirador y persuasivo que motive a la acción. Si no tienes la información para completar tus textos, no pongas la parte que te falta. Pon solo datos concretos y que tienes; no inventes nada ni dejes partes incompletas.";
                    const prompt = prefix + userPrompt + suffix;
                    const model = '{{ env('OLLAMA_MODEL_MINI', 'default_model_name') }}'; // Provide a default

                    fetch('{{ url("ollama/process") }}', { // Use url() helper for full URL
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ prompt: prompt, model: model })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                     })
                    .then(data => {
                        if (data.text) {
                            textarea.value = data.text;
                            Toast.fire({ icon: 'success', title: '{{ __("Text processed successfully!") }}' });
                        } else {
                            const errorMessage = data.error || '{{ __("An error occurred while processing the text.") }}';
                            Swal.fire({ title: '{{ __("Error") }}', text: errorMessage, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                        }
                    })
                    .catch(error => {
                        console.error('AI Processing Error:', error);
                        Swal.fire({ title: '{{ __("Request Error") }}', text: `{{ __("Could not connect to the AI service.") }} ${error.message}`, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                    })
                    .finally(() => {
                         $(this).prop('disabled', false).html(originalButtonText);
                    });
                });


                // --- Schedule Publication ---
                $('#btnSchedule').on('click', function () {
                    Swal.fire({
                        title: '{{ __("Schedule Publication") }}',
                        width: getSwalWidth(),
                        html: `
                            <div class="mb-4 text-left">
                                <label for="publish_date" class="swal2-label">{{ __("Select Date and Time") }}</label>
                                <input type="datetime-local" id="publish_date" class="custom-swal-input" placeholder="{{ __("Select date and time") }}">
                            </div>
                            <div class="text-left">
                                <label for="task_prompt" class="swal2-label">{{ __("Prompt (optional, uses post text if empty)") }}</label>
                                <textarea id="task_prompt" class="custom-swal-textarea" placeholder="{{ __("Enter prompt or leave empty to use the main post text") }}"></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Schedule") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' },
                        didOpen: () => {
                            const now = new Date();
                            const year = now.getFullYear();
                            const month = (now.getMonth() + 1).toString().padStart(2, '0');
                            const day = now.getDate().toString().padStart(2, '0');
                            const hours = now.getHours().toString().padStart(2, '0');
                            const minutes = now.getMinutes().toString().padStart(2, '0');
                            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                            document.getElementById('publish_date').min = minDateTime;
                        },
                        preConfirm: () => {
                            const publish_date = document.getElementById('publish_date').value;
                            const task_prompt = document.getElementById('task_prompt').value;
                            if (!publish_date) {
                                Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                                return false;
                            }
                            if (new Date(publish_date) <= new Date()) {
                                Swal.showValidationMessage('{{ __("Please select a future date and time") }}');
                                return false;
                            }
                            return { publish_date, task_prompt };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            const promptValue = data.task_prompt.trim() || document.getElementById('content').value.trim();

                            if (!promptValue) {
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("The prompt cannot be empty. Please write something in the main post area or the schedule prompt field.") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                return;
                            }

                            fetch('{{ url("tasker-linkedin/store") }}', { // Use url() helper
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    prompt: promptValue,
                                    publish_date: data.publish_date
                                })
                            })
                            .then(response => response.json())
                            .then(resp => {
                                if (resp.success) {
                                    Toast.fire({
                                        icon: 'success',
                                        title: resp.success || '{{ __("Task scheduled successfully!") }}'
                                    });
                                    // Check if DataTable instance exists before reloading
                                    if ($.fn.DataTable.isDataTable('#tasksTable')) {
                                        $('#tasksTable').DataTable().ajax.reload();
                                    }
                                } else {
                                    Swal.fire({ title: '{{ __("Error") }}', text: resp.error || '{{ __("An error occurred while scheduling") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error("Schedule Error:", error);
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("An error occurred while saving the task") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            });
                        }
                    });
                });

                // --- Initialize DataTable ---
                // Check if the table element exists before initializing
                if ($('#tasksTable').length) {
                    const tasksTable = $('#tasksTable').DataTable({
                        processing: true,
                        serverSide: false, // Keep client-side if data is small/manageable
                        ajax: {
                            url: '{{ url("tasker-linkedin/data") }}',
                            dataSrc: 'data',
                            error: function (xhr, error, thrown) {
                                 console.error("DataTables Error:", error, thrown);
                                 $('#tasksTable tbody').html(
                                     '<tr><td colspan="8" class="text-center text-red-500 py-4">{{ __("Could not load scheduled tasks.") }}</td></tr>'
                                 );
                            }
                        },
                        columns: [
                            { data: 'id', className: 'text-center w-12' },
                            {
                                data: 'prompt',
                                className: 'max-w-xs truncate',
                                render: function(data, type, row) {
                                    const safeData = data ? $('<div>').text(data).html() : 'N/A';
                                    return type === 'display' && safeData.length > 50
                                        ? `<span title="${safeData}">${safeData.substring(0, 50)}...</span>`
                                        : safeData;
                                }
                            },
                            {
                                data: 'response',
                                className: 'max-w-xs truncate',
                                render: function(data, type, row) {
                                    const safeData = data ? $('<div>').text(data).html() : 'N/A';
                                    return type === 'display' && safeData.length > 50
                                         ? `<span title="${safeData}">${safeData.substring(0, 50)}...</span>`
                                         : safeData;
                                }
                            },
                            {
                                data: 'status',
                                className: 'text-center',
                                render: function(data, type, row) {
                                    let badgeClass = 'bg-slate-200 text-slate-700';
                                    if (data === 'completed') badgeClass = 'bg-green-100 text-green-700';
                                    if (data === 'failed') badgeClass = 'bg-red-100 text-red-700';
                                    if (data === 'pending') badgeClass = 'bg-yellow-100 text-yellow-700';
                                    return `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${data || 'N/A'}</span>`;
                                }
                             },
                            {
                                data: 'error',
                                className: 'max-w-xs truncate',
                                render: function(data, type, row) {
                                    const safeData = data ? $('<div>').text(data).html() : 'N/A';
                                    return type === 'display' && safeData.length > 50
                                         ? `<span title="${safeData}">${safeData.substring(0, 50)}...</span>`
                                         : safeData;
                                }
                            },
                            {
                                data: 'publish_date',
                                render: function(data, type, row) {
                                    return data ? new Date(data).toLocaleString() : 'N/A';
                                }
                             },
                            {
                                data: 'created_at',
                                render: function(data, type, row) {
                                    return data ? new Date(data).toLocaleString() : 'N/A';
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-center w-40',
                                render: function(data, type, row) {
                                    const safePrompt = encodeURIComponent(row.prompt || '');
                                    const safeResponse = encodeURIComponent(row.response || '');
                                    const safePublishDate = row.publish_date || '';

                                    return `
                                        <div class="actions-wrapper">
                                            <span class="action-icon editTask"
                                                data-id="${row.id}"
                                                data-prompt="${safePrompt}"
                                                data-publish-date="${safePublishDate}"
                                                data-response="${safeResponse}"
                                                title="{{ __('Edit Task') }}">
                                                <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                                            </span>
                                            <span class="action-icon viewDetails"
                                                data-prompt="${safePrompt}"
                                                data-response="${safeResponse}"
                                                data-status="${row.status || ''}"
                                                data-error="${encodeURIComponent(row.error || '')}"
                                                title="{{ __('View Details') }}">
                                                <iconify-icon icon="heroicons:eye"></iconify-icon>
                                            </span>
                                            <span class="action-icon deleteTask"
                                                data-id="${row.id}" title="{{ __('Delete Task') }}">
                                                <iconify-icon icon="heroicons:trash"></iconify-icon>
                                            </span>
                                            <span class="action-icon repeatTask"
                                                data-prompt="${safePrompt}"
                                                title="{{ __('Repeat Task') }}">
                                                <iconify-icon icon="material-symbols:replay-circle-filled-outline"></iconify-icon>
                                            </span>
                                        </div>
                                    `;
                                }
                            }
                        ],
                        order: [[0, "desc"]],
                        responsive: true,
                        autoWidth: false,
                        language: {
                            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                        },
                        pagingType: 'simple_numbers',
                    });

                    // --- DataTable Auto Refresh ---
                    // Set an interval to reload the DataTable data every 10 seconds (10000 ms)
                    // The first argument 'null' keeps the current paging position
                    // The second argument 'false' prevents resetting the sort/filter
                    setInterval(function () {
                        if ($.fn.DataTable.isDataTable('#tasksTable')) { // Ensure table is still initialized
                            console.log('Reloading DataTable data...'); // Optional: for debugging
                            tasksTable.ajax.reload(null, false);
                        }
                    }, 10000); // Refresh every 10 seconds

                } // End if ($('#tasksTable').length)

                // --- DataTable Action Handlers ---

                // Handler for "View Details"
                $('#tasksTable tbody').on('click', '.viewDetails', function () {
                    const prompt = decodeURIComponent($(this).data('prompt') || '');
                    const response = decodeURIComponent($(this).data('response') || '');
                    const status = $(this).data('status') || 'N/A';
                    const error = decodeURIComponent($(this).data('error') || '');

                    let htmlContent = `
                        <div class="text-left space-y-4">
                            <div>
                                <strong class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Prompt") }}:</strong>
                                <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap break-words">${prompt || '<em>{{ __("Not available") }}</em>'}</p>
                            </div>
                            <div>
                                <strong class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Response") }}:</strong>
                                <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap break-words">${response || '<em>{{ __("Not available / Not generated yet") }}</em>'}</p>
                            </div>
                             <div>
                                <strong class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __("Status") }}:</strong>
                                <p class="text-sm text-slate-600 dark:text-slate-400">${status}</p>
                            </div>
                    `;
                    if (error) {
                         htmlContent += `
                            <div>
                                <strong class="block text-sm font-medium text-red-700 dark:text-red-400 mb-1">{{ __("Error Details") }}:</strong>
                                <p class="text-sm text-red-600 dark:text-red-500 whitespace-pre-wrap break-words">${error}</p>
                            </div>
                         `;
                    }
                    htmlContent += `</div>`;

                    Swal.fire({
                        title: '{{ __("Task Details") }}',
                        html: htmlContent,
                        width: getSwalWidth(),
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    });
                });

                // Handler for "Delete Task"
                $('#tasksTable tbody').on('click', '.deleteTask', function () {
                    const taskId = $(this).data('id');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the task permanently.") }}',
                        icon: 'warning',
                        width: getSwalWidth(),
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Delete") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ url('tasker-linkedin') }}/${taskId}`, { // Use url() helper
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Toast.fire({
                                        icon: 'success',
                                        title: data.success || '{{ __("Task deleted successfully!") }}'
                                    });
                                    // Check if DataTable instance exists before reloading
                                    if ($.fn.DataTable.isDataTable('#tasksTable')) {
                                        $('#tasksTable').DataTable().ajax.reload(null, false); // Reload without resetting page
                                    }
                                } else {
                                    Swal.fire({ title: '{{ __("Error") }}', text: data.error || '{{ __("An error occurred") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error("Delete Error:", error);
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("An error occurred while deleting the task") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            });
                        }
                    });
                });

                // Handler for "Edit Task"
                $('#tasksTable tbody').on('click', '.editTask', function () {
                    const taskId = $(this).data('id');
                    const prompt = decodeURIComponent($(this).data('prompt') || '');
                    const publishDate = $(this).data('publish-date');
                    const responseText = decodeURIComponent($(this).data('response') || '');

                    let formattedDate = '';
                    if (publishDate) {
                        try {
                            const dateObj = new Date(publishDate.replace(' ', 'T'));
                            if (!isNaN(dateObj)) {
                                const year = dateObj.getFullYear();
                                const month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
                                const day = dateObj.getDate().toString().padStart(2, '0');
                                const hours = dateObj.getHours().toString().padStart(2, '0');
                                const minutes = dateObj.getMinutes().toString().padStart(2, '0');
                                formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
                            }
                        } catch (e) {
                            console.error("Error formatting date:", e);
                        }
                    }


                    Swal.fire({
                        title: '{{ __("Edit Task") }}',
                        width: getSwalWidth(),
                        html: `
                            <div class="space-y-4 text-left">
                                <div>
                                    <label for="edit_publish_date" class="swal2-label">{{ __("Select Date and Time") }}</label>
                                    <input type="datetime-local" id="edit_publish_date" class="custom-swal-input" value="${formattedDate}">
                                </div>
                                <div>
                                    <label for="edit_task_prompt" class="swal2-label">{{ __("Prompt") }}</label>
                                    <textarea id="edit_task_prompt" class="custom-swal-textarea">${prompt}</textarea>
                                </div>
                                <div>
                                    <label for="edit_task_response" class="swal2-label">{{ __("Response (read-only/for info)") }}</label>
                                    <textarea id="edit_task_response" class="custom-swal-textarea" readonly>${responseText}</textarea>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' },
                        didOpen: () => {
                            const now = new Date();
                            const year = now.getFullYear();
                            const month = (now.getMonth() + 1).toString().padStart(2, '0');
                            const day = now.getDate().toString().padStart(2, '0');
                            const hours = now.getHours().toString().padStart(2, '0');
                            const minutes = now.getMinutes().toString().padStart(2, '0');
                            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                            document.getElementById('edit_publish_date').min = minDateTime;
                        },
                        preConfirm: () => {
                            const publish_date = document.getElementById('edit_publish_date').value;
                            const task_prompt = document.getElementById('edit_task_prompt').value.trim();

                            if (!publish_date) {
                                Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                                return false;
                            }
                             if (new Date(publish_date) <= new Date()) {
                                Swal.showValidationMessage('{{ __("Please select a future date and time") }}');
                                return false;
                            }
                            if (!task_prompt) {
                                Swal.showValidationMessage('{{ __("Please enter a prompt") }}');
                                return false;
                            }
                            return { publish_date, task_prompt };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            // *** IMPORTANT: Adjust the URL to your actual update route ***
                            // Example: fetch(`{{ url('tasker-linkedin') }}/${taskId}`, {
                            fetch(`{{ url('tasker-linkedin') }}/${taskId}`, { // ADJUST ROUTE IF NEEDED
                                method: 'PUT', // Or PATCH
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    prompt: data.task_prompt,
                                    publish_date: data.publish_date,
                                })
                            })
                            .then(response => response.json())
                            .then(resp => {
                                if (resp.success) {
                                     Toast.fire({
                                        icon: 'success',
                                        title: resp.success || '{{ __("Task updated successfully!") }}'
                                    });
                                    // Check if DataTable instance exists before reloading
                                    if ($.fn.DataTable.isDataTable('#tasksTable')) {
                                        $('#tasksTable').DataTable().ajax.reload(null, false);
                                    }
                                } else {
                                    Swal.fire({ title: '{{ __("Error") }}', text: resp.error || '{{ __("An error occurred while updating") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error("Update Error:", error);
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("An error occurred while updating the task") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            });
                        }
                    });
                });

                // Handler for "Repeat Task" (Reprogram)
                $('#tasksTable tbody').on('click', '.repeatTask', function () {
                    const originalPrompt = decodeURIComponent($(this).data('prompt') || '');

                    Swal.fire({
                        title: '{{ __("Reprogram Task") }}',
                        width: getSwalWidth(),
                        html: `
                            <div class="space-y-4 text-left">
                                <div>
                                    <label for="repeat_publish_date" class="swal2-label">{{ __("Select New Date and Time") }}</label>
                                    <input type="datetime-local" id="repeat_publish_date" class="custom-swal-input">
                                </div>
                                <div>
                                    <label for="repeat_task_prompt" class="swal2-label">{{ __("Prompt (edit if needed)") }}</label>
                                    <textarea id="repeat_task_prompt" class="custom-swal-textarea">${originalPrompt}</textarea>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save New Schedule") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' },
                         didOpen: () => {
                            const now = new Date();
                            const year = now.getFullYear();
                            const month = (now.getMonth() + 1).toString().padStart(2, '0');
                            const day = now.getDate().toString().padStart(2, '0');
                            const hours = now.getHours().toString().padStart(2, '0');
                            const minutes = now.getMinutes().toString().padStart(2, '0');
                            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                            document.getElementById('repeat_publish_date').min = minDateTime;
                        },
                        preConfirm: () => {
                            const publish_date = document.getElementById('repeat_publish_date').value;
                            const task_prompt = document.getElementById('repeat_task_prompt').value.trim();
                            if (!publish_date) {
                                Swal.showValidationMessage('{{ __("Please select a date and time") }}');
                                return false;
                            }
                            if (new Date(publish_date) <= new Date()) {
                                Swal.showValidationMessage('{{ __("Please select a future date and time") }}');
                                return false;
                            }
                            if (!task_prompt) {
                                Swal.showValidationMessage('{{ __("Please enter a prompt") }}');
                                return false;
                            }
                            return { publish_date, task_prompt };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            fetch('{{ url("tasker-linkedin/store") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    prompt: data.task_prompt,
                                    publish_date: data.publish_date
                                })
                            })
                            .then(response => response.json())
                            .then(resp => {
                                if (resp.success) {
                                    Toast.fire({
                                        icon: 'success',
                                        title: resp.success || '{{ __("Task rescheduled successfully!") }}'
                                    });
                                    // Check if DataTable instance exists before reloading
                                    if ($.fn.DataTable.isDataTable('#tasksTable')) {
                                         $('#tasksTable').DataTable().ajax.reload(null, false);
                                    }
                                } else {
                                    Swal.fire({ title: '{{ __("Error") }}', text: resp.error || '{{ __("An error occurred") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error("Repeat Task Error:", error);
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("An error occurred while saving the task") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            });
                        }
                    });
                });

            }); // End $(document).ready
        </script>
    @endpush
</x-app-layout>
