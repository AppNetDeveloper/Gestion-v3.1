<x-app-layout>
    <div class="max-w-xl mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Componer Correo</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('emails.send') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="to" class="block font-medium">Para:</label>
                <input type="email" name="to" id="to" class="w-full border rounded p-2" placeholder="destinatario@ejemplo.com" required>
            </div>
            <div class="mb-4">
                <label for="subject" class="block font-medium">Asunto:</label>
                <input type="text" name="subject" id="subject" class="w-full border rounded p-2" placeholder="Asunto del correo" required>
            </div>
            <div class="mb-4">
                <label for="content" class="block font-medium">Contenido:</label>
                <textarea name="content" id="content" rows="6" class="w-full border rounded p-2" placeholder="Escribe aquí el contenido del correo..." required></textarea>
            </div>
            <div class="mb-4">
                <label for="action_url" class="block font-medium">URL de Acción (opcional):</label>
                <input type="url" name="action_url" id="action_url" class="w-full border rounded p-2" placeholder="https://ejemplo.com/accion">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Enviar Correo
            </button>
        </form>
    </div>
</x-app-layout>
