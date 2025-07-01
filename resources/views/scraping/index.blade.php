<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? [['name' => __('Scraping Tasks Manager')]]" :page-title="__('Scraping Tasks Manager')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" class="mb-5" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" class="mb-5" />
    @endif
    @if ($errors->any())
        <x-alert :message="$errors->first()" :type="'danger'" class="mb-5" />
    @endif
    {{-- Alert end --}}

    {{-- Create Task Card --}}
    <div class="card shadow rounded-lg overflow-hidden mb-8">
        <div class="bg-white dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h4 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                {{ __('Create New Scraping Task') }}
            </h4>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800">
            <form action="{{ route('scraping.tasks.store') }}" method="POST" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {{-- Keyword --}}
                    <div class="input-area">
                        <label for="keyword" class="form-label">{{ __('Keyword / Activity') }}<span class="text-red-500">*</span></label>
                        <input id="keyword" name="keyword" type="text" class="form-control" placeholder="{{ __('Enter keyword or activity') }}" value="{{ old('keyword') }}" required>
                        @error('keyword')
                            <span class="text-sm text-red-500 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Region --}}
                    <div class="input-area">
                        <label for="region" class="form-label">{{ __('Region / Province') }}</label>
                        <input id="region" name="region" type="text" class="form-control" placeholder="{{ __('Enter region (for directories)') }}" value="{{ old('region') }}">
                        @error('region')
                             <span class="text-sm text-red-500 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                     {{-- Source --}}
                    <div class="input-area">
                        <label for="source" class="form-label">{{ __('Source') }}<span class="text-red-500">*</span></label>
                        <select id="source" name="source" class="form-control" required>
                            <option value="" disabled selected>{{ __('Select source') }}</option>
                            <option value="google_ddg" @selected(old('source') == 'google_ddg')>{{ __('Google / DuckDuckGo') }}</option>
                            <option value="empresite" @selected(old('source') == 'empresite')>{{ __('Empresite') }}</option>
                            <option value="paginas_amarillas" @selected(old('source') == 'paginas_amarillas')>{{ __('Páginas Amarillas') }}</option>
                        </select>
                         @error('source')
                             <span class="text-sm text-red-500 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary inline-flex items-center">
                    <iconify-icon icon="mdi:plus-circle-outline" class="mr-1 text-lg"></iconify-icon>
                    {{ __('Create Task') }}
                </button>
            </form>
        </div>
    </div>


    {{-- Tasks List Card --}}
    <div class="card shadow rounded-lg overflow-hidden">
        {{-- Card Header --}}
        <div class="bg-white dark:bg-slate-800 px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center">
                <h4 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                    {{ __('My Scraping Tasks') }}
                </h4>
                 {{-- Refresh Button --}}
                <button id="refreshTasks" class="btn inline-flex justify-center btn-dark dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 rounded-full items-center p-2 h-10 w-10" title="{{ __('Refresh List') }}">
                    <iconify-icon icon="mdi:refresh" class="text-xl"></iconify-icon>
                </button>
            </div>
        </div>

        {{-- Card Body --}}
        <div class="p-6 bg-white dark:bg-slate-800">
            <div class="overflow-x-auto -mx-6 px-6">
                <table id="tasksTable" class="w-full min-w-[800px] dataTable" data-url="{{ route('scraping.tasks.data') }}">
                    <thead class="bg-slate-100 dark:bg-slate-700 text-xs uppercase text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium text-left">{{ __('ID') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Source') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Keyword') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Region') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('API Task ID') }}</th>
                            <th class="px-4 py-3 font-medium text-left">{{ __('Created At') }}</th>
                            <th class="px-4 py-3 font-medium text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-300">
                        {{-- Data loaded via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Los estilos no necesitan cambios --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.tailwindcss.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            #tasksTable { border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 0.5rem; overflow: hidden; }
            .dark #tasksTable { border-color: #334155; }
            #tasksTable thead th { border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1rem; }
            .dark #tasksTable thead th { border-bottom-color: #334155; }
            #tasksTable tbody td { padding: 0.75rem 1rem; vertical-align: middle; }
            #tasksTable tbody tr { border-bottom: 1px solid #e2e8f0; }
            .dark #tasksTable tbody tr { border-bottom-color: #334155; }
            #tasksTable tbody tr:last-child { border-bottom: none; }
            #tasksTable td:last-child { text-align: center; }
            #tasksTable .actions-wrapper { display: inline-flex; gap: 0.75rem; align-items: center; }
            #tasksTable .action-icon { display: inline-block; color: #64748b; transition: color 0.15s ease-in-out; font-size: 1.25rem; cursor: pointer; }
            .dark #tasksTable .action-icon { color: #94a3b8; }
            #tasksTable .action-icon:hover { color: #1e293b; }
            .dark #tasksTable .action-icon:hover { color: #f1f5f9; }
            #tasksTable .action-icon.deleteTask:hover { color: #ef4444; }
            #tasksTable .action-icon.editTask:hover { color: #3b82f6; }
            #tasksTable .action-icon.viewContacts:hover { color: #10b981; }
            #tasksTable .action-icon.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { margin-bottom: 1.5rem; }
            .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 0.375rem; padding: 0.5rem 0.75rem; background-color: white; }
            .dark .dataTables_wrapper .dataTables_filter input { background-color: #1e293b; border-color: #334155; color: #e2e8f0; }
            .dataTables_wrapper .dataTables_length select { border: 1px solid #cbd5e1; border-radius: 0.375rem; padding: 0.5rem 2rem 0.5rem 0.75rem; background-color: white; background-position: right 0.5rem center; background-size: 1.5em 1.5em; background-repeat: no-repeat; }
            .dark .dataTables_wrapper .dataTables_length select { background-color: #1e293b; border-color: #334155; color: #e2e8f0; }
            .dataTables_wrapper .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center; justify-content: center; padding: 0.5rem 1rem !important; margin: 0 0.125rem !important; border: 1px solid #e2e8f0 !important; border-radius: 0.375rem !important; background-color: #ffffff !important; color: #334155 !important; cursor: pointer !important; transition: all 0.15s ease-in-out; font-size: 0.875rem; font-weight: 500; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button { background-color: #1e293b !important; border-color: #334155 !important; color: #cbd5e1 !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background-color: #f1f5f9 !important; border-color: #cbd5e1 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background-color: #334155 !important; border-color: #475569 !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #3b82f6 !important; color: #ffffff !important; border-color: #3b82f6 !important; }
            .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { background-color: #3b82f6 !important; color: #ffffff !important; border-color: #3b82f6 !important; }
            .dataTables_wrapper .dataTables_paginate .paginate_button.disabled, .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover { opacity: 0.5; cursor: not-allowed; }
            .dataTables_wrapper .dataTables_info { padding-top: 0.5rem; font-size: 0.875rem; color: #64748b; }
            .dark .dataTables_wrapper .dataTables_info { color: #94a3b8; }
            .swal2-popup { border-radius: 0.5rem; background-color: #ffffff; }
            .dark .swal2-popup { background-color: #1e293b; color: #e2e8f0; }
            .dark .swal2-title { color: #f1f5f9; }
            .dark .swal2-html-container { color: #cbd5e1; }
            .dark .swal2-label { color: #cbd5e1 !important; }
            .custom-swal-input, .custom-swal-select { display: block !important; width: 100% !important; padding: 0.75rem !important; border: 1px solid #cbd5e1 !important; border-radius: 0.375rem !important; background-color: #ffffff !important; color: #1e293b !important; box-sizing: border-box !important; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important; }
            .dark .custom-swal-input, .dark .custom-swal-select { background-color: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important; }
            .custom-swal-input:focus, .custom-swal-select:focus { outline: none !important; border-color: #3b82f6 !important; box-shadow: 0 0 0 1px #3b82f6 !important; }
            .swal2-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; text-align: left; }
        </style>
    @endpush

    @push('scripts')
        {{-- CAMBIO: Simplificamos los scripts para que coincidan con el fichero que SÍ funciona --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        {{-- Los scripts de Tailwind y Responsive para DataTables se han eliminado temporalmente --}}
        
        <script>
            // Volvemos a añadir los console.log para verificar
            console.log('SCRIPT DE SCRAPING CARGADO (VERSIÓN SIMPLIFICADA) - ' + new Date().toISOString());
            
            $(document).ready(function() {
                console.log('DOCUMENT READY EJECUTADO (VERSIÓN SIMPLIFICADA) - ' + new Date().toISOString());

                // El resto del script no necesita cambios, ya que la lógica es la misma.
                // --- Toast ---
                function buildToast () { const isDark = document.documentElement.classList.contains('dark'); return Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, backdrop: false, customClass: { popup: isDark ? 'dark' : '' }, didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); } }); }
                let Toast = buildToast();
                const observer = new MutationObserver(() => { Toast = buildToast(); });
                observer.observe(document.documentElement, { attributes: true });
                function getSwalWidth () { return window.innerWidth < 768 ? '95%' : '600px'; }
                
                // --- Initialize DataTable ---
                if ($('#tasksTable').length) {
                    const urlData = $('#tasksTable').data('url');
                    console.log('DataTable: URL de datos:', urlData);
                    
                    const tasksTable = $('#tasksTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: urlData,
                            type: 'GET',
                            error: function (xhr, error, thrown) {
                                console.error("DataTables AJAX Error:", { error, thrown, status: xhr.status, responseText: xhr.responseText });
                                alert('ERROR FATAL AL CARGAR DATOS: Revisa la consola (F12). Status: ' + xhr.status);
                            },
                            dataSrc: function(json) {
                                console.log("DataTable: Datos recibidos:", json);
                                return json.data;
                            }
                        },
                        columns: [
                            { data: 'id', name: 'id' },
                            { data: 'source', name: 'source' },
                            { data: 'keyword', name: 'keyword' },
                            { data: 'region', name: 'region', defaultContent: '-' },
                            { data: 'status', name: 'status' },
                            { data: 'api_task_id', name: 'api_task_id', defaultContent: '-' },
                            { data: 'created_at', name: 'created_at' },
                            { data: 'actions', name: 'actions', orderable: false, searchable: false }
                        ],
                        order: [[0, "desc"]],
                        language: {
                            url: "{{ app()->getLocale() == 'es' ? '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' : '//cdn.datatables.net/plug-ins/1.13.4/i18n/en-GB.json' }}"
                        },
                    });

                    // --- Event Handlers ---
                    $('#refreshTasks').on('click', function() {
                        Toast.fire({ icon: 'info', title: '{{ __("Task list refreshed") }}' });
                        tasksTable.ajax.reload(null, false);
                    });
                    setInterval(function () {
                        if ($.fn.DataTable.isDataTable('#tasksTable')) {
                            tasksTable.ajax.reload(null, false);
                        }
                    }, 60000);
                } else {
                    console.error("DataTable: ERROR CRÍTICO - No se encontró el elemento #tasksTable.");
                }

                // --- Handlers para acciones (sin cambios) ---
                $('#tasksTable tbody').on('click', '.deleteTask:not(.disabled)', function () {
                    const taskId = $(this).data('id');
                    const taskRow = $(this).closest('tr');
                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("This will delete the pending task.") }}',
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
                            const deleteUrl = `{{ url('scraping-tasks') }}/${taskId}`;
                            fetch(deleteUrl, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json().then(data => ({ status: response.status, body: data })))
                            .then(({ status, body }) => {
                                if (status === 200 && body.success) {
                                    Toast.fire({ icon: 'success', title: body.success || '{{ __("Task deleted successfully!") }}' });
                                    if ($.fn.DataTable.isDataTable('#tasksTable')) {
                                       $('#tasksTable').DataTable().row(taskRow).remove().draw(false);
                                    }
                                } else {
                                    Swal.fire({ title: '{{ __("Error") }}', text: body.error || '{{ __("Could not delete task.") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error("Delete Error:", error);
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("An error occurred while deleting the task") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            });
                        }
                    });
                });
                $('#tasksTable tbody').on('click', '.editTask:not(.disabled)', function () {
                    const taskId = $(this).data('id');
                    const keyword = $(this).data('keyword');
                    const region = $(this).data('region') || '';
                    const source = $(this).data('source');
                    Swal.fire({
                        title: '{{ __("Edit Pending Task") }} (ID: ' + taskId + ')',
                        width: getSwalWidth(),
                        html: `
                            <div class="space-y-4 text-left">
                                <div>
                                    <label for="edit_keyword" class="swal2-label">{{ __('Keyword / Activity') }}<span class="text-red-500">*</span></label>
                                    <input type="text" id="edit_keyword" class="custom-swal-input" value="${keyword}">
                                </div>
                                <div>
                                    <label for="edit_region" class="swal2-label">{{ __('Region / Province') }}</label>
                                    <input type="text" id="edit_region" class="custom-swal-input" value="${region}">
                                </div>
                                <div>
                                    <label for="edit_source" class="swal2-label">{{ __('Source') }}<span class="text-red-500">*</span></label>
                                    <select id="edit_source" class="custom-swal-select">
                                        <option value="google_ddg" ${source === 'google_ddg' ? 'selected' : ''}>{{ __('Google / DuckDuckGo') }}</option>
                                        <option value="empresite" ${source === 'empresite' ? 'selected' : ''}>{{ __('Empresite') }}</option>
                                        <option value="paginas_amarillas" ${source === 'paginas_amarillas' ? 'selected' : ''}>{{ __('Páginas Amarillas') }}</option>
                                    </select>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' },
                        preConfirm: () => {
                            const newKeyword = document.getElementById('edit_keyword').value.trim();
                            const newRegion = document.getElementById('edit_region').value.trim();
                            const newSource = document.getElementById('edit_source').value;
                            if (!newKeyword) {
                                Swal.showValidationMessage('{{ __("Keyword/Activity cannot be empty") }}');
                                return false;
                            }
                            if (!newSource) {
                                Swal.showValidationMessage('{{ __("Please select a source") }}');
                                return false;
                            }
                            return { keyword: newKeyword, region: newRegion || null, source: newSource };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const data = result.value;
                            const updateUrl = `{{ url('scraping-tasks') }}/${taskId}`;
                            fetch(updateUrl, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(data)
                            })
                            .then(response => response.json().then(data => ({ status: response.status, body: data })))
                            .then(({ status, body }) => {
                                if (status === 200 && body.success) {
                                    Toast.fire({ icon: 'success', title: body.success || '{{ __("Task updated successfully!") }}' });
                                    if ($.fn.DataTable.isDataTable('#tasksTable')) {
                                        $('#tasksTable').DataTable().ajax.reload(null, false);
                                    }
                                } else {
                                    let errorMsg = body.error || '{{ __("Could not update task.") }}';
                                    if (body.errors) {
                                        errorMsg += '<br>' + Object.values(body.errors).flat().join('<br>');
                                    }
                                    Swal.fire({ title: '{{ __("Error") }}', html: errorMsg, icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                                }
                            })
                            .catch(error => {
                                console.error("Update Error:", error);
                                Swal.fire({ title: '{{ __("Error") }}', text: '{{ __("An error occurred while updating the task") }}', icon: 'error', customClass: { popup: document.documentElement.classList.contains('dark') ? 'dark' : '' } });
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
