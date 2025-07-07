<x-app-layout>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header with actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex items-center">
                <h2 class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                    {{ $digitalCertificate->name }}
                </h2>
                @if($digitalCertificate->isExpired())
                    <span class="ml-3 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-md">
                        Expirado
                    </span>
                @elseif($digitalCertificate->is_active)
                    <span class="ml-3 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-md">
                        Activo
                    </span>
                @else
                    <span class="ml-3 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                        Inactivo
                    </span>
                @endif
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-2">
                <a href="{{ route('digital-certificates.index') }}" class="btn btn-sm btn-outline-secondary">
                    <iconify-icon icon="heroicons:arrow-left" class="mr-1"></iconify-icon>
                    {{ __('Back') }}
                </a>
                @can('update', $digitalCertificate)
                    <a href="{{ route('digital-certificates.edit', $digitalCertificate) }}" class="btn btn-sm btn-primary">
                        <iconify-icon icon="heroicons:pencil-square" class="mr-1"></iconify-icon>
                        {{ __('Edit') }}
                    </a>
                @endcan
            </div>
        </div>

        {{-- Información del certificado --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">{{ __('Certificate Information') }}</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Name') }}:</div>
                        <div class="col-span-2 text-sm text-slate-900 dark:text-slate-100">{{ $digitalCertificate->name }}</div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Status') }}:</div>
                        <div class="col-span-2">
                            @if($digitalCertificate->isExpired())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('Expired') }}
                                </span>
                            @elseif($digitalCertificate->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Expiration Date') }}:</div>
                        <div class="col-span-2 text-sm text-slate-900 dark:text-slate-100">
                            {{ $digitalCertificate->expires_at ? $digitalCertificate->expires_at->format('d/m/Y') : 'N/A' }}
                            @if($digitalCertificate->expires_at && $digitalCertificate->expires_at->diffInDays(now(), false) > -30)
                                <span class="ml-2 text-{{ $digitalCertificate->isExpired() ? 'red' : 'amber' }}-500 dark:text-{{ $digitalCertificate->isExpired() ? 'red' : 'amber' }}-400 text-xs">
                                    ({{ $digitalCertificate->expires_at->diffForHumans() }})
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('File Name') }}:</div>
                        <div class="col-span-2 text-sm text-slate-900 dark:text-slate-100 break-all">{{ basename($digitalCertificate->file_path) }}</div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('File Size') }}:</div>
                        <div class="col-span-2 text-sm text-slate-900 dark:text-slate-100">{{ number_format(filesize(storage_path('app/' . $digitalCertificate->file_path)) / 1024, 2) }} KB</div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Upload Date') }}:</div>
                        <div class="col-span-2 text-sm text-slate-900 dark:text-slate-100">{{ $digitalCertificate->created_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 items-center">
                        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Last Update') }}:</div>
                        <div class="col-span-2 text-sm text-slate-900 dark:text-slate-100">{{ $digitalCertificate->updated_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                </div>
            </div>
                    
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">{{ __('Certificate Actions') }}</h3>
                
                <div class="space-y-3">
                    @can('download', $digitalCertificate)
                        <a href="{{ route('digital-certificates.download', $digitalCertificate) }}" 
                           class="btn btn-primary btn-lg d-flex align-items-center justify-content-center w-100 mb-3">
                            <iconify-icon icon="heroicons:arrow-down-tray" class="me-2" width="24" height="24"></iconify-icon>
                            <span class="fw-bold">{{ __('Download Certificate') }}</span>
                        </a>
                    @endcan
                    
                    @can('update', $digitalCertificate)
                        <a href="{{ route('digital-certificates.edit', $digitalCertificate) }}" 
                           class="btn btn-success btn-lg d-flex align-items-center justify-content-center w-100 mb-3">
                            <iconify-icon icon="heroicons:pencil-square" class="me-2" width="24" height="24"></iconify-icon>
                            <span class="fw-bold">{{ __('Edit Certificate') }}</span>
                        </a>
                    @endcan
                    
                    @can('delete', $digitalCertificate)
                        <form action="{{ route('digital-certificates.destroy', $digitalCertificate) }}" 
                              method="POST" 
                              class="mt-3"
                              onsubmit="return confirm('{{ __('Are you sure you want to delete this certificate? This action cannot be undone.') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-lg d-flex align-items-center justify-content-center w-100">
                                <iconify-icon icon="heroicons:trash" class="me-2" width="24" height="24"></iconify-icon>
                                <span class="fw-bold">{{ __('Delete Certificate') }}</span>
                            </button>
                        </form>
                    @endcan
                </div>
                
                @if($digitalCertificate->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-base font-medium text-slate-900 dark:text-slate-100 mb-2">{{ __('Notes') }}</h4>
                        <div class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">
                            {!! nl2br(e($digitalCertificate->notes)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @can('delete', $digitalCertificate)
        <div class="mt-6">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border-l-4 border-red-500">
                <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">{{ __('Danger Zone') }}</h3>
                
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    {{ __('Deleting a certificate is permanent and cannot be undone. This will delete the certificate file and all associated data.') }}
                </p>
                
                <form action="{{ route('digital-certificates.destroy', $digitalCertificate) }}" 
                      method="POST" 
                      onsubmit="return confirm('{{ __('Are you absolutely sure you want to delete this certificate? This action cannot be undone.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-lg d-flex align-items-center justify-content-center w-100">
                        <iconify-icon icon="heroicons:exclamation-triangle" class="me-2" width="24" height="24"></iconify-icon>
                        <span class="fw-bold">{{ __('Permanently Delete Certificate') }}</span>
                    </button>
                </form>
            </div>
        </div>
        @endcan
    </div>
</div>

</x-app-layout>
