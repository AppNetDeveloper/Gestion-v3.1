<!-- resources/views/email/partials/smtp-settings.blade.php -->
<div id="modal-smtp-settings" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded p-6 w-11/12 md:w-1/2">
        <h3 class="text-xl font-bold mb-4">{{ __("Editar configuración SMTP") }}</h3>
        <form action="{{ route('emails.smtp.update') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="smtp_host" class="block font-medium">{{ __("SMTP Host") }}</label>
                <input type="text" name="smtp_host" id="smtp_host" class="w-full border rounded p-2"
                       value="{{ auth()->user()->smtp_host }}" required>
            </div>
            <div class="mb-4">
                <label for="smtp_port" class="block font-medium">{{ __("SMTP Port") }}</label>
                <input type="number" name="smtp_port" id="smtp_port" class="w-full border rounded p-2"
                       value="{{ auth()->user()->smtp_port }}" required>
            </div>
            <div class="mb-4">
                <label for="smtp_encryption" class="block font-medium">{{ __("SMTP Encryption") }}</label>
                <input type="text" name="smtp_encryption" id="smtp_encryption" class="w-full border rounded p-2"
                       value="{{ auth()->user()->smtp_encryption }}">
            </div>
            <div class="mb-4">
                <label for="smtp_username" class="block font-medium">{{ __("SMTP Username") }}</label>
                <input type="text" name="smtp_username" id="smtp_username" class="w-full border rounded p-2"
                       value="{{ auth()->user()->smtp_username }}" required>
            </div>
            <div class="mb-4">
                <label for="smtp_password" class="block font-medium">{{ __("SMTP Password") }}</label>
                <input type="password" name="smtp_password" id="smtp_password" class="w-full border rounded p-2"
                       value="{{ auth()->user()->smtp_password }}" required>
            </div>
            <div class="flex justify-end">
                <button type="button"
                        class="mr-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded flex items-center"
                        onclick="document.getElementById('modal-smtp-settings').classList.add('hidden')"
                        title="{{ __('Cancelar') }}">
                    <iconify-icon icon="heroicons-outline:x" class="text-xl mr-1"></iconify-icon>
                    {{ __("Cancelar") }}
                </button>
                <button type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center"
                        title="{{ __('Guardar') }}">
                    <iconify-icon icon="heroicons-outline:check" class="text-xl mr-1"></iconify-icon>
                    {{ __("Guardar") }}
                </button>
            </div>
        </form>
    </div>
</div>
