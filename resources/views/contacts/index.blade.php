<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :breadcrumb-items="[
                ['name' => __('Contacts'), 'url' => route('contacts.index'), 'active' => false],
                ['name' => __('Contacts List'), 'url' => '', 'active' => true]
            ]"
            :page-title="__('Contacts')"
        />
    </div>

    @if(session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif

    @if(session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif

    {{-- Botones de acciones --}}
    <div class="mb-4">
        <a href="{{ route('contacts.create') }}" class="btn btn-primary">{{ __("Add New Contact") }}</a>

        <!-- Botón para Exportar a Excel -->
        <a href="{{ route('contacts.export') }}" class="btn btn-success ml-2">
            {{ __("Export to Excel") }}
        </a>

        <!-- Botón para Importar desde Excel -->
        <button class="btn btn-info ml-2" onclick="document.getElementById('importForm').style.display='block'">
            {{ __("Import from Excel") }}
        </button>
    </div>

    {{-- Formulario de Importación --}}
    <div id="importForm" style="display: none;" class="mb-4 p-4 bg-gray-100 rounded-lg shadow">
        <form action="{{ route('contacts.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">{{ __("Upload Excel File") }}</label>
                <input type="file" name="file" id="file" class="form-control" required accept=".xls,.xlsx">
            </div>
            <button type="submit" class="btn btn-primary">{{ __("Upload") }}</button>
            <button type="button" class="btn btn-danger ml-2" onclick="document.getElementById('importForm').style.display='none'">
                {{ __("Cancel") }}
            </button>
        </form>
    </div>

    {{-- Tabla de Contactos con DataTables --}}
    <div class="card shadow rounded-lg">
        <div class="card-body p-6">
            <table id="contactsTable" class="table table-striped table-bordered">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th>{{ __("Name") }}</th>
                        <th>{{ __("Phone") }}</th>
                        <th>{{ __("Address") }}</th>
                        <th>{{ __("Email") }}</th>
                        <th>{{ __("Web") }}</th>
                        <th>{{ __("Telegram") }}</th>
                        <th>{{ __("Actions") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr>
                            <td>{{ $contact->name }}</td>
                            <td>{{ $contact->phone }}</td>
                            <td>{{ $contact->address }}</td>
                            <td>{{ $contact->email }}</td>
                            <td>{{ $contact->web }}</td>
                            <td>{{ $contact->telegram }}</td>
                            <td>
                                {{-- Editar --}}
                                <a href="{{ route('contacts.edit', $contact->id) }}" class="text-blue-600 hover:underline">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                {{-- Botón para WhatsApp --}}
                                <a href="/whatsapp/{{ $contact->phone }}" class="text-blue-600 hover:underline">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                {{-- Botón para WhatsApp --}}
                                <a href="/telegram/{{ $contact->phone }}" class="text-blue-600 hover:underline">
                                    <i class="fa-brands fa-telegram"></i>
                                </a>
                                {{-- Eliminar --}}
                                <form action="{{ route('contacts.destroy', $contact->id) }}" method="POST" class="inline-block ml-2"
                                      onsubmit="return confirm('{{ __("Are you sure you want to delete this contact?") }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">{{ __("No contacts found.") }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('styles')
        <!-- FontAwesome para íconos -->
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
              crossorigin="anonymous" />

        <!-- DataTables CSS -->
        <link rel="stylesheet"
              href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
    @endpush

    @push('scripts')
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

        <script>
            $(document).ready(function() {
                if (!$.fn.DataTable.isDataTable('#contactsTable')) {
                    $('#contactsTable').DataTable({
                        responsive: true,
                        autoWidth: false,
                        destroy: true, // Permite reinicializar sin errores
                        language: {
                            "decimal": "",
                            "emptyTable": "{{ __('No data available in table') }}",
                            "info": "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                            "infoEmpty": "{{ __('Showing 0 to 0 of 0 entries') }}",
                            "infoFiltered": "{{ __('(filtered from _MAX_ total entries)') }}",
                            "infoPostFix": "",
                            "thousands": ",",
                            "lengthMenu": "{{ __('Show _MENU_ entries') }}",
                            "loadingRecords": "{{ __('Loading...') }}",
                            "processing": "{{ __('Processing...') }}",
                            "search": "{{ __('Search:') }}",
                            "zeroRecords": "{{ __('No matching records found') }}",
                            "paginate": {
                                "first": "{{ __('First') }}",
                                "last": "{{ __('Last') }}",
                                "next": "{{ __('Next') }}",
                                "previous": "{{ __('Previous') }}"
                            },
                            "aria": {
                                "sortAscending": "{{ __(': activate to sort column ascending') }}",
                                "sortDescending": "{{ __(': activate to sort column descending') }}"
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
