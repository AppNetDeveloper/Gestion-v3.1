<x-app-layout>
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header with actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                <iconify-icon icon="heroicons:document-plus" class="mr-2 text-primary"></iconify-icon> {{ __('New Digital Certificate') }}
            </h2>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('digital-certificates.index') }}" class="btn btn-sm btn-outline-secondary">
                    <iconify-icon icon="heroicons:arrow-left" class="mr-1"></iconify-icon>
                    {{ __('Back to List') }}
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
        <form action="{{ route('digital-certificates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Certificate Name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" class="inputField w-full p-2 border {{ $errors->has('name') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                               id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="certificate" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Certificate File') }} (.pfx o .p12) <span class="text-red-500">*</span>
                        </label>
                        <input type="file" class="inputField w-full p-2 border {{ $errors->has('certificate') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                               id="certificate" name="certificate" accept=".pfx,.p12" required>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Maximum file size: 5MB') }}</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Certificate Password') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="password" class="inputField w-full p-2 border {{ $errors->has('password') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                               id="password" name="password" required minlength="4">
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Minimum 4 characters') }}</p>
                    </div>
                </div>
                
                <div>
                    <div class="mb-4">
                        <label for="expires_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Expiration Date') }}
                        </label>
                        <input type="date" class="inputField w-full p-2 border {{ $errors->has('expires_at') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                               id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Leave empty if the certificate doesn\'t expire') }}</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" class="form-checkbox h-5 w-5 text-indigo-600 rounded border-slate-300 dark:border-slate-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-slate-800" 
                                   name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <span class="ml-2 text-slate-700 dark:text-slate-300">{{ __('Active Certificate') }}</span>
                        </label>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Notes') }}
                        </label>
                        <textarea class="inputField w-full p-2 border {{ $errors->has('notes') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500" 
                                  id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between mt-6">
                <a href="{{ route('digital-certificates.index') }}" class="btn btn-outline-secondary">
                    <iconify-icon icon="heroicons:x-mark" class="mr-1"></iconify-icon> {{ __('Cancel') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="heroicons:document-check" class="mr-1"></iconify-icon> {{ __('Save Certificate') }}
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>

@push('scripts')
<script>
    // Validación del formulario antes de enviar
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const fileInput = document.getElementById('certificate');
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        
        form.addEventListener('submit', function(e) {
            // Validar tamaño del archivo
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > maxFileSize) {
                    e.preventDefault();
                    alert('The file size must be less than 5MB.');
                    return false;
                }
            }
            
            // Mostrar indicador de carga
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Uploading...';
            }
            
            return true;
        });
    });
</script>
@endpush
