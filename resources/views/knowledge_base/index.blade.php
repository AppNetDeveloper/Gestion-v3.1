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

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            {{-- Formulario colapsable para subir PDF --}}
            @canany(['knowledgebase.upload.user', 'knowledgebase.upload.company'])
            <div class="mb-8 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                <div id="togglePdfFormHeader" class="flex justify-between items-center cursor-pointer">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">{{ __('Subir PDF a la IA Memory') }}</h3>
                    <button type="button" aria-expanded="false" aria-controls="pdfFormContainer"
                            class="bg-indigo-100 hover:bg-indigo-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-indigo-600 dark:text-indigo-400 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 flex items-center justify-center w-8 h-8 transition-colors duration-150">
                        <iconify-icon id="formToggleIcon" icon="heroicons:plus-circle-20-solid" class="text-2xl transition-transform duration-300 ease-in-out"></iconify-icon>
                    </button>
                </div>
                <div id="pdfFormContainer" class="overflow-hidden">
                    <form action="{{ route('knowledge_base.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6 pt-6">
                        @csrf
                        <div>
                            <label for="pdf" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Archivo PDF') }} <span class="text-red-500">*</span>
                            </label>
                            <input class="form-control w-full p-3 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" type="file" name="pdf" id="pdf" accept="application/pdf" required>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Solo archivos PDF. Tamaño máximo 20MB.') }}</p>
                        </div>
                        @php
                            $canUser = auth()->user()->can('knowledgebase.upload.user');
                            $canCompany = auth()->user()->can('knowledgebase.upload.company');
                        @endphp
                        @if($canUser && $canCompany)
                            <div>
                                <label for="upload_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Subir como:') }}</label>
                                <select name="upload_type" id="upload_type" class="inputField w-full p-2 border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900">
                                    <option value="user">{{ __('Usuario (privado)') }}</option>
                                    <option value="company">{{ __('Empresa (global)') }}</option>
                                </select>
                            </div>
                        @elseif($canUser)
                            <input type="hidden" name="upload_type" value="user">
                        @elseif($canCompany)
                            <input type="hidden" name="upload_type" value="company">
                        @endif
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 transition-colors duration-150 flex items-center text-lg">
                                <iconify-icon icon="mdi:brain" class="mr-2"></iconify-icon>
                                {{ __('Subir PDF') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endcanany

            {{-- Tabs para los DataTables --}}
            <div class="mt-8">
                <ul class="flex border-b border-slate-200 dark:border-slate-700 mb-4" id="knowledgeTabs" role="tablist">
                    <li class="mr-2">
                        <a href="#user-pdfs" class="inline-block py-2 px-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active" id="user-pdfs-tab" data-toggle="tab" role="tab" aria-controls="user-pdfs" aria-selected="true">{{ __('Mis PDFs') }}</a>
                    </li>
                    @can('knowledgebase.upload.company')
                    <li>
                        <a href="#company-pdfs" class="inline-block py-2 px-4 text-slate-600 border-b-2 border-transparent rounded-t-lg" id="company-pdfs-tab" data-toggle="tab" role="tab" aria-controls="company-pdfs" aria-selected="false">{{ __('PDFs de Empresa') }}</a>
                    </li>
                    @endcan
                </ul>
                <div class="tab-content" id="knowledgeTabsContent">
                    <div class="tab-pane fade show active" id="user-pdfs" role="tabpanel" aria-labelledby="user-pdfs-tab">
                        <div class="overflow-x-auto w-full">
                            <table id="userKnowledgeTable" class="w-full border-collapse dataTable table-fixed" style="width:100%">
                                <thead class="bg-slate-100 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Archivo') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Tipo') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Subido') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Acción') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                    {{-- Se llenará por DataTables AJAX --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @can('knowledgebase.upload.company')
                    <div class="tab-pane fade" id="company-pdfs" role="tabpanel" aria-labelledby="company-pdfs-tab">
                        <div class="overflow-x-auto w-full">
                            <table id="companyKnowledgeTable" class="w-full border-collapse dataTable table-fixed" style="width:100%">
                                <thead class="bg-slate-100 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Archivo') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Tipo') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Subido') }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Acción') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                    {{-- Se llenará por DataTables AJAX --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/es.min.js"></script>
    <script>
        $(document).ready(function () {
            // Inicializar DataTables
            initDataTables();
            
            // Tabs
            $('#knowledgeTabs a').on('click', function (e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                // Ocultar todas las pestañas y mostrar la seleccionada
                $('.tab-pane').removeClass('show active');
                $(target).addClass('show active');
                
                // Actualizar clases de los tabs
                $('#knowledgeTabs a').removeClass('text-blue-600 border-blue-600').addClass('text-slate-600 border-transparent');
                $(this).removeClass('text-slate-600 border-transparent').addClass('text-blue-600 border-blue-600');
                
                // Redimensionar DataTables cuando se cambia de pestaña
                if (target === '#company-pdfs' && $.fn.dataTable.isDataTable('#companyKnowledgeTable')) {
                    $('#companyKnowledgeTable').DataTable().columns.adjust().draw();
                } else if (target === '#user-pdfs' && $.fn.dataTable.isDataTable('#userKnowledgeTable')) {
                    $('#userKnowledgeTable').DataTable().columns.adjust().draw();
                }
            });
            
            // Función para inicializar DataTables
            function initDataTables() {

                // Configuración común para ambas tablas
                var commonConfig = {
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    order: [[2, 'desc']],
                    language: {
                        url: '{{ app()->getLocale() == "es" ? asset("vendor/datatables/es-ES.json") : "" }}',
                        lengthMenu: "Mostrar _MENU_ registros",
                        search: "Buscar:",
                        paginate: {
                            first: "Primero",
                            last: "Último",
                            next: "Siguiente",
                            previous: "Anterior"
                        }
                    },
                    columnDefs: [
                        { className: "dt-center", targets: [1, 2, 3] },
                        { className: "dt-left", targets: [0] },
                        { width: "40%", targets: 0 },
                        { width: "20%", targets: 1 },
                        { width: "20%", targets: 2 },
                        { width: "20%", targets: 3 }
                    ],
                    dom: '<"flex items-center justify-between mb-4"<"flex items-center"l><"flex"f>>rt<"flex items-center justify-between"<"flex"i><"flex"p>>',
                    drawCallback: function() {
                        // Ajustar columnas después de dibujar la tabla
                        $(this).find('thead th').addClass('px-4 py-3 text-xs font-medium uppercase');
                        $(this).find('tbody td').addClass('py-3');
                    }
                };
                
                // DataTable para PDFs de usuario
                $('#userKnowledgeTable').DataTable($.extend({}, commonConfig, {
                    ajax: '{{ route('knowledge_base.user_data') }}',
                    columns: [
                        { 
                            data: 'original_name', 
                            name: 'original_name',
                            className: 'px-4 py-3 text-left'
                        },
                        { 
                            data: 'tipo', 
                            name: 'tipo',
                            className: 'px-4 py-3 text-center',
                            render: function(data) {
                                return '<span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-medium">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'created_at', 
                            name: 'created_at',
                            className: 'px-4 py-3 text-center',
                            render: function(data) {
                                return moment(data).format('DD/MM/YYYY HH:mm');
                            }
                        },
                        { 
                            data: 'action', 
                            name: 'action', 
                            orderable: false, 
                            searchable: false,
                            className: 'px-4 py-3 text-center'
                        }
                    ]
                }));

                // DataTable para PDFs de empresa - Exactamente igual que Mis PDFs
                $('#companyKnowledgeTable').DataTable($.extend({}, commonConfig, {
                    ajax: '{{ route('knowledge_base.company_data') }}',
                    columns: [
                        { 
                            data: 'original_name', 
                            name: 'original_name',
                            className: 'px-4 py-3 text-left'
                        },
                        { 
                            data: 'tipo', 
                            name: 'tipo',
                            className: 'px-4 py-3 text-center',
                            render: function(data) {
                                return '<span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-medium">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'created_at', 
                            name: 'created_at',
                            className: 'px-4 py-3 text-center',
                            render: function(data) {
                                return moment(data).format('DD/MM/YYYY HH:mm');
                            }
                        },
                        { 
                            data: 'action', 
                            name: 'action', 
                            orderable: false, 
                            searchable: false,
                            className: 'px-4 py-3 text-center'
                        }
                    ]
                }));
            }
        });
    </script>
    @endpush

    <!-- Formulario oculto para eliminar PDF -->
    <form id="deletePdfForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="pdf_id" id="pdfIdToDelete">
    </form>

    <script>
        function deleteItem(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este PDF?')) {
                document.getElementById('pdfIdToDelete').value = id;
                document.getElementById('deletePdfForm').action = '{{ url('knowledge-base/delete') }}/' + id;
                document.getElementById('deletePdfForm').submit();
            }
        }
    </script>
</x-app-layout>
