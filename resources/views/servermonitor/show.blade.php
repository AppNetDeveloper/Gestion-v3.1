<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-12 gap-6">
            <!-- Columna de Detalles -->
            <div class="col-span-12 lg:col-span-6">
                <div class="card shadow-lg">
                    <div class="card-header border-b px-6 py-4">
                        <h2 class="text-xl font-bold text-slate-800">Detalle del Monitor #{{ $monitor->id }}</h2>
                    </div>
                    <div class="card-body px-6 py-4 space-y-3">
                        <p class="text-slate-700"><strong>Host:</strong> {{ $monitor->hostList->name ?? 'N/A' }}</p>
                        <p class="text-slate-700"><strong>Total Memory:</strong> {{ $monitor->total_memory }}</p>
                        <p class="text-slate-700"><strong>Memory Free:</strong> {{ $monitor->memory_free }}</p>
                        <p class="text-slate-700"><strong>Memory Used:</strong> {{ $monitor->memory_used }}</p>
                        <p class="text-slate-700"><strong>Memory Used %:</strong> {{ $monitor->memory_used_percent }}%</p>
                        <p class="text-slate-700"><strong>Disk Usage:</strong> {{ $monitor->disk }}%</p>
                        <p class="text-slate-700"><strong>CPU:</strong> {{ $monitor->cpu }}%</p>
                        <div class="mt-4">
                            <a href="{{ route('servermonitor.index') }}" class="btn btn-dark">
                                Volver al Listado
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Columna de Gráfica -->
            <div class="col-span-12 lg:col-span-6">
                <div class="card shadow-lg">
                    <div class="card-header border-b px-6 py-4">
                        <h2 class="text-xl font-bold text-slate-800">Uso de CPU</h2>
                    </div>
                    <div class="card-body px-6 py-4">
                        <canvas id="cpuChart" class="w-full"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- Incluir Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('cpuChart').getContext('2d');
            const cpuChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['CPU Utilizado', 'CPU Libre'],
                    datasets: [{
                        data: [{{ $monitor->cpu }}, {{ 100 - $monitor->cpu }}],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 14
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
</x-app-layout>
