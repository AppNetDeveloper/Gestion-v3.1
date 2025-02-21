<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <h1 class="text-2xl font-bold mb-4">Crear Nuevo Monitor</h1>
        <form action="{{ route('servermonitor.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="host_id" class="block font-medium">Host</label>
                <select name="host_id" id="host_id" class="form-input mt-1 block w-full">
                    @foreach($hosts as $host)
                        <option value="{{ $host->id }}">{{ $host->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="total_memory" class="block font-medium">Total Memory</label>
                    <input type="number" name="total_memory" id="total_memory" class="form-input mt-1 block w-full" required>
                </div>
                <div>
                    <label for="memory_free" class="block font-medium">Memory Free</label>
                    <input type="number" name="memory_free" id="memory_free" class="form-input mt-1 block w-full" required>
                </div>
                <div>
                    <label for="memory_used" class="block font-medium">Memory Used</label>
                    <input type="number" name="memory_used" id="memory_used" class="form-input mt-1 block w-full" required>
                </div>
                <div>
                    <label for="memory_used_percent" class="block font-medium">Memory Used %</label>
                    <input type="number" name="memory_used_percent" id="memory_used_percent" class="form-input mt-1 block w-full" required>
                </div>
                <div>
                    <label for="disk" class="block font-medium">Disk Usage %</label>
                    <input type="number" name="disk" id="disk" class="form-input mt-1 block w-full" required>
                </div>
                <div>
                    <label for="cpu" class="block font-medium">CPU (%)</label>
                    <input type="number" name="cpu" id="cpu" class="form-input mt-1 block w-full" required>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary">Crear Monitor</button>
            </div>
        </form>
    </div>
</x-app-layout>
