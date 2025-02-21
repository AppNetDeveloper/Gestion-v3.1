<x-app-layout>
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Listado de Servidores</h1>
        <a href="{{ route('hosts.create') }}" class="btn btn-primary">
            Crear Nuevo Servidor
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-500 text-white p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Grid de servidores -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($hosts as $host)
            <div class="card border p-4 rounded shadow">
                <h2 class="text-lg font-semibold">{{ $host->name }}</h2>
                <p><strong>Host:</strong> {{ $host->host }}</p>
                <p><strong>Token:</strong> {{ $host->token }}</p>
                <div class="mt-4 flex space-x-2">
                    <a href="{{ route('hosts.edit', $host->id) }}" class="btn btn-warning">Editar</a>
                    <form action="{{ route('hosts.destroy', $host->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Borrar</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
