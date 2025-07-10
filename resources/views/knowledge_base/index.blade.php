<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('IA Memory (Knowledge Base)')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    @if ($errors->any() && old('_token'))
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
    
    {{-- Dashboard Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                    <iconify-icon class="text-2xl text-blue-500" icon="mdi:file-document-outline"></iconify-icon>
                </div>
                <div>
                    <h3 class="text-sm text-slate-500 dark:text-slate-400">{{ __('Total Documents') }}</h3>
                    <p class="text-xl font-bold text-slate-700 dark:text-slate-200" id="total-documents">{{ $stats['total_documents'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                    <iconify-icon class="text-2xl text-green-500" icon="mdi:check-circle-outline"></iconify-icon>
                </div>
                <div>
                    <h3 class="text-sm text-slate-500 dark:text-slate-400">{{ __('Processed') }}</h3>
                    <p class="text-xl font-bold text-slate-700 dark:text-slate-200" id="processed-documents">{{ $stats['processed_documents'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 mr-4">
                    <iconify-icon class="text-2xl text-yellow-500" icon="mdi:clock-outline"></iconify-icon>
                </div>
                <div>
                    <h3 class="text-sm text-slate-500 dark:text-slate-400">{{ __('Processing') }}</h3>
                    <p class="text-xl font-bold text-slate-700 dark:text-slate-200" id="processing-documents">{{ $stats['processing_documents'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                    <iconify-icon class="text-2xl text-purple-500" icon="mdi:brain"></iconify-icon>
                </div>
                <div>
                    <h3 class="text-sm text-slate-500 dark:text-slate-400">{{ __('Knowledge Chunks') }}</h3>
                    <p class="text-xl font-bold text-slate-700 dark:text-slate-200" id="total-chunks">{{ $stats['total_chunks'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Formulario colapsable para subir PDF --}}
        @canany(['knowledgebase.upload.user', 'knowledgebase.upload.company'])
        <div class="p-6 border border-slate-200 dark:border-slate-700 rounded-lg bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-slate-800 dark:to-slate-700">
            <div class="flex justify-between items-center cursor-pointer" id="formToggleHeader">
                <div class="flex items-center">
                    <iconify-icon icon="heroicons:cloud-arrow-up-20-solid" class="text-3xl text-indigo-600 dark:text-indigo-400 mr-4"></iconify-icon>
                    <div>
                        <h4 class="text-lg font-bold text-slate-800 dark:text-slate-200">{{ __('IA Memory') }}</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Gestiona tus documentos PDF para la base de conocimiento de la IA.') }}</p>
                    </div>
                </div>
                <button type="button" class="p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <iconify-icon id="formToggleIcon" icon="heroicons:plus-circle-20-solid" class="text-2xl transition-transform duration-300 ease-in-out"></iconify-icon>
                </button>
            </div>
            <div id="pdfFormContainer" class="overflow-hidden">
                <div class="pt-6 pb-2">
                    <div class="p-4 bg-blue-50 dark:bg-slate-700 rounded-lg mb-6 border-l-4 border-blue-500">
                        <div class="flex items-start">
                            <iconify-icon icon="heroicons:information-circle-20-solid" class="text-2xl text-blue-600 dark:text-blue-400 mr-3 mt-1"></iconify-icon>
                            <div>
                                <h5 class="font-semibold text-blue-800 dark:text-blue-200">{{ __('Instrucciones de Uso') }}</h5>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ __('Sube tus documentos en formato PDF. El sistema los procesará y los añadirá a la base de conocimiento. Podrás ver el estado de procesamiento en la tabla de abajo. Los documentos de empresa serán visibles para todos los usuarios de tu organización.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <form action="{{ route('knowledge_base.upload.post') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="uploadForm">
                    @csrf
                    <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm">
                        <label for="pdf" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Archivo PDF') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 dark:border-slate-600 border-dashed rounded-lg hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors duration-200">
                            <div class="space-y-2 text-center">
                                <iconify-icon class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" icon="heroicons:document-arrow-up"></iconify-icon>
                                <div class="flex text-sm text-slate-600 dark:text-slate-400">
                                    <label for="pdf" class="relative cursor-pointer bg-white dark:bg-slate-800 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        <span>{{ __('Selecciona un archivo') }}</span>
                                        <input id="pdf" name="pdf" type="file" class="sr-only" accept=".pdf">
                                    </label>
                                    <p class="pl-1">{{ __('o arrástralo aquí') }}</p>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('PDF hasta 10MB') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400" id="file-name-display">{{ __('Ningún archivo seleccionado') }}</p>
                            </div>
                        </div>
                    </div>
                    @php
                        $canUser = auth()->user()->can('knowledgebase.upload.user');
                        $canCompany = auth()->user()->can('knowledgebase.upload.company');
                    @endphp
                    @if($canUser && $canCompany)
                        <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Visibilidad del documento:') }}</label>
                            <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <input type="radio" id="upload_type_user" name="upload_type" value="user" class="peer sr-only" checked>
                                    <label for="upload_type_user" class="flex flex-col text-center p-4 border-2 border-slate-300 dark:border-slate-600 rounded-lg cursor-pointer transition-all duration-200 hover:border-indigo-500 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 dark:peer-checked:bg-slate-700 dark:peer-checked:border-indigo-500">
                                        <div class="flex items-center justify-center mb-2">
                                            <iconify-icon icon="heroicons:user-solid" class="text-2xl text-slate-500 dark:text-slate-400 peer-checked:text-indigo-600 dark:peer-checked:text-indigo-400"></iconify-icon>
                                            <span class="ml-2 font-semibold text-slate-800 dark:text-slate-200">{{ __('Personal') }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Solo visible para ti') }}</p>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="upload_type_company" name="upload_type" value="company" class="peer sr-only">
                                    <label for="upload_type_company" class="flex flex-col text-center p-4 border-2 border-slate-300 dark:border-slate-600 rounded-lg cursor-pointer transition-all duration-200 hover:border-indigo-500 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 dark:peer-checked:bg-slate-700 dark:peer-checked:border-indigo-500">
                                        <div class="flex items-center justify-center mb-2">
                                            <iconify-icon icon="heroicons:building-office-2-solid" class="text-2xl text-slate-500 dark:text-slate-400 peer-checked:text-indigo-600 dark:peer-checked:text-indigo-400"></iconify-icon>
                                            <span class="ml-2 font-semibold text-slate-800 dark:text-slate-200">{{ __('Empresa') }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Visible para toda la empresa') }}</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @elseif($canUser)
                        <input type="hidden" name="upload_type" value="user">
                    @elseif($canCompany)
                        <input type="hidden" name="upload_type" value="company">
                    @endif

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="btn btn-primary inline-flex items-center justify-center">
                            <iconify-icon icon="heroicons:arrow-up-tray-solid" class="mr-2"></iconify-icon>
                            {{ __('Subir Documento') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endcanany

        <!-- Card for DataTables -->
        <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
            <div class="card-body p-6">
                <div class="card-title flex justify-between items-center mb-5">
                    <h5 class="text-xl font-bold text-slate-700 dark:text-slate-200">{{ __('Documentos Disponibles') }}</h5>
                    <div class="relative">
                        <input type="text" id="global-search" class="form-input w-full md:w-64 pl-9" placeholder="{{ __('Buscar documentos...') }}">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <iconify-icon icon="heroicons:magnifying-glass" class="text-slate-400"></iconify-icon>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs flex flex-col md:flex-row flex-wrap list-none border-b-0 pl-0 mb-4" id="tabs-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tabs-user" class="nav-link block font-medium text-sm leading-tight uppercase border-x-0 border-t-0 border-b-2 border-transparent px-6 py-3 my-2 hover:border-transparent hover:bg-gray-100 dark:hover:bg-slate-700 focus:border-transparent active" id="tabs-user-tab" data-bs-toggle="pill" data-bs-target="#tabs-user" role="tab" aria-controls="tabs-user" aria-selected="true">
                            <iconify-icon class="mr-2" icon="heroicons:user-circle"></iconify-icon>
                            {{ __('Mis Documentos') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tabs-company" class="nav-link block font-medium text-sm leading-tight uppercase border-x-0 border-t-0 border-b-2 border-transparent px-6 py-3 my-2 hover:border-transparent hover:bg-gray-100 dark:hover:bg-slate-700 focus:border-transparent" id="tabs-company-tab" data-bs-toggle="pill" data-bs-target="#tabs-company" role="tab" aria-controls="tabs-company" aria-selected="false">
                            <iconify-icon class="mr-2" icon="heroicons:building-office-2"></iconify-icon>
                            {{ __('Documentos de la Empresa') }}
                        </a>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content" id="tabs-tabContent">
                    <div class="tab-pane fade show active" id="tabs-user" role="tabpanel" aria-labelledby="tabs-user-tab">
                        <div class="overflow-x-auto">
                            <table id="userKnowledgeTable" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700" style="width:100%">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Estado') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Tipo') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider no-sort">{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tabs-company" role="tabpanel" aria-labelledby="tabs-company-tab">
                        <div class="overflow-x-auto">
                            <table id="companyKnowledgeTable" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700" style="width:100%">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Nombre') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Estado') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Tipo') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider no-sort">{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .loading-spinner .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 10px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        label.is-selected {
            border-color: #4f46e5 !important; /* indigo-600 */
            background-color: #eef2ff !important; /* indigo-50 */
        }

        .dark label.is-selected {
            border-color: #6366f1 !important; /* indigo-500 */
            background-color: #3730a3 !important; /* indigo-800 */
        }

        label.is-selected .iconify-icon {
            color: #4f46e5 !important; /* indigo-600 */
        }

        .dark label.is-selected .iconify-icon {
            color: #a5b4fc !important; /* indigo-300 */
        }

    </style>
    @endpush

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/es.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Función para eliminar un PDF, accesible globalmente
        function deletePdf(button) {
            const url = button.getAttribute('data-url');
            
            Swal.fire({
                title: '{{ __('¿Estás seguro?') }}',
                text: '{{ __('Este documento y todos sus datos relacionados serán eliminados permanentemente.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('Sí, eliminar') }}',
                cancelButtonText: '{{ __('Cancelar') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar indicador de carga
                    const loadingOverlay = document.createElement('div');
                    loadingOverlay.className = 'loading-overlay';
                    loadingOverlay.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><p>{{ __('Processing...') }}</p></div>';
                    document.body.appendChild(loadingOverlay);
                    
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.body.removeChild(loadingOverlay);
                        if (data.success) {
                            if ($.fn.dataTable.isDataTable('#userKnowledgeTable')) {
                                $('#userKnowledgeTable').DataTable().ajax.reload();
                            }
                            if ($.fn.dataTable.isDataTable('#companyKnowledgeTable')) {
                                $('#companyKnowledgeTable').DataTable().ajax.reload();
                            }
                            updateDashboardStats();
                            Swal.fire({
                                title: '{{ __('Eliminado') }}',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: '{{ __('Aceptar') }}'
                            });
                        } else {
                            Swal.fire({
                                title: '{{ __('Error') }}',
                                text: data.message || '{{ __('Ha ocurrido un error al eliminar el documento.') }}',
                                icon: 'error',
                                confirmButtonText: '{{ __('Aceptar') }}'
                            });
                        }
                    })
                    .catch(error => {
                        document.body.removeChild(loadingOverlay);
                        console.error('Error:', error);
                        Swal.fire({
                            title: '{{ __('Error') }}',
                            text: '{{ __('Ha ocurrido un error al procesar la solicitud.') }}',
                            icon: 'error',
                            confirmButtonText: '{{ __('Aceptar') }}'
                        });
                    });
                }
            });
        }

        // Función para actualizar las estadísticas del dashboard
        function updateDashboardStats() {
            fetch('{{ route("knowledge_base.stats") }}', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-documents').textContent = data.stats.total_documents;
                    document.getElementById('processed-documents').textContent = data.stats.processed_documents;
                    document.getElementById('processing-documents').textContent = data.stats.processing_documents;
                    document.getElementById('total-chunks').textContent = data.stats.total_chunks;
                }
            })
            .catch(error => console.error('Error actualizando estadísticas:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            function waitForJQuery(callback) {
                if (window.jQuery) {
                    callback(window.jQuery);
                } else {
                    setTimeout(() => waitForJQuery(callback), 100);
                }
            }

            waitForJQuery(function($) {
                // Configuración común para ambas tablas
                const commonConfig = {
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' },
                    order: [[2, 'desc']],
                    columnDefs: [{ targets: 'no-sort', orderable: false }]
                };

                function formatDate(dateString) {
                    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                    return new Date(dateString).toLocaleDateString('es-ES', options);
                }

                function renderStatus(data) {
                    if (data === 'processed') {
                        return `<span class="badge bg-success text-white px-3 py-2 rounded-pill"><iconify-icon icon="heroicons:check-circle" class="inline-block mr-1"></iconify-icon> {{ __('Procesado') }}</span>`;
                    } else {
                        return `<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><iconify-icon icon="heroicons:clock" class="inline-block mr-1"></iconify-icon> {{ __('Procesando') }}</span>`;
                    }
                }

                function renderType(data) {
                    if (data === 'user') {
                        return `<span class="badge bg-primary text-white px-3 py-2 rounded-pill"><iconify-icon icon="heroicons:user" class="inline-block mr-1"></iconify-icon> {{ __('Personal') }}</span>`;
                    } else {
                        return `<span class="badge bg-info text-white px-3 py-2 rounded-pill"><iconify-icon icon="heroicons:building-office" class="inline-block mr-1"></iconify-icon> {{ __('Empresa') }}</span>`;
                    }
                }

                const userTable = $('#userKnowledgeTable').DataTable({
                    ...commonConfig,
                    ajax: '{{ route("knowledge_base.user_data") }}',
                    columns: [
                        { data: 'file_name', name: 'file_name', title: '{{ __('Nombre') }}' },
                        { data: 'status', name: 'status', title: '{{ __('Estado') }}', render: renderStatus },
                        { data: 'type', name: 'type', title: '{{ __('Tipo') }}', render: renderType },
                        { data: 'created_at', name: 'created_at', title: '{{ __('Fecha') }}', render: data => formatDate(data) },
                        { data: 'action', name: 'action', title: '{{ __('Acciones') }}', orderable: false, searchable: false, className: 'no-sort text-center' }
                    ]
                });

                const companyTable = $('#companyKnowledgeTable').DataTable({
                    ...commonConfig,
                    ajax: '{{ route("knowledge_base.company_data") }}',
                    columns: [
                        { data: 'file_name', name: 'file_name', title: '{{ __('Nombre') }}' },
                        { data: 'status', name: 'status', title: '{{ __('Estado') }}', render: renderStatus },
                        { data: 'type', name: 'type', title: '{{ __('Tipo') }}', render: renderType },
                        { data: 'created_at', name: 'created_at', title: '{{ __('Fecha') }}', render: data => formatDate(data) },
                        { data: 'action', name: 'action', title: '{{ __('Acciones') }}', orderable: false, searchable: false, className: 'no-sort text-center' }
                    ]
                });

                $('#global-search').on('keyup', function() {
                    const searchTerm = $(this).val();
                    userTable.search(searchTerm).draw();
                    companyTable.search(searchTerm).draw();
                });

                $('#uploadForm').on('submit', function(e) {
                    e.preventDefault();
                    
                    var formData = new FormData(this);
                    var submitButton = $(this).find('button[type="submit"]');
                    var originalButtonText = submitButton.html();

                    submitButton.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Subiendo...') }}`);

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if ($.fn.dataTable.isDataTable('#userKnowledgeTable')) {
                                $('#userKnowledgeTable').DataTable().ajax.reload();
                            }
                            if ($.fn.dataTable.isDataTable('#companyKnowledgeTable')) {
                                $('#companyKnowledgeTable').DataTable().ajax.reload();
                            }
                            updateDashboardStats();
                            Swal.fire({
                                title: '{{ __('Éxito') }}',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: '{{ __('Accept') }}'
                            });
                            $('#uploadForm')[0].reset();
                        },
                        error: function(xhr) {
                            var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __('Ha ocurrido un error inesperado.') }}';
                            Swal.fire({
                                title: '{{ __('Error') }}',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonText: '{{ __('Accept') }}'
                            });
                        },
                        complete: function() {
                            submitButton.prop('disabled', false).html(originalButtonText);
                        }
                    });
                });
                
                // Actualizar estadísticas periódicamente
                setInterval(updateDashboardStats, 30000);

                // Lógica para resaltar la selección de visibilidad del documento
                const radioButtons = document.querySelectorAll('input[name="upload_type"]');
                const labels = {
                    user: document.querySelector('label[for="upload_type_user"]'),
                    company: document.querySelector('label[for="upload_type_company"]')
                };

                function updateSelection() {
                    const selectedValue = document.querySelector('input[name="upload_type"]:checked').value;
                    for (const type in labels) {
                        if (labels[type]) {
                            if (type === selectedValue) {
                                labels[type].classList.add('is-selected');
                            } else {
                                labels[type].classList.remove('is-selected');
                            }
                        }
                    }
                }

                radioButtons.forEach(radio => {
                    radio.addEventListener('change', updateSelection);
                });

                // Estado inicial
                if(document.querySelector('input[name="upload_type"]:checked')){
                    updateSelection();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
