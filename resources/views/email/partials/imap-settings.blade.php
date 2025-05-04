<!-- resources/views/email/partials/imap-settings.blade.php -->
<div id="modal-imap-settings" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded p-6 w-11/12 md:w-1/2">
        <h3 class="text-xl font-bold mb-4">{{ __("Editar configuración IMAP") }}</h3>
        <form action="{{ route('emails.settings.update') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="imap_host" class="block font-medium">{{ __("Host") }}</label>
                <input type="text" name="imap_host" id="imap_host" class="w-full border rounded p-2"
                       value="{{ auth()->user()->imap_host }}" required>
            </div>
            <div class="mb-4">
                <label for="imap_port" class="block font-medium">{{ __("Port") }}</label>
                <input type="number" name="imap_port" id="imap_port" class="w-full border rounded p-2"
                       value="{{ auth()->user()->imap_port }}" required>
            </div>
            <div class="mb-4">
                <label for="imap_encryption" class="block font-medium">{{ __("Encryption") }}</label>
                <input type="text" name="imap_encryption" id="imap_encryption" class="w-full border rounded p-2"
                       value="{{ auth()->user()->imap_encryption }}">
            </div>
            <div class="mb-4">
                <label for="imap_username" class="block font-medium">{{ __("Username") }}</label>
                <input type="text" name="imap_username" id="imap_username" class="w-full border rounded p-2"
                       value="{{ auth()->user()->imap_username }}" required>
            </div>
            <div class="mb-4">
                <label for="imap_password" class="block font-medium">{{ __("Password") }}</label>
                <input type="password" name="imap_password" id="imap_password" class="w-full border rounded p-2"
                       value="{{ auth()->user()->imap_password }}" required>
            </div>
            <div class="flex justify-end">
                <button type="button"
                        class="mr-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded flex items-center"
                        onclick="document.getElementById('modal-imap-settings').classList.add('hidden')"
                        title="{{ __('Cancelar') }}">
                    <iconify-icon icon="heroicons-outline:x" class="text-xl mr-1"></iconify-icon>
                    {{ __("Cancelar") }}
                </button>
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded flex items-center"
                        title="{{ __('Guardar') }}">
                    <iconify-icon icon="heroicons-outline:check" class="text-xl mr-1"></iconify-icon>
                    {{ __("Guardar") }}
                </button>
            </div>
        </form>
    </div>
</div>
