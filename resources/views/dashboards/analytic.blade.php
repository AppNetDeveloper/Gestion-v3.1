<x-app-layout>

    <!-- START:: Breadcrumbs -->
    <div class="flex justify-between flex-wrap items-center mb-6">
        <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block ltr:pr-4 rtl:pl-4 mb-4 sm:mb-0 flex space-x-3 rtl:space-x-reverse">
            Dashboard
        </h4>
        <div class="flex sm:space-x-4 space-x-2 sm:justify-end items-center rtl:space-x-reverse">
            <button class="btn leading-0 inline-flex justify-center bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300 !font-normal">
                <span class="flex items-center">
                    <iconify-icon class="text-xl ltr:mr-2 rtl:ml-2 font-light" icon="heroicons-outline:calendar"></iconify-icon>
                    <span>Weekly</span>
                </span>
            </button>
            <button class="btn leading-0 inline-flex justify-center bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300 !font-normal">
                <span class="flex items-center">
                    <iconify-icon class="text-xl ltr:mr-2 rtl:ml-2 font-light" icon="heroicons-outline:filter"></iconify-icon>
                    <span>Select Date</span>
                </span>
            </button>
        </div>
    </div>
    <!-- END:: Breadcrumbs -->

    <div class="grid grid-cols-12 gap-5 mb-5">
        <div class="2xl:col-span-3 lg:col-span-4 col-span-12">
            <div class="bg-no-repeat bg-cover bg-center p-4 rounded-[6px] relative"
                 style="background-image: url(images/all-img/widget-bg-1.png)">
                <div class="max-w-[180px]">
                    <div class="text-xl font-medium text-slate-900 mb-2">
                        Upgrade your AppNet Developer
                    </div>
                    <p class="text-sm text-slate-800">
                        Pro plan for better results
                    </p>
                </div>
                <div class="absolute top-1/2 -translate-y-1/2 ltr:right-6 rtl:left-6 mt-2 h-12 w-12 bg-white rounded-full text-xs font-medium
                        flex flex-col items-center justify-center">
                    Now
                </div>
            </div>
        </div>
        <div class="2xl:col-span-9 lg:col-span-8 col-span-12">
            <div class="p-4 card">
                <div class="grid md:grid-cols-3 col-span-1 gap-4">

                    <!-- Revenue -->
                    <div class="py-[18px] px-4 rounded-[6px] bg-[#E5F9FF] dark:bg-slate-900">
                        <div class="flex items-center space-x-6 rtl:space-x-reverse">
                            <div class="flex-none">
                                <div id="wline1"></div>
                            </div>
                            <div class="flex-1">
                                <div class="text-slate-800 dark:text-slate-300 text-sm mb-1 font-medium">
                                    {{ __('Total Revenue') }}
                                </div>
                                <div class="text-slate-900 dark:text-white text-lg font-medium">
                                    {{ $data['yearlyRevenue']['currencySymbol'].' '.$data['yearlyRevenue']['total'] }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products sold -->
                    <div class="py-[18px] px-4 rounded-[6px] bg-[#FFEDE5] dark:bg-slate-900">
                        <div class="flex items-center space-x-6 rtl:space-x-reverse">
                            <div class="flex-none">
                                <div id="wline2"></div>
                            </div>
                            <div class="flex-1">
                                <div class="text-slate-800 dark:text-slate-300 text-sm mb-1 font-medium">
                                    {{ __('Product Sold') }}
                                </div>
                                <div class="text-slate-900 dark:text-white text-lg font-medium">
                                    {{ $data['productSold']['total'] }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Growth -->
                    <div class="py-[18px] px-4 rounded-[6px] bg-[#EAE5FF] dark:bg-slate-900">
                        <div class="flex items-center space-x-6 rtl:space-x-reverse">
                            <div class="flex-none">
                                <div id="wline3"></div>
                            </div>
                            <div class="flex-1">
                                <div class="text-slate-800 dark:text-slate-300 text-sm mb-1 font-medium">
                                    {{ __(' Growth') }}
                                </div>
                                <div class="text-slate-900 dark:text-white text-lg font-medium">
                                    {{ $data['growth']['preSymbol'].' '.$data['growth']['total'].$data['growth']['postSymbol'] }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- End grid -->
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-5">
        <div class="lg:col-span-8 col-span-12">
            <div class="card">
                <div class="card-body p-6">
                    <div class="legend-ring">
                        <div id="revenue-barchart"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lg:col-span-4 col-span-12">
            <div class="card">
                <header class="card-header">
                    <h4 class="card-title">Overview</h4>
                    <div class="relative">
                        <div class="dropdown relative">
                            <button class="text-xl text-center block w-full " type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="text-lg inline-flex h-6 w-6 flex-col items-center justify-center border border-slate-200 dark:border-slate-700
                                    rounded dark:text-slate-400">
                                    <iconify-icon icon="heroicons-outline:dots-horizontal"></iconify-icon>
                                </span>
                            </button>
                            <ul class=" dropdown-menu min-w-[120px] absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700
                                shadow z-[2] overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                <li>
                                    <a href="#" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600
                                        dark:hover:text-white">
                                        Last 28 Days
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600
                                        dark:hover:text-white">
                                        Last Month
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600
                                        dark:hover:text-white">
                                        Last Year
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>
                <div class="card-body p-6">
                    <div id="radial-bar"></div>
                </div>
            </div>
        </div>
        <div class="lg:col-span-8 col-span-12">
            <div class="card">
                <header class="card-header noborder">
                    <h4 class="card-title">All Company</h4>
                    <div class="relative">
                        <div class="dropdown relative">
                            <button class="text-xl text-center block w-full " type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="text-lg inline-flex h-6 w-6 flex-col items-center justify-center border border-slate-200 dark:border-slate-700
                                    rounded dark:text-slate-400">
                                    <iconify-icon icon="heroicons-outline:dots-horizontal"></iconify-icon>
                                </span>
                            </button>
                            <ul class=" dropdown-menu min-w-[120px] absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700
                                shadow z-[2] overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                <li>
                                    <a href="#" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600
                                        dark:hover:text-white">
                                        Last 28 Days
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600
                                        dark:hover:text-white">
                                        Last Month
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600
                                        dark:hover:text-white">
                                        Last Year
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>
                <div class="card-body p-6">
                    <div class="overflow-x-auto -mx-6">
                        <div class="inline-block min-w-full align-middle">
                            <div class="overflow-hidden ">
                                <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                                    <thead class="  bg-slate-200 dark:bg-slate-700">
                                        <tr>
                                            <th scope="col" class="table-th">
                                                {{ __('NAME') }}
                                            </th>
                                            <th scope="col" class="table-th">
                                                {{ __('EMAIL') }}
                                            </th>
                                            <th scope="col" class="table-th">
                                                {{ __('MEMBER SINCE') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                        @foreach($data['users'] as $user)
                                        @php
                                            $mediaId = App\Models\Media::where('model_id', $user->id)
                                                            ->where('collection_name', 'profile-image')
                                                            ->value('id');
                                        @endphp
                                        <tr>
                                            <td class="table-td">
                                                <div class="flex items-center">
                                                    <div class="flex-none">
                                                        <div class="w-8 h-8 rounded-[100%] ltr:mr-3 rtl:ml-3">
                                                            <img class="w-full h-full rounded-[100%] object-cover"
                                                                 src="{{$mediaId
                                                                    ? route('image.show', ['media' => $mediaId])
                                                                    : Avatar::create($user->name)->toBase64() }}"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 text-start">
                                                        <h4 class="text-sm font-medium text-slate-600 whitespace-nowrap">
                                                            {{ $user->name }}
                                                        </h4>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="table-td">{{ $user->email }}</td>
                                            <td class="table-td ">{{ $user->created_at->diffForHumans() }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination-area flex flex-wrap gap-3 items-center justify-center pt-8 px-8">
                                {{ $data['users']->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 col-span-12">
            <div class="card ">
                <div class="card-header ">
                    <h4 class="card-title">Recent Activity</h4>
                </div>
                <div class="card-body p-6">
                    <!-- BEGIN: Activity Card -->
                    <div>
                        <ul class="list-item space-y-3 h-full overflow-x-auto">
                            <li class="flex items-center space-x-3 rtl:space-x-reverse border-b border-slate-100 dark:border-slate-700 last:border-b-0 pb-3 last:pb-0">
                                <div>
                                    <div class="w-8 h-8 rounded-[100%]">
                                        <img src="images/users/user-1.jpg" alt="" class="w-full h-full rounded-[100%] object-cover">
                                    </div>
                                </div>
                                <div class="text-start overflow-hidden text-ellipsis whitespace-nowrap max-w-[63%]">
                                    <div class="text-sm text-slate-600 dark:text-slate-300 overflow-hidden text-ellipsis whitespace-nowrap">
                                        Finance KPI Mobile app launch preparion meeting.
                                    </div>
                                </div>
                                <div class="flex-1 ltr:text-right rtl:text-left">
                                    <div class="text-sm font-light text-slate-400 dark:text-slate-400">
                                        1 hours
                                    </div>
                                </div>
                            </li>
                            <li class="flex items-center space-x-3 rtl:space-x-reverse border-b border-slate-100 dark:border-slate-700 last:border-b-0 pb-3 last:pb-0">
                                <div>
                                    <div class="w-8 h-8 rounded-[100%]">
                                        <img src="images/users/user-2.jpg" alt="" class="w-full h-full rounded-[100%] object-cover">
                                    </div>
                                </div>
                                <div class="text-start overflow-hidden text-ellipsis whitespace-nowrap max-w-[63%]">
                                    <div class="text-sm text-slate-600 dark:text-slate-300 overflow-hidden text-ellipsis whitespace-nowrap">
                                        Finance KPI Mobile app launch preparion meeting.
                                    </div>
                                </div>
                                <div class="flex-1 ltr:text-right rtl:text-left">
                                    <div class="text-sm font-light text-slate-400 dark:text-slate-400">
                                        1 hours
                                    </div>
                                </div>
                            </li>
                            <!-- ... Repite más elementos de lista según tu necesidad ... -->
                        </ul>
                    </div>
                    <!-- END: Activity Card -->
                </div>
            </div>
        </div>

        <!-- MONITOR DE SERVIDOR -->
        <div class="lg:col-span-8 col-span-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Most Sales</h4>
                </div>
                <div class="card-body p-6">
                    <div class="md:flex items-center">
                        <div class="grow-0">
                            <h4 class="text-slate-600 dark:text-slate-200 text-sm font-normal mb-[6px]">
                                Total earnings
                            </h4>
                            <div class="text-lg font-medium mb-[6px] dark:text-white text-slate-900">
                                $12,65,64787.00
                            </div>
                            <div class="text-xs font-light dark:text-slate-200">
                                <span class="text-primary-500">+08%</span> From last month
                            </div>
                            <ul class="bg-slate-50 dark:bg-slate-900 rounded p-4 min-w-[184px] space-y-5 mt-4">
                                <li class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                                    <span class="flex space-x-2 rtl:space-x-reverse items-center">
                                        <span class="inline-flex h-[6px] w-[6px] bg-primary-500 ring-opacity-25 rounded-full ring-4
                                            bg-primary-500 ring-primary-500">
                                        </span>
                                        <span>Nevada</span>
                                    </span>
                                    <span>$125k</span>
                                </li>
                                <li class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                                    <span class="flex space-x-2 rtl:space-x-reverse items-center">
                                        <span class="inline-flex h-[6px] w-[6px] bg-primary-500 ring-opacity-25 rounded-full ring-4
                                            bg-success-500 ring-success-500">
                                        </span>
                                        <span>Colorado</span>
                                    </span>
                                    <span>$$325k</span>
                                </li>
                                <!-- ... Más filas si deseas ... -->
                            </ul>
                        </div>
                        <div class="grow">
                            <div class="h-[360px] w-full bg-white dark:bg-slate-800 ltr:pl-10 rtl:pr-10">
                                <div id="world-map" class="h-full w-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CAMBIO AQUÍ: Monitor de Servidor con ApexCharts en lugar de Chart.js -->
        <div class="lg:col-span-4 col-span-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Monitor de Servidor</h4>
                </div>
                <div class="card-body p-6">

                    <!-- Reemplazamos el canvas de Chart.js por un div de ApexCharts -->
                    <div id="cpu-load-chart"></div>

                    <div class="bg-slate-50 dark:bg-slate-900 rounded p-4 mt-8 flex justify-between flex-wrap">
                        <div class="space-y-1">
                            <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">
                                Memoria RAM
                            </h4>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ exec("free -m | awk 'NR==2{printf \"%.2f%%\", $3*100/$2 }'") }}
                            </div>
                            <div class="text-slate-500 dark:text-slate-300 text-xs font-normal">
                                {{ exec("free -h | awk '/^Mem:/{printf $2}'") }} /
                                {{ exec("free -m | awk '/^Mem:/{printf(\"%.1fMb\",$2/1)}'") }} |
                                {{ exec("free -m | awk '/^Mem:/{printf(\"%.1fMb\",$3/1)}'") }} used |
                                {{ exec("free -m | awk '/^Mem:/{printf(\"%.1fMb\",$6/1)}'") }} available
                            </div>
                        </div>

                        <div class="space-y-1">
                            <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">
                                Uso de Disco
                            </h4>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ exec("df -h | awk '$6==\"/\"{printf \"%s\", $5}'") }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">
                                Carga de CPU
                            </h4>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ exec("top -bn1 | grep load | awk '{printf \"%.2f%%\", $(NF-2)}'") }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">
                                Load Average (1, 5, 15 min)
                            </h4>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                @if (isset($data['loadAverage']))
                                    {{ $data['loadAverage'][0] }}, {{ $data['loadAverage'][1] }}, {{ $data['loadAverage'][2] }}
                                @else
                                    Sin datos
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- SCRIPT para ApexCharts en el Monitor de Servidor --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Obtenemos los datos de carga promediada (1, 5 y 15 min)
            let loadAverageData = @json($data['loadAverage'] ?? [0,0,0]);

            // Convertimos cada valor a número (por si viene como string)
            let numericData = loadAverageData.map(item => parseFloat(item));

            // Configuración de ApexCharts para un Pie Chart
            let options = {
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: ['1 min', '5 min', '15 min'],
                series: numericData,
                legend: {
                    position: 'bottom'
                }
            };

            let chart = new ApexCharts(document.querySelector("#cpu-load-chart"), options);
            chart.render();
        });
    </script>

    @push('scripts')
        @vite(['resources/js/plugins/jquery-jvectormap-2.0.5.min.js'])
        @vite(['resources/js/plugins/jquery-jvectormap-world-mill-en.js'])
        @vite(['resources/js/custom/chart-active.js'])
        <script type="module">
            $("#world-map").vectorMap({
                map: "world_mill_en",
                normalizeFunction: "polynomial",
                hoverOpacity: 0.7,
                hoverColor: false,
                regionStyle: {
                    initial: { fill: "#8092FF" },
                    hover: { fill: "#4669fa", "fill-opacity": 1 },
                },
                backgroundColor: "transparent",
            });
        </script>
    @endpush
</x-app-layout>
