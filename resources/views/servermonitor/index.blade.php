<x-app-layout>
    {{-- Verificar si el usuario tiene permiso para ver el módulo --}}
    @canany(['servermonitor show', 'servermonitorbusynes show'])
        {{-- ========================= --}}
        {{--  Monitor de Servidores  --}}
        {{-- ========================= --}}
        <div class="2xl:col-span-9 lg:col-span-8 col-span-12 space-y-6">
            <div class="p-4 card">
                <!-- ENCABEZADO (Botón Crear Servidor) -->
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">{{ __('server_monitor') }}</h1>
                    <div class="flex space-x-4">
                        @canany(['servermonitor create', 'servermonitorbusynes create'])
                            <a href="{{ route('hosts.create') }}"
                               class="btn inline-flex justify-center btn-dark rounded-[25px] items-center !p-2 !px-3
                                      bg-blue-500 text-white hover:bg-blue-600
                                      dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                <iconify-icon class="text-xl mr-2 font-light" icon="heroicons-outline:plus"></iconify-icon>
                                {{ __('create_new_server') }}
                            </a>
                        @else
                            <button class="btn bg-gray-200 text-slate-400 cursor-not-allowed !font-normal" disabled
                                    title="{{ __('create_new_server') . ' - ' . __('no_permission_module') }}">
                                <iconify-icon class="text-xl mr-2 font-light" icon="heroicons-outline:plus"></iconify-icon>
                                {{ __('create_new_server') }}
                            </button>
                        @endcanany
                    </div>
                </div>

                {{-- Mensaje de éxito --}}
                @if(session('success'))
                    <div class="bg-green-500 text-white p-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Tarjetas por cada Host --}}
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($hosts as $host)
                        @php
                            $latest = $host->hostMonitors->first();
                            // Se considera offline si no existe registro o si el último registro es anterior a 3 minutos.
                            $offline = !$latest || $latest->created_at->diffInMinutes(now()) >= 3;
                        @endphp
                        <div class="border rounded shadow-sm cursor-pointer hover:bg-slate-50" data-host-id="{{ $host->id }}">
                            <div class="p-4 bg-slate-100 dark:bg-slate-800 flex justify-between items-center">
                                <h2 class="text-xl font-bold {{ $offline ? 'text-red-500' : 'text-slate-800 dark:text-slate-300' }}">
                                    {{ $host->name }}
                                </h2>
                                @if($offline)
                                    <span class="text-sm font-medium text-red-500 ml-2">OFFLINE</span>
                                @endif
                                <div class="flex space-x-2">
                                    {{-- Botón de editar --}}
                                    @canany(['servermonitor update', 'servermonitorbusynes update'])
                                        <a href="{{ route('hosts.edit', $host->id) }}" class="text-blue-500 hover:text-blue-700" title="{{ __('edit') }}">
                                            <iconify-icon icon="heroicons-outline:pencil-alt" class="text-xl"></iconify-icon>
                                        </a>
                                    @else
                                        <button class="text-gray-400 cursor-not-allowed" disabled title="{{ __('edit') . ' - ' . __('no_permission_module') }}">
                                            <iconify-icon icon="heroicons-outline:pencil-alt" class="text-xl"></iconify-icon>
                                        </button>
                                    @endcanany

                                    {{-- Botón de instalar --}}
                                    @canany(['servermonitor update', 'servermonitorbusynes update', 'servermonitor create', 'servermonitorbusynes create'])
                                        <button type="button" class="text-green-500 hover:text-green-700 install-btn" data-token="{{ $host->token }}" title="{{ __('install') }}">
                                            <iconify-icon icon="heroicons-outline:download" class="text-xl"></iconify-icon>
                                        </button>
                                    @else
                                        <button class="text-gray-400 cursor-not-allowed" disabled title="{{ __('install') . ' - ' . __('no_permission_module') }}">
                                            <iconify-icon icon="heroicons-outline:download" class="text-xl"></iconify-icon>
                                        </button>
                                    @endcanany

                                    {{-- Botón de borrar --}}
                                    @canany(['servermonitor delete', 'servermonitorbusynes delete'])
                                        <form action="{{ route('hosts.destroy', $host->id) }}" method="POST" class="inline-block" onsubmit="return false;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="text-red-500 hover:text-red-700 delete-btn" title="{{ __('delete') }}">
                                                <iconify-icon icon="heroicons-outline:trash" class="text-xl"></iconify-icon>
                                            </button>
                                        </form>
                                    @else
                                        <button class="text-gray-400 cursor-not-allowed" disabled title="{{ __('delete') . ' - ' . __('no_permission_module') }}">
                                            <iconify-icon icon="heroicons-outline:trash" class="text-xl"></iconify-icon>
                                        </button>
                                    @endcanany
                                </div>
                            </div>
                            <div class="p-4 grid grid-cols-3 gap-4">
                                <!-- Tarjeta: CPU -->
                                <div class="py-4 px-4 rounded bg-[#E5F9FF] dark:bg-slate-900">
                                    <div class="flex items-center space-x-2">
                                        <iconify-icon icon="heroicons-outline:chip" class="text-3xl"></iconify-icon>
                                        <div>
                                            <div class="text-slate-800 dark:text-slate-300 text-sm font-medium">{{ __('cpu') }}</div>
                                            <div id="cpu-{{ $host->id }}" class="text-slate-900 dark:text-white text-lg font-medium">
                                                @if($latest)
                                                    {{ number_format($latest->cpu, 1) }}%
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Tarjeta: RAM -->
                                <div class="py-4 px-4 rounded bg-[#FFEDE5] dark:bg-slate-900">
                                    <div class="flex items-center space-x-2">
                                        <iconify-icon icon="heroicons-outline:server" class="text-3xl"></iconify-icon>
                                        <div>
                                            <div class="text-slate-800 dark:text-slate-300 text-sm font-medium">{{ __('ram') }}</div>
                                            <div id="ram-{{ $host->id }}" class="text-slate-900 dark:text-white text-lg font-medium">
                                                @if($latest)
                                                    {{ number_format($latest->memory_used_percent, 1) }}%
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Tarjeta: Disco -->
                                <div class="py-4 px-4 rounded bg-[#EAE5FF] dark:bg-slate-900">
                                    <div class="flex items-center space-x-2">
                                        <iconify-icon icon="heroicons-outline:folder" class="text-3xl"></iconify-icon>
                                        <div>
                                            <div class="text-slate-800 dark:text-slate-300 text-sm font-medium">{{ __('disk') }}</div>
                                            <div id="disk-{{ $host->id }}" class="text-slate-900 dark:text-white text-lg font-medium">
                                                @if($latest)
                                                    {{ number_format($latest->disk, 1) }}%
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Gráfica en vivo --}}
            <div class="p-4 card">
                <div class="card-header border-b p-4">
                    <h4 class="card-title text-lg font-bold">{{ __('live_monitoring') }}</h4>
                </div>
                <div class="card-body p-4">
                    <!-- Aquí se renderizará la gráfica de ApexCharts -->
                    <div id="liveChart"></div>
                </div>
            </div>
        </div>
    @else
        <div class="p-4 card">
            <div class="card-body">
                <p class="text-red-500 text-center font-bold">{{ __('no_permission_module') }}</p>
            </div>
        </div>
    @endcanany

    @push('scripts')
        <!-- Iconify -->
        <script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
        <!-- ApexCharts -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Eliminamos la barra final en appUrl si existe
            const appUrl = "{{ rtrim(config('app.url'), '/') }}";

            (function(){
                // Variables para la gráfica en vivo con ApexCharts
                let currentHostId = null;
                let chartInstance = null;
                let pollInterval = null;

                // Datos de la gráfica
                let chartCategories = [];
                let seriesCPU = [];
                let seriesRAM = [];
                let seriesDisk = [];

                // 1) Creamos la gráfica como LINE CHART
                function createChart() {
                    const options = {
                        chart: {
                            type: 'line',
                            height: 350,
                            animations: {
                                enabled: true,
                                easing: 'linear',
                                dynamicAnimation: { speed: 500 }
                            }
                        },
                        series: [
                            { name: 'CPU (%)', data: seriesCPU, color: '#000000' },
                            { name: 'RAM (%)', data: seriesRAM, color: '#F97316' },
                            { name: 'Disco (%)', data: seriesDisk, color: '#FACC15' }
                        ],
                        stroke: { curve: 'smooth' },
                        xaxis: {
                            categories: chartCategories,
                            title: { text: 'Hora' }
                        },
                        yaxis: {
                            max: 100,
                            title: { text: 'Porcentaje' }
                        },
                        legend: { position: 'bottom' }
                    };
                    chartInstance = new ApexCharts(document.querySelector("#liveChart"), options);
                    chartInstance.render();
                }

                // 2) Función para actualizar la gráfica con los datos en memoria
                function updateChart() {
                    chartInstance.updateOptions({
                        xaxis: { categories: chartCategories }
                    });
                    chartInstance.updateSeries([
                        { name: 'CPU (%)', data: seriesCPU, color: '#000000' },
                        { name: 'RAM (%)', data: seriesRAM, color: '#F97316' },
                        { name: 'Disco (%)', data: seriesDisk, color: '#FACC15' }
                    ]);
                }

                function resetChartData() {
                    chartCategories = [];
                    seriesCPU = [];
                    seriesRAM = [];
                    seriesDisk = [];
                }

                // 3) Obtener el historial (últimos 40 registros) para el host
                async function fetchHistoricalData(hostId) {
                    try {
                        const url = `${appUrl}/servermonitor/history/${hostId}`;
                        const response = await fetch(url);
                        const dataArray = await response.json();
                        dataArray.forEach(item => {
                            chartCategories.push(item.timestamp);
                            seriesCPU.push(item.cpu);
                            seriesRAM.push(item.memory);
                            seriesDisk.push(item.disk);
                        });
                        updateChart();
                    } catch (error) {
                        console.error('Error al obtener datos históricos para el host ' + hostId, error);
                    }
                }

                // 4) Obtener el dato más reciente y agregarlo a la gráfica
                async function fetchLatestChartData(hostId) {
                    try {
                        const url = `${appUrl}/servermonitor/latest/${hostId}`;
                        const response = await fetch(url);
                        const json = await response.json();
                        chartCategories.push(json.timestamp);
                        seriesCPU.push(json.cpu);
                        seriesRAM.push(json.memory);
                        seriesDisk.push(json.disk);
                        if (chartCategories.length > 60) {
                            chartCategories.shift();
                            seriesCPU.shift();
                            seriesRAM.shift();
                            seriesDisk.shift();
                        }
                        updateChart();
                    } catch (error) {
                        console.error('Error al obtener datos para la gráfica del host ' + hostId, error);
                    }
                }

                // 5) Iniciar el monitoreo en vivo para un host (cargando historial y luego actualizando)
                async function startLiveMonitoring(hostId) {
                    currentHostId = hostId;
                    resetChartData();
                    if (chartInstance) {
                        chartInstance.destroy();
                    }
                    createChart();
                    if (pollInterval) {
                        clearInterval(pollInterval);
                    }
                    await fetchHistoricalData(currentHostId);
                    fetchLatestChartData(currentHostId);
                    pollInterval = setInterval(() => {
                        fetchLatestChartData(currentHostId);
                    }, 5000);
                }

                // 6) Actualizar los datos de las tarjetas (CPU, RAM, Disco) cada 30 segundos
                function fetchHostData(hostId) {
                    const url = `${appUrl}/servermonitor/latest/${hostId}`;
                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            if(data){
                                document.getElementById('cpu-' + hostId).innerText = parseFloat(data.cpu).toFixed(1) + '%';
                                document.getElementById('ram-' + hostId).innerText = parseFloat(data.memory).toFixed(1) + '%';
                                document.getElementById('disk-' + hostId).innerText = parseFloat(data.disk).toFixed(1) + '%';
                            }
                        })
                        .catch(error => console.error('Error al obtener datos del host ' + hostId, error));
                }

                function updateAllHosts() {
                    const hostIds = [
                        @foreach($hosts as $host)
                            {{ $host->id }},
                        @endforeach
                    ];
                    hostIds.forEach(hostId => {
                        fetchHostData(hostId);
                    });
                }

                // 7) Configurar el click en cada tarjeta para cambiar el host en la gráfica
                document.querySelectorAll('[data-host-id]').forEach(card => {
                    card.addEventListener('click', function(){
                        const hostId = parseInt(this.getAttribute('data-host-id'));
                        if (hostId !== currentHostId) {
                            startLiveMonitoring(hostId);
                        }
                    });
                });

                // 8) Al cargar la página, selecciona el primer host y comienza la actualización
                document.addEventListener('DOMContentLoaded', () => {
                    const firstCard = document.querySelector('[data-host-id]');
                    if(firstCard){
                        const hostId = parseInt(firstCard.getAttribute('data-host-id'));
                        startLiveMonitoring(hostId);
                    }
                    setInterval(updateAllHosts, 30000);
                });

                // 9) Configuración para el botón de borrar usando SweetAlert2
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function(){
                        const form = this.closest('form');
                        Swal.fire({
                            title: '{{ __("confirm_delete_title") }}',
                            text: '{{ __("confirm_delete_text") }}',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: '{{ __("confirm_delete_button") }}'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });

                // 10) Configuración para el botón de instalar en cada tarjeta
                document.querySelectorAll('.install-btn').forEach(button => {
                    button.addEventListener('click', function(){
                        const hostToken = this.getAttribute('data-token');
                        Swal.fire({
                            title: '{{ __("choose_version") }}',
                            input: 'radio',
                            inputOptions: {
                                linux: '{{ __("linux") }}',
                                windows: '{{ __("windows") }}'
                            },
                            inputValidator: (value) => {
                                if (!value) {
                                    return '{{ __("choose_version") }}: Debes elegir una opción!';
                                }
                            },
                            confirmButtonText: 'Siguiente',
                            showCancelButton: true,
                            width: (window.innerWidth < 800 ? '90%' : '800px')
                        }).then((result) => {
                            if(result.isConfirmed){
                                if(result.value === 'windows'){
                                Swal.fire({
                                    title: '{{ __("install_instructions_windows") }}',
                                    html: `
<p><strong>1.</strong> {{ __("install_step_windows_1") }}</p>
<pre style="text-align: left;">
import psutil
import requests
import time

# Configuración de la API
API_URL = "${appUrl}/api/server-monitor"  # Reemplaza con la URL real de la API
API_TOKEN = "${hostToken}"  # Token del host

def collect_metrics():
    cpu_usage = psutil.cpu_percent(interval=1)
    memoria = psutil.virtual_memory()
    total_memory = memoria.total
    memory_free = memoria.free
    memory_used = memoria.used
    memory_used_percent = memoria.percent
    disco = psutil.disk_usage('/')
    disk_usage_percent = disco.percent
    payload = {
        "token": API_TOKEN,
        "total_memory": total_memory,
        "memory_free": memory_free,
        "memory_used": memory_used,
        "memory_used_percent": memory_used_percent,
        "disk": disk_usage_percent,
        "cpu": cpu_usage
    }
    return payload

def send_data(payload):
    headers = {"Content-Type": "application/json"}
    try:
        response = requests.post(API_URL, json=payload, headers=headers)
        return response
    except Exception as e:
        print("Error al enviar la petición:", e)
        return None

def main():
    data = collect_metrics()
    print("Enviando los siguientes datos a la API:")
    print(data)
    response = send_data(data)
    if response:
        if response.status_code == 201:
            print("Datos almacenados exitosamente.")
        else:
            print("Error al almacenar datos. Código de estado:", response.status_code)
            print("Respuesta:", response.text)
    else:
        print("No se pudo enviar la petición a la API.")

if __name__ == '__main__':
    try:
        while True:
            main()
            print("Esperando 30 segundos para el siguiente envío.")
            time.sleep(30)
    except KeyboardInterrupt:
        print("Script interrumpido por el usuario. Saliendo...")
</pre>
<p><strong>2.</strong> {{ __("install_step_windows_2") }}</p>
<p><strong>3.</strong> {{ __("install_step_windows_3") }}</p>
<pre style="text-align: left;">
schtasks /create /tn "AppNetDeveloper Monitor" /tr "python C:\AppNetDeveloper\appnetdev-monitor.py" /sc onstart /ru SYSTEM
</pre>
<p><strong>4.</strong> {{ __("install_step_windows_4") }}</p>
<p><strong>5.</strong> {{ __("install_step_windows_5") }}</p>
`,
                                        icon: 'info',
                                        confirmButtonText: 'Entendido',
                                        width: (window.innerWidth < 800 ? '90%' : '80%')
                                    });
                                }else if(result.value === 'linux'){
                                    Swal.fire({
                                        title: '{{ __("install_instructions_linux") }}',
                                        html: `
<p><strong>1.</strong> {{ __("install_step_1") }}</p>
<pre style="text-align: left;">
import psutil
import requests
import time

# Configuración de la API
API_URL = "${appUrl}/api/server-monitor"  # Reemplaza con la URL real de la API
API_TOKEN = "${hostToken}"  # Token del host

def collect_metrics():
    cpu_usage = psutil.cpu_percent(interval=1)
    memoria = psutil.virtual_memory()
    total_memory = memoria.total
    memory_free = memoria.free
    memory_used = memoria.used
    memory_used_percent = memoria.percent
    disco = psutil.disk_usage('/')
    disk_usage_percent = disco.percent
    payload = {
        "token": API_TOKEN,
        "total_memory": total_memory,
        "memory_free": memory_free,
        "memory_used": memory_used,
        "memory_used_percent": memory_used_percent,
        "disk": disk_usage_percent,
        "cpu": cpu_usage
    }
    return payload

def send_data(payload):
    headers = {"Content-Type": "application/json"}
    try:
        response = requests.post(API_URL, json=payload, headers=headers)
        return response
    except Exception as e:
        print("Error al enviar la petición:", e)
        return None

def main():
    data = collect_metrics()
    print("Enviando los siguientes datos a la API:")
    print(data)
    response = send_data(data)
    if response:
        if response.status_code == 201:
            print("Datos almacenados exitosamente.")
        else:
            print("Error al almacenar datos. Código de estado:", response.status_code)
            print("Respuesta:", response.text)
    else:
        print("No se pudo enviar la petición a la API.")

if __name__ == '__main__':
    try:
        while True:
            main()
            print("Esperando 30 segundos para el siguiente envío..")
            time.sleep(30)
    except KeyboardInterrupt:
        print("Script interrumpido por el usuario. Saliendo...")
</pre>
<p><strong>2.</strong> {{ __("install_step_2") }}</p>
<p><strong>3.</strong> {{ __("install_step_3") }}</p>
<p><strong>4.</strong> {{ __("install_step_4") }}</p>
<p><strong>5.</strong> {{ __("install_step_5") }}</p>
<pre style="text-align: left;">
[Unit]
Description=AppNetDev Monitor Service
After=network.target

[Service]
Type=simple
# Especifica el usuario con el que se ejecutará el servicio (en este caso, root)
User=root
# Ruta al intérprete de Python, asegúrate de que sea la correcta en tu sistema
ExecStart=/usr/bin/python3 /root/appnetdev-monitor.py
Restart=on-failure
# Opcional: define el entorno de trabajo si es necesario
WorkingDirectory=/root

[Install]
WantedBy=multi-user.target

</pre>
<p><strong>6.</strong> {{ __("install_step_6") }}</p>
`,
                                        icon: 'info',
                                        confirmButtonText: 'Entendido',
                                        width: (window.innerWidth  < 800 ? '90%' : '80%')
                                    });
                                }
                            }
                        });
                    });
                });
            })();
        </script>
    @endpush
</x-app-layout>
