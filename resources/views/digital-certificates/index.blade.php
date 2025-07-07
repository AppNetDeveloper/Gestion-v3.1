<x-app-layout>

<style>
    /* Estilos para los iconos de acción */
    .action-icon { 
        display: inline-block; 
        color: #64748b; 
        transition: color 0.15s ease-in-out; 
        font-size: 1.25rem; 
        cursor: pointer; 
    }
    .dark .action-icon { 
        color: #94a3b8; 
    }
    .action-icon:hover { 
        color: #1e293b; 
    }
    .dark .action-icon:hover { 
        color: #f1f5f9; 
    }
    .action-icon.deleteTask:hover { 
        color: #ef4444; 
    }
    .action-icon.editTask:hover { 
        color: #3b82f6; 
    }
</style>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header with actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                <iconify-icon icon="heroicons:document-text" class="mr-2 text-primary"></iconify-icon> {{ __('Digital Certificates') }}
            </h2>
            <div class="mt-4 sm:mt-0">
                @can('create', \App\Models\DigitalCertificate::class)
                    <a href="{{ route('digital-certificates.create') }}" class="btn btn-sm btn-primary">
                        <iconify-icon icon="heroicons:plus" class="mr-1"></iconify-icon>
                        {{ __('New Certificate') }}
                    </a>
                @endcan
            </div>
        </div>

{{-- Alert start --}}
@if (session('message'))
    <div class="mb-4 p-4 {{ session('type') == 'success' ? 'bg-green-100 dark:bg-green-900 border-green-200 dark:border-green-700 text-green-700 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 border-red-200 dark:border-red-700 text-red-700 dark:text-red-200' }} border rounded-lg">
        {{ session('message') }}
    </div>
@endif
{{-- Alert end --}}

<div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
    <div class="card-body p-6">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap table-hover">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Expires') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($certificates as $certificate)
                        <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="py-3 px-2 font-medium">{{ $certificate->name }}</td>
                            <td class="py-3 px-2">
                                @if($certificate->expires_at && $certificate->expires_at->isPast())
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100">{{ __('Expired') }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100">{{ __('Valid') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-2">{{ $certificate->expires_at ? $certificate->expires_at->format('d/m/Y') : __('N/A') }}</td>
                            <td class="py-3 px-2 text-center">
                                <div class="actions-wrapper" style="display: inline-flex; gap: 0.75rem; align-items: center;">
                                    @can('view', $certificate)
                                        <a href="{{ route('digital-certificates.show', $certificate) }}" class="action-icon" title="{{ __('View details') }}">
                                            <iconify-icon icon="heroicons:eye"></iconify-icon>
                                        </a>
                                    @endcan
                                    @can('update', $certificate)
                                        <a href="{{ route('digital-certificates.edit', $certificate) }}" class="action-icon editTask" title="{{ __('Edit certificate') }}">
                                            <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                                        </a>
                                    @endcan
                                    @can('delete', $certificate)
                                        <form action="{{ route('digital-certificates.destroy', $certificate) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ __('Are you sure you want to delete this certificate?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-icon deleteTask" title="{{ __('Delete certificate') }}">
                                                <iconify-icon icon="heroicons:trash"></iconify-icon>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('download', $certificate)
                                        <a href="{{ route('digital-certificates.download', $certificate) }}" class="action-icon" title="{{ __('Download certificate') }}">
                                            <iconify-icon icon="heroicons:arrow-down-tray"></iconify-icon>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 mb-4">
                                        <iconify-icon icon="heroicons:document" class="text-slate-400 dark:text-slate-500 text-2xl"></iconify-icon>
                                    </div>
                                    <h3 class="text-lg font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('No certificates found') }}</h3>
                                    <p class="text-slate-500 dark:text-slate-400 mb-4">{{ __('Upload your first certificate to get started') }}</p>
                                    @can('create', \App\Models\DigitalCertificate::class)
                                        <a href="{{ route('digital-certificates.create') }}" class="btn btn-primary">
                                            <iconify-icon icon="heroicons:plus" class="mr-1"></iconify-icon> {{ __('Upload Certificate') }}
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $certificates->links() }}
        </div>
    </div>
</div>
</x-app-layout>

@push('scripts')
<script>
    // Código JavaScript adicional si es necesario
</script>
@endpush
