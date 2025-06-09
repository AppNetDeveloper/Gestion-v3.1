<x-app-layout>
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header with actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                <iconify-icon icon="heroicons:pencil-square" class="mr-2 text-primary"></iconify-icon> {{ __('Edit Digital Certificate') }}: {{ $digitalCertificate->name }}
            </h2>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('digital-certificates.show', $digitalCertificate) }}" class="btn btn-sm btn-outline-secondary">
                    <iconify-icon icon="heroicons:arrow-left" class="mr-1"></iconify-icon>
                    {{ __('Back to Details') }}
                </a>
            </div>
        </div>
        
        {{-- Alert start --}}
        @if ($errors->any())
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
                <form action="{{ route('digital-certificates.update', $digitalCertificate) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Certificate Name') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="inputField w-full p-2 border {{ $errors->has('name') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="name" name="name" value="{{ old('name', $digitalCertificate->name) }}" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="certificate" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Replace Certificate File') }} (.pfx o .p12)
                                </label>
                                <input type="file" class="inputField w-full p-2 border {{ $errors->has('certificate') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="certificate" name="certificate" accept=".pfx,.p12">
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('Current File') }}: {{ basename($digitalCertificate->file_path) }} 
                                    ({{ number_format(filesize(storage_path('app/' . $digitalCertificate->file_path)) / 1024, 2) }} KB)
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Leave empty to keep current certificate. Maximum file size: 5MB') }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Certificate Password') }}
                                </label>
                                <input type="password" class="inputField w-full p-2 border {{ $errors->has('password') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="password" name="password" placeholder="{{ __('Leave empty to keep current password') }}" minlength="4">
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Minimum 4 characters if changed') }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <label for="expires_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Expiration Date') }}
                                </label>
                                <input type="date" class="inputField w-full p-2 border {{ $errors->has('expires_at') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="expires_at" name="expires_at" 
                                       value="{{ old('expires_at', $digitalCertificate->expires_at ? $digitalCertificate->expires_at->format('Y-m-d') : '') }}">
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Leave empty if the certificate doesn\'t expire') }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-slate-300 dark:border-slate-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-slate-800" 
                                           name="is_active" id="is_active" value="1" 
                                           {{ old('is_active', $digitalCertificate->is_active) ? 'checked' : '' }}>
                                    <span class="ml-2 text-slate-700 dark:text-slate-300">{{ __('Active Certificate') }}</span>
                                </label>
                            </div>
                            
                            <div class="mb-4">
                                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('Notes') }}
                                </label>
                                <textarea class="inputField w-full p-2 border {{ $errors->has('notes') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $digitalCertificate->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mt-6">
                        <a href="{{ route('digital-certificates.show', $digitalCertificate) }}" class="btn btn-outline-secondary">
                            <iconify-icon icon="heroicons:x-mark" class="mr-1"></iconify-icon> {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <iconify-icon icon="heroicons:document-check" class="mr-1"></iconify-icon> {{ __('Update Certificate') }}
                        </button>
                    </div>
                </form>
                
                @can('delete', $digitalCertificate)
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="bg-white dark:bg-slate-800 border-l-4 border-red-500 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-red-700 dark:text-red-400 mb-2">{{ __('Danger Zone') }}</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ __('This action cannot be undone. This will permanently delete this digital certificate.') }}</p>
                        <form action="{{ route('digital-certificates.destroy', $digitalCertificate) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                                    onclick="return confirm('{{ __('Are you sure you want to delete this certificate? This action cannot be undone.') }}');">
                                <iconify-icon icon="heroicons:trash" class="mr-2"></iconify-icon>
                                {{ __('Delete Certificate') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Validación del formulario antes de enviar
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const fileInput = document.getElementById('certificate');
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        
        form.addEventListener('submit', function(e) {
            // Validar tamaño del archivo si se está subiendo uno nuevo
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > maxFileSize) {
                    e.preventDefault();
                    alert('{{ __('The file must be less than 5MB.') }}');
                    return false;
                }
            }
            
            // Mostrar indicador de carga
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                const originalContent = submitButton.innerHTML;
                submitButton.innerHTML = '<iconify-icon icon="heroicons:arrow-path" class="animate-spin mr-2"></iconify-icon> Guardando...';
                
                // Restaurar el botón si hay un error
                setTimeout(function() {
                    if (form.getAttribute('data-submitted') !== 'true') {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalContent;
                    }
                }, 10000);
            }
            
            form.setAttribute('data-submitted', 'true');
            return true;
        });
    });
</script>
@endpush
</x-app-layout>
