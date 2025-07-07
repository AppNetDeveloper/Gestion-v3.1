<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Scraping Contacts')" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    {{-- Alert end --}}

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('Contacts for Scraping Task') }}: #{{ $scraping->id }}
                    </h3>
                    <a href="{{ route('scrapings.index') }}" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-medium py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 transition-colors duration-150 flex items-center">
                        <iconify-icon icon="heroicons:arrow-left-20-solid" class="mr-2"></iconify-icon>
                        {{ __('Back to Scraping Tasks') }}
                    </a>
                </div>
                
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <h4 class="font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Keywords') }}</h4>
                        <p class="text-slate-600 dark:text-slate-400">{{ $scraping->keywords }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <h4 class="font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Status') }}</h4>
                        <p>
                            @if($scraping->status == 0)
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs">{{ __('Pending') }}</span>
                            @elseif($scraping->status == 1)
                                <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-xs">{{ __('Completed') }}</span>
                            @elseif($scraping->status == 2)
                                <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-xs">{{ __('Error') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tabla de contactos --}}
            <div class="mt-8">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('Contacts Found') }}</h3>
                <div class="overflow-x-auto">
                    <table id="contactsTable" class="w-full border-collapse dataTable">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('ID') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Address') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($contacts as $contact)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $contact->id }}</td>
                                    <td class="px-4 py-3">{{ $contact->name }}</td>
                                    <td class="px-4 py-3">{{ $contact->phone }}</td>
                                    <td class="px-4 py-3">{{ $contact->email }}</td>
                                    <td class="px-4 py-3">{{ $contact->address }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <a href="{{ route('contacts.show', $contact->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="{{ __('View Contact') }}">
                                                <iconify-icon icon="heroicons:eye-20-solid" class="text-xl"></iconify-icon>
                                            </a>
                                            <a href="{{ route('contacts.edit', $contact->id) }}" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300" title="{{ __('Edit Contact') }}">
                                                <iconify-icon icon="heroicons:pencil-square-20-solid" class="text-xl"></iconify-icon>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $(function() {
                    // Inicializar DataTables si está disponible
                    if (typeof $.fn.DataTable !== 'undefined') {
                        $('#contactsTable').DataTable({
                            language: {
                                url: '{{ app()->getLocale() == "es" ? asset("vendor/datatables/es-ES.json") : "" }}'
                            },
                            responsive: true
                        });
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
