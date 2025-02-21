<x-app-layout>
    <div class="mb-6">
      <h1 class="text-2xl font-bold">{{ __("All Notifications") }}</h1>
    </div>

    <div class="card shadow rounded-lg">
      <header class="card-header border-b-0">
        <div class="flex justify-end gap-3 items-center flex-wrap p-4">
          <!-- Refresh Button -->
          <a class="btn inline-flex justify-center btn-dark rounded-full items-center p-2" href="{{ route('notifications.index') }}">
            <iconify-icon icon="mdi:refresh" class="text-xl"></iconify-icon>
          </a>
        </div>
      </header>
      <div class="card-body px-6 pb-6">
        <table id="notificationsTable" class="w-full border-collapse dataTable">
          <thead class="bg-slate-200 dark:bg-slate-700">
            <tr>
              <th class="px-4 py-2 border-r border-gray-300">{{ __("ID") }}</th>
              <th class="px-4 py-2 border-r border-gray-300">{{ __("Message") }}</th>
              <th class="px-4 py-2 border-r border-gray-300">{{ __("Sended") }}</th>
              <th class="px-4 py-2 border-r border-gray-300">{{ __("Seen") }}</th>
              <th class="px-4 py-2">{{ __("Created At") }}</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-slate-800">
            <!-- Los datos se cargarán vía AJAX -->
          </tbody>
        </table>
      </div>
    </div>

    @push('styles')
      <!-- DataTables CSS desde CDN -->
      <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    @endpush

    @push('scripts')
      <!-- jQuery y DataTables -->
      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
      <script>
        $(document).ready(function() {
          // Si la tabla ya fue inicializada, destruirla para evitar errores.
          if ($.fn.DataTable.isDataTable('#notificationsTable')) {
            $('#notificationsTable').DataTable().destroy();
          }
          $('#notificationsTable').DataTable({
            ajax: {
              url: "{{ str_replace('http://', 'https://', route('notifications.data')) }}",
              dataSrc: 'data'
            },
            columns: [
              { data: 'id' },
              { data: 'message' },
              { data: 'sended' },
              { data: 'seen' },
              { data: 'created_at' }
            ],
            order: [[4, "desc"]],
            responsive: true,
            autoWidth: false,
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
        });
      </script>
    @endpush
  </x-app-layout>
