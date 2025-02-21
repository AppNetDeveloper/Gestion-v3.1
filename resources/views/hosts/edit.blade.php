<x-app-layout>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Editar Servidor</h1>

        @if ($errors->any())
            <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('hosts.update', $host->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="name" class="block font-medium">Nombre del Servidor</label>
                <input type="text" name="name" id="name" value="{{ $host->name }}" class="form-input mt-1 block w-full" required>
            </div>
            <div class="mb-4">
                <label for="host" class="block font-medium">Host (IP o dominio)</label>
                <input type="text" name="host" id="host" value="{{ $host->host }}" class="form-input mt-1 block w-full" required>
            </div>
            <div class="mb-4">
                <label for="token" class="block font-medium">Token</label>
                <input type="text" name="token" id="token" value="{{ $host->token }}" class="form-input mt-1 block w-full" required>
            </div>
            <div class="flex space-x-4">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('hosts.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
