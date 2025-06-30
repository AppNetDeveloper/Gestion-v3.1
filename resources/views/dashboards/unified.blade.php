{{-- /resources/views/dashboards/unified.blade.php --}}
<x-app-layout>
    {{-- Include ApexCharts library --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    {{-- jQuery and plugins will be loaded via @push below --}}

    <div class="space-y-8">
        {{-- Breadcrumbs (from Dashboard 1) --}}
        <div class="block sm:flex items-center justify-between mb-6">
            {{-- Use the pageTitle passed from the unifiedDashboard controller method --}}
            <x-breadcrumb :pageTitle="$pageTitle"/>
            {{-- Removed filter buttons from Dashboard 2 for simplicity --}}
        </div>

        {{-- Control Horario Section (from Dashboard 1 - Placed Prominently) --}}
        {{-- El contenido interno (#buttons-container o #time-control-error-message) se actualizará con AJAX --}}
        <section class="control-horario card p-6 relative min-h-[100px]"> {{-- Added relative positioning and min-height --}}
            <h2 class="text-xl font-medium text-slate-900 dark:text-white mb-4">Panel de Control de Horario</h2>
            

            {{-- Container for buttons - This div's content will be replaced --}}
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4" id="buttons-container">
                 {{-- Initial content rendered by the server (or load the partial here initially) --}}
                 @include('partials._control_horario_buttons', ['allowedButtons' => $allowedButtons ?? [], 'allowedAddButtons' => $allowedAddButtons ?? false])
            </div>

            {{-- Div to show persistent error messages (initially hidden) --}}
            <div id="time-control-error-message" class="text-center text-danger-700 font-medium mt-4 p-4 border border-danger-500 rounded bg-danger-500 bg-opacity-10" style="display: none;">
                {{-- Error messages will be inserted here by JavaScript --}}
            </div>

            {{-- Loading Overlay (Initially Hidden) --}}
            <div id="loading-overlay" class="loading-overlay" style="display: none;">
                <div class="flex items-center justify-center h-full">
                    <span class="text-slate-600 dark:text-slate-300">Cargando...</span>
                    {{-- You can add a spinner icon here --}}
                    {{-- <iconify-icon icon="line-md:loading-twotone-loop" class="text-2xl ltr:mr-2 rtl:ml-2"></iconify-icon> --}}
                </div>
            </div>
            {{-- Basic styles for loading overlay --}}
            <style>
                .loading-overlay {
                    position: absolute; /* Overlay covers the section */
                    top: 0; left: 0; right: 0; bottom: 0;
                    background-color: rgba(255, 255, 255, 0.8); /* Light overlay */
                    z-index: 10; /* Ensure it's above the buttons */
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: inherit; /* Inherit border radius from parent card */
                }
                .dark .loading-overlay {
                    background-color: rgba(15, 23, 42, 0.8); /* Dark overlay for dark mode */
                }
            </style>
         </section>

        {{-- Dashboard Top Cards (from Dashboard 1 - Using data from unified controller) --}}
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-7">
            {{-- Welcome Card --}}
            <div class="dasboardCard bg-white dark:bg-slate-800 rounded-md px-5 py-4 flex items-center justify-between bg-center bg-cover bg-no-repeat" style="background-image:url('{{ asset('/images/ecommerce-wid-bg.png') }}')">
                <div class="w-full">
                    <h3 class="font-Inter font-normal text-white text-lg">
                        {{ __('Good evening') }}, {{-- Consider dynamic greeting based on time --}}
                    </h3>
                    <h3 class="font-Inter font-medium text-white text-2xl pb-2">
                        {{ auth()->user()?->name ?? 'Invitado' }}
                    </h3>
                    <p class="font-Inter text-base text-white font-normal">
                        {{ __('Welcome to AppNet Developer') }}
                    </p>
                </div>
            </div>
            {{-- Total Revenue Card --}}
            <div class="bg-white dark:bg-slate-800 rounded-md px-5 py-4 flex justify-between items-center"> {{-- Use flex for alignment --}}
                <div class="pl-14 relative">
                    <div class="w-10 h-10 rounded-full bg-sky-100 text-sky-800 text-base flex items-center justify-center absolute left-0 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="ph:shopping-cart-simple-bold"></iconify-icon>
                    </div>
                    <h4 class="font-Inter font-normal text-sm text-textColor dark:text-white pb-1">
                        {{ __('Total revenue') }}
                    </h4>
                    <p class="font-Inter text-xl text-black dark:text-white font-medium">
                        {{ '€' . ($totalSales ?? '0,00') }}
                    </p>
                </div>
                <div class="flex-none w-24"> {{-- Ensure chart area doesn't shrink --}}
                    <div id="EChart"></div> {{-- Target for small revenue chart --}}
                </div>
            </div>
            {{-- Products Sold Card --}}
            <div class="bg-white dark:bg-slate-800 rounded-md px-5 py-4 flex justify-between items-center">
                <div class="pl-14 relative">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-800 text-base flex items-center justify-center absolute left-0 top-1/2 -translate-y-1/2">
                        <iconify-icon icon="teenyicons:box-outline"></iconify-icon>
                    </div>
                    <h4 class="font-Inter font-normal text-sm text-textColor dark:text-white pb-1">
                        {{ __('Products sold') }}
                    </h4>
                    <p class="font-Inter text-xl text-black dark:text-white font-medium">
                        {{-- Use 'productSold' from unified data --}}
                        {{ number_format($data['productSold']['total'] ?? 0) }}
                    </p>
                </div>
                <div class="flex-none w-24">
                    <div id="EChart2"></div> {{-- Target for small product sold chart --}}
                </div>
            </div>
            {{-- Growth Card --}}
            <div class="bg-white dark:bg-slate-800 rounded-md px-5 py-4 flex justify-between items-center">
                <div class="pl-14 relative">
                    <div class="w-10 h-10 rounded-full bg-pink-100 text-pink-800 text-base flex items-center justify-center absolute left-0 top-1/2 -translate-y-1/2"> {{-- Changed color --}}
                        <iconify-icon icon="carbon:growth"></iconify-icon>
                    </div>
                    <h4 class="font-Inter font-normal text-sm text-textColor dark:text-white pb-1">
                        @lang("Growth")
                    </h4>
                    <p class="font-Inter text-xl text-black dark:text-white font-medium">
                         {{-- Use 'growth' from unified data --}}
                        {{ ($data['growth']['preSymbol'] ?? '+') . number_format($data['growth']['total'] ?? 0) . ($data['growth']['postSymbol'] ?? '%') }}
                    </p>
                </div>
                <div class="flex-none w-24">
                    <div id="EChart3"></div> {{-- Target for small growth chart --}}
                </div>
            </div>
        </div>

        {{-- Main Charts Row --}}
        <div class="grid xl:grid-cols-12 grid-cols-1 gap-6">
            {{-- Revenue Report Chart (from Dashboard 1) --}}
            <div class="xl:col-span-8 col-span-12">
                <div class="card p-6 h-full"> {{-- Added h-full --}}
                    <div id="barChartOne"></div> {{-- Target for main revenue bar chart --}}
                </div>
            </div>

            {{-- Statistics Charts (from Dashboard 1) --}}
            <div class="xl:col-span-4 col-span-12">
                 <div class="card h-full"> {{-- Added h-full --}}
                    <h3 class="px-6 py-5 font-Inter font-medium text-black dark:text-white text-xl border-b border-b-slate-100 dark:border-b-slate-900">
                        {{ __('Statistics') }}
                    </h3>
                    <div class="grid md:grid-cols-2 grid-cols-1 gap-4 p-6">
                        @php
                            // Verificar permisos para cada tarjeta
                            $showOrders = auth()->user()->can('quotes index') || auth()->user()->can('invoices index');
                            $showRevenue = auth()->user()->can('invoices index');
                            $showProfit = auth()->user()->can('invoices index');
                            $showGrowth = auth()->user()->can('quotes index') || auth()->user()->can('invoices index');
                        @endphp

                        {{-- Pedidos --}}
                        @if($showOrders)
                        <div class="statisticsChartCard">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]">{{ $stats['sales']['label'] ?? 'Pedidos' }}</h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">{{ $stats['sales']['value'] ?? '0' }}</h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="{{ ($stats['sales']['trend'] ?? '') == 'up' ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ ($stats['sales']['trend'] == 'up' ? '+' : '') . ($stats['sales']['growth'] ?? 0) }}%
                                    </span>
                                    {{ __('From last week.') }}
                                </p>
                            </div>
                            <div id="columnChart" class="mt-1"></div>
                        </div>
                        @endif

                        {{-- Ingresos --}}
                        @if($showRevenue)
                        <div class="statisticsChartCard">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]">{{ $stats['revenue']['label'] ?? 'Ingresos' }}</h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">
                                    {{ ($stats['revenue']['prefix'] ?? '') . ($stats['revenue']['value'] ?? '0,00') }}
                                </h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="{{ ($stats['revenue']['trend'] ?? '') == 'up' ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ ($stats['revenue']['trend'] == 'up' ? '+' : '') . ($stats['revenue']['growth'] ?? 0) }}%
                                    </span>
                                    {{ __('From last week.') }}
                                </p>
                            </div>
                            <div id="revenueChart" class="mt-1"></div>
                        </div>
                        @endif

                        {{-- Beneficio --}}
                        @if($showProfit)
                        <div class="statisticsChartCard">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]">{{ $stats['profit']['label'] ?? 'Beneficio' }}</h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">
                                    {{ ($stats['profit']['prefix'] ?? '') . ($stats['profit']['value'] ?? '0,00') }}
                                </h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="{{ ($stats['profit']['trend'] ?? '') == 'up' ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ ($stats['profit']['trend'] == 'up' ? '+' : '') . ($stats['profit']['growth'] ?? 0) }}%
                                    </span>
                                    {{ __('From last week.') }}
                                </p>
                            </div>
                            <div id="profitChart" class="mt-1"></div>
                        </div>
                        @endif

                        {{-- Crecimiento --}}
                        @if($showGrowth)
                        <div class="statisticsChartCard">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]">Crecimiento</h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">
                                    {{ ($stats['sales']['trend'] == 'up' ? '+' : '') . ($stats['sales']['growth'] ?? 0) }}%
                                </h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="{{ ($stats['sales']['trend'] ?? '') == 'up' ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ ($stats['sales']['trend'] == 'up' ? '+' : '') . ($stats['sales']['growth'] ?? 0) }}%
                                    </span>
                                    {{ __('From last week.') }}
                                </p>
                            </div>
                            <div id="growthChart" class="mt-1"></div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer & Orders Row --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- Total de Ventas --}}
            <div class="xl:col-span-6 col-span-12">
                <div class="card h-full">
                    <div class="card-header flex justify-between items-center">
                        <h4 class="card-title">{{ __('Total Sales') }}</h4>
                    </div>
                    <div class="card-body p-6">
                        @if(isset($data['totalSales']))
                            <div class="flex flex-col items-center justify-center h-full">
                                <div class="text-4xl font-bold text-primary-600 dark:text-primary-400 mb-2">
                                    {{ $data['totalSales'] }} €
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ $data['totalOrders'] ?? 0 }} {{ trans_choice('invoice.invoice', $data['totalOrders'] ?? 0) }}
                                </div>
                                <div class="mt-4 w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-primary-500" style="width: 100%"></div>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">
                                    {{ __('Last 12 months') }}
                                </div>
                            </div>
                        @else
                            <p class="text-slate-500 dark:text-slate-400">{{ __('No sales data available.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pedidos Recientes --}}
            <div class="xl:col-span-6 col-span-12">
                <div class="card h-full">
                    <div class="card-header flex justify-between items-center">
                        <h4 class="card-title">{{ __('Recent Orders') }}</h4>
                    </div>
                    <div class="card-body p-6">
                        @if(isset($data['recentOrders']) && count($data['recentOrders']) > 0)
                            <div class="space-y-4">
                                @foreach($data['recentOrders'] as $order)
                                <div class="flex items-start justify-between p-3 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700">
                                    <div class="flex items-start space-x-3 rtl:space-x-reverse">
                                        <div class="flex-none w-10 h-10 rounded-full flex items-center justify-center {{ $order['type'] === 'invoice' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }} dark:bg-opacity-20">
                                            @if($order['type'] === 'invoice')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                            </svg>
                                            @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 4a1 1 0 00-.94.652L1.879 11H2a1 1 0 01.936 1.351l-1.5 4A1 1 0 002 17h12a1 1 0 00.966-1.259l-1.5-4A1 1 0 0114 11h.121l-2.06-6.348A1 1 0 0011 4H5zm9.5 7a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" clip-rule="evenodd" />
                                            </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                {{ $order['client_name'] }}
                                            </h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                @if($order['type'] === 'invoice')
                                                    {{ __('Invoice') }} #{{ $order['number'] }}
                                                @else
                                                    {{ __('Quote') }} #{{ $order['number'] }}
                                                @endif
                                                <span class="mx-1">•</span>
                                                {{ $order['date'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ number_format($order['amount'], 2) }} €
                                        </p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $order['status']['class'] }} bg-opacity-10">
                                            {{ $order['status']['text'] }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-4 text-center">
                                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                    {{ __('View all orders') }} →
                                </a>
                            </div>
                        @else
                            <p class="text-slate-500 dark:text-slate-400">{{ __('No recent orders found.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Users Table & Activity Row --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- All Company (Users Table) (from Dashboard 2 - data source is $users) --}}
            <div class="lg:col-span-8 col-span-12">
                <div class="card h-full">
                    <header class="card-header flex justify-between items-center">
                        <h4 class="card-title">{{ __('Usuarios') }}</h4>
                        <a href="{{ route('users.index') }}" class="text-sm text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 flex items-center">
                            {{ __('Ver todos') }}
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </header>
                    <div class="card-body p-6">
                        @if(isset($users) && $users->count() > 0)
                        <div class="overflow-x-auto -mx-6">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden">
                                    <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                                        <thead class="bg-slate-50 dark:bg-slate-700">
                                            <tr>
                                                <th scope="col" class="table-th">{{ __('NOMBRE') }}</th>
                                                <th scope="col" class="table-th">{{ __('CORREO') }}</th>
                                                <th scope="col" class="table-th">{{ __('REGISTRADO') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                            @foreach($users as $userItem)
                                            @php
                                                // Obtener la imagen de perfil
                                                $profileImageUrl = $userItem->profile_photo_url ?? null;
                                                if (!$profileImageUrl && class_exists(\Laravolt\Avatar\Facade::class)) {
                                                    try { 
                                                        $profileImageUrl = \Laravolt\Avatar\Facade::create($userItem->name)->toBase64(); 
                                                    } catch (\Exception $e) { 
                                                        $profileImageUrl = null; 
                                                    }
                                                }
                                                if (!$profileImageUrl) {
                                                    $profileImageUrl = 'https://ui-avatars.com/api/?name=' . urlencode($userItem->name) . '&color=7F9CF5&background=EBF4FF';
                                                }
                                            @endphp
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                                <td class="table-td">
                                                    <a href="{{ route('users.show', $userItem->id) }}" class="flex items-center group">
                                                        <div class="flex-none">
                                                            <div class="w-8 h-8 rounded-full ltr:mr-3 rtl:ml-3 overflow-hidden">
                                                                <img class="w-full h-full object-cover"
                                                                     src="{{ $profileImageUrl }}"
                                                                     alt="{{ $userItem->name }}"/>
                                                            </div>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-primary-500 truncate">
                                                                {{ $userItem->name }}
                                                            </h4>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td class="table-td">
                                                    <a href="mailto:{{ $userItem->email }}" class="text-slate-600 dark:text-slate-300 hover:text-primary-500 text-sm">
                                                        {{ $userItem->email }}
                                                    </a>
                                                </td>
                                                <td class="table-td text-sm text-slate-500 dark:text-slate-400">
                                                    {{ $userItem->created_at ? $userItem->created_at->diffForHumans() : 'N/A' }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- Enlaces de paginación --}}
                        @if($users->hasPages())
                        <div class="mt-4">
                            {{ $users->links('vendor.pagination.simple-tailwind') }}
                        </div>
                        @endif
                        @else
                            <div class="text-center py-8">
                                <div class="text-slate-400 dark:text-slate-500 mb-2">
                                    <i class="fas fa-users text-4xl"></i>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400">{{ __('No se encontraron usuarios.') }}</p>
                                @can('create', \App\Models\User::class)
                                <a href="{{ route('users.create') }}" class="inline-flex items-center mt-2 text-sm text-primary-500 hover:text-primary-600">
                                    <i class="fas fa-plus mr-1"></i>
                                    {{ __('Agregar usuario') }}
                                </a>
                                @endcan
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actividad Reciente --}}
            <div class="lg:col-span-4 col-span-12">
                <div class="card h-full">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('Recent Activity') }}</h4>
                    </div>
                    <div class="card-body p-6">
                        @if(isset($data['recentActivities']) && count($data['recentActivities']) > 0)
                            <div class="space-y-4">
                                @foreach($data['recentActivities'] as $activity)
                                <div class="flex items-start pb-4 border-b border-slate-100 dark:border-slate-700 last:border-0 last:pb-0 last:mb-0">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="h-9 w-9 rounded-full flex items-center justify-center {{ $activity['type'] === 'invoice' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }} dark:bg-opacity-20">
                                            @if($activity['type'] === 'invoice')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                            </svg>
                                            @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 4a1 1 0 00-.94.652L1.879 11H2a1 1 0 01.936 1.351l-1.5 4A1 1 0 002 17h12a1 1 0 00.966-1.259l-1.5-4A1 1 0 0114 11h.121l-2.06-6.348A1 1 0 0011 4H5zm9.5 7a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" clip-rule="evenodd" />
                                            </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">
                                            {{ $activity['description'] }}
                                        </p>
                                        <div class="flex items-center justify-between mt-1">
                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $activity['time_ago'] }}
                                            </span>
                                            @if(is_array($activity['status'] ?? null))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $activity['status']['class'] ?? '' }} bg-opacity-10">
                                                    {{ $activity['status']['text'] ?? '' }}
                                                </span>
                                            @elseif(is_string($activity['status'] ?? null))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                                    {{ $activity['status'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">{{ __('No recent activity') }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Activity will appear here as it happens.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección de Estadísticas Principales --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {{-- Tarjeta de Pedidos --}}
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-slate-800 dark:to-slate-900 rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Pedidos</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Total de pedidos registrados</p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900/50 rounded-full p-2">
                            <i class="fas fa-shopping-cart text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-end justify-between mt-6">
                        <div>
                            <div class="text-3xl font-bold text-slate-800 dark:text-white">
                                {{ $stats['sales']['value'] }}
                            </div>
                            <div class="flex items-center mt-2">
                                @if($stats['sales']['trend'] === 'up')
                                    <span class="text-green-500 text-sm font-medium">
                                        <i class="fas fa-arrow-up mr-1"></i> {{ $stats['sales']['growth'] }}%
                                    </span>
                                @else
                                    <span class="text-red-500 text-sm font-medium">
                                        <i class="fas fa-arrow-down mr-1"></i> {{ abs($stats['sales']['growth']) }}%
                                    </span>
                                @endif
                                <span class="text-slate-500 dark:text-slate-400 text-xs ml-2">vs período anterior</span>
                            </div>
                        </div>
                        <div class="h-16 w-24" id="ordersChart">
                            <!-- Gráfico de pedidos se insertará aquí -->
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tarjeta de Ingresos --}}
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-slate-800 dark:to-slate-900 rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Ingresos</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Total facturado</p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/50 rounded-full p-2">
                            <i class="fas fa-euro-sign text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-end justify-between mt-6">
                        <div>
                            <div class="text-3xl font-bold text-slate-800 dark:text-white">
                                {{ $stats['revenue']['prefix'] }} {{ $stats['revenue']['value'] }}
                            </div>
                            <div class="flex items-center mt-2">
                                @if($stats['revenue']['trend'] === 'up')
                                    <span class="text-green-500 text-sm font-medium">
                                        <i class="fas fa-arrow-up mr-1"></i> {{ $stats['revenue']['growth'] }}%
                                    </span>
                                @else
                                    <span class="text-red-500 text-sm font-medium">
                                        <i class="fas fa-arrow-down mr-1"></i> {{ abs($stats['revenue']['growth']) }}%
                                    </span>
                                @endif
                                <span class="text-slate-500 dark:text-slate-400 text-xs ml-2">vs período anterior</span>
                            </div>
                        </div>
                        <div class="h-16 w-24" id="revenueChart">
                            <!-- Gráfico de ingresos se insertará aquí -->
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tarjeta de Beneficio --}}
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-slate-800 dark:to-slate-900 rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Beneficio</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Beneficio estimado</p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900/50 rounded-full p-2">
                            <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-end justify-between mt-6">
                        <div>
                            <div class="text-3xl font-bold text-slate-800 dark:text-white">
                                {{ $stats['profit']['prefix'] }} {{ $stats['profit']['value'] }}
                            </div>
                            <div class="flex items-center mt-2">
                                @if($stats['profit']['trend'] === 'up')
                                    <span class="text-green-500 text-sm font-medium">
                                        <i class="fas fa-arrow-up mr-1"></i> {{ $stats['profit']['growth'] }}%
                                    </span>
                                @else
                                    <span class="text-red-500 text-sm font-medium">
                                        <i class="fas fa-arrow-down mr-1"></i> {{ abs($stats['profit']['growth']) }}%
                                    </span>
                                @endif
                                <span class="text-slate-500 dark:text-slate-400 text-xs ml-2">vs período anterior</span>
                            </div>
                        </div>
                        <div class="h-16 w-24" id="profitChart">
                            <!-- Gráfico de beneficio se insertará aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección de Estadísticas Secundarias --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {{-- Tarjeta de Tareas --}}
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Tareas</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Estado actual de tus tareas</p>
                        </div>
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-full p-2">
                            <i class="fas fa-tasks text-slate-600 dark:text-slate-300 text-xl"></i>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center mt-6">
                        @foreach($analyticChartData['taskStats']['data'] as $index => $count)
                            <div class="bg-white dark:bg-slate-800/50 rounded-lg p-3 shadow-sm">
                                <div class="text-2xl font-bold mb-1" style="color: {{ $analyticChartData['taskStats']['colors'][$index] }}">
                                    {{ $count }}
                                </div>
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                    {{ $analyticChartData['taskStats']['labels'][$index] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800/30 p-4 border-t border-slate-100 dark:border-slate-700/50">
                    <a href="{{ route('tasks.my') }}" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white flex items-center justify-center">
                        Ver todas las tareas
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            {{-- Tarjeta de Proyectos --}}
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Proyectos</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Resumen de proyectos</p>
                        </div>
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-full p-2">
                            <i class="fas fa-project-diagram text-slate-600 dark:text-slate-300 text-xl"></i>
                        </div>
                    </div>
                    <div class="space-y-3 mt-4">
                        <div class="flex items-center justify-between bg-white dark:bg-slate-800/50 rounded-lg p-3 shadow-sm">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-300">Total</span>
                            </div>
                            <span class="font-medium text-slate-800 dark:text-white">{{ $analyticChartData['projectStats']['total'] }}</span>
                        </div>
                        <div class="flex items-center justify-between bg-white dark:bg-slate-800/50 rounded-lg p-3 shadow-sm">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-300">En Progreso</span>
                            </div>
                            <span class="font-medium text-yellow-600 dark:text-yellow-400">{{ $analyticChartData['projectStats']['active'] }}</span>
                        </div>
                        <div class="flex items-center justify-between bg-white dark:bg-slate-800/50 rounded-lg p-3 shadow-sm">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-300">Completados</span>
                            </div>
                            <span class="font-medium text-green-600 dark:text-green-400">{{ $analyticChartData['projectStats']['completed'] }}</span>
                        </div>
                        <div class="flex items-center justify-between bg-white dark:bg-slate-800/50 rounded-lg p-3 shadow-sm">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-300">Atrasados</span>
                            </div>
                            <span class="font-medium text-red-600 dark:text-red-400">{{ $analyticChartData['projectStats']['overdue'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800/30 p-4 border-t border-slate-100 dark:border-slate-700/50">
                    <a href="{{ route('projects.index') }}" class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 flex items-center justify-center">
                        Ver todos los proyectos
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            {{-- Tarjeta de Ventas --}}
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-slate-800 dark:to-slate-900 rounded-xl shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Ventas</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Resumen de facturación</p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/50 rounded-full p-2">
                            <i class="fas fa-file-invoice-dollar text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex flex-col items-center justify-center py-4">
                        <div class="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">
                            {{ $totalInvoices ?? 0 }}
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                            {{ trans_choice('factura total|facturas totales', $totalInvoices ?? 0) }}
                        </div>
                        <div class="w-full bg-white dark:bg-slate-800/50 rounded-lg p-3 shadow-sm">
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="text-slate-600 dark:text-slate-300">Este mes</span>
                                <span class="font-medium text-green-600 dark:text-green-400">+12.5%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800/30 p-4 border-t border-slate-100 dark:border-slate-700/50">
                    <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 flex items-center justify-center">
                        Ver informe de ventas
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Tareas y Monitor de Servidor --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- Resumen de Tareas Pendientes --}}
            <div class="lg:col-span-8 col-span-12">
                <div class="card h-full">
                    <div class="card-header flex justify-between items-center">
                        <h4 class="card-title">Resumen de Tareas</h4>
                        <a href="{{ route('tasks.my') }}" class="btn-sm bg-slate-50 hover:bg-slate-100 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200">
                            Ver Mis Tareas
                        </a>
                    </div>
                    <div class="card-body p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {{-- Tareas Pendientes --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-primary-500">
                                    {{ $taskCounts['pending'] ?? 0 }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Pendientes
                                </div>
                                <div class="h-1 bg-slate-200 dark:bg-slate-700 mt-2 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500" style="width: {{ min(($taskCounts['pending'] ?? 0) / max(($taskCounts['total'] ?? 1), 1) * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Tareas en Progreso --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-warning-500">
                                    {{ $taskCounts['in_progress'] ?? 0 }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-300 mt-1">
                                    En Progreso
                                </div>
                                <div class="h-1 bg-slate-200 dark:bg-slate-700 mt-2 rounded-full overflow-hidden">
                                    <div class="h-full bg-warning-500" style="width: {{ min(($taskCounts['in_progress'] ?? 0) / max(($taskCounts['total'] ?? 1), 1) * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Tareas Completadas --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-success-500">
                                    {{ $taskCounts['completed'] ?? 0 }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-300 mt-1">
                                    Completadas
                                </div>
                                <div class="h-1 bg-slate-200 dark:bg-slate-700 mt-2 rounded-full overflow-hidden">
                                    <div class="h-full bg-success-500" style="width: {{ min(($taskCounts['completed'] ?? 0) / max(($taskCounts['total'] ?? 1), 1) * 100, 100) }}%"></div>
                                </div>
                            </div>
                            
                            {{-- Tareas Totales --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-info-500">
                                    {{ $taskCounts['total'] ?? 0 }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-300 mt-1">
                                    Total Tareas
                                </div>
                                <div class="h-1 bg-slate-200 dark:bg-slate-700 mt-2 rounded-full overflow-hidden">
                                    <div class="h-full bg-info-500" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Lista de Tareas Recientes --}}
                        <div class="mt-6">
                            <h5 class="text-sm font-medium text-slate-600 dark:text-slate-200 mb-3">Tareas Recientes</h5>
                            <div class="space-y-2">
                                @if(isset($recentTasks) && count($recentTasks) > 0)
                                    @foreach($recentTasks as $task)
                                        <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                            <div class="flex items-center space-x-2 truncate">
                                                <div class="w-2 h-2 flex-shrink-0 rounded-full {{ 
                                                    $task['status'] === 'completed' ? 'bg-success-500' : 
                                                    ($task['status'] === 'in_progress' ? 'bg-warning-500' : 'bg-primary-500') 
                                                }}"></div>
                                                <div class="truncate">
                                                    <h6 class="text-xs font-medium text-slate-700 dark:text-slate-200 truncate">{{ $task['title'] }}</h6>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $task['project'] ?? 'Sin proyecto' }}</p>
                                                </div>
                                            </div>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap ml-2">
                                                {{ $task['due_date'] ?? 'Sin fecha' }}
                                            </span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-slate-500 dark:text-slate-400">No hay tareas recientes</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Server Monitor --}}
            <div class="lg:col-span-4 col-span-12">
                <div class="card h-full flex flex-col">
                    <div class="card-header">
                        <h4 class="card-title">Monitor de Servidor</h4>
                    </div>
                    <div class="card-body p-6 flex-1 flex flex-col">
                        {{-- ApexCharts Pie chart for Load Average --}}
                        <div class="flex-1 min-h-[200px]">
                            <div id="cpu-load-chart" class="w-full h-full"></div>
                        </div>

                        {{-- Server stats using direct shell_exec --}}
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            {{-- Memoria RAM --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-slate-600 dark:text-slate-200 text-xs font-medium">Memoria RAM</h4>
                                    <span class="text-xs px-2 py-1 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 rounded-full">
                                        {{ trim(shell_exec("free -m | awk 'NR==2{printf \"%.1f%%\", \$3*100/\$2 }'")) ?: 'N/A' }}
                                    </span>
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span>Usado: {{ trim(shell_exec("free -h | awk '/^Mem:/{print \$3}'")) ?: 'N/A' }}</span>
                                        <span>Total: {{ trim(shell_exec("free -h | awk '/^Mem:/{print \$2}'")) ?: 'N/A' }}</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                        @php
                                            $ram_percent = trim(shell_exec("free -m | awk 'NR==2{printf \"%.0f\", \$3*100/\$2 }'")) ?: 0;
                                        @endphp
                                        <div class="bg-primary-500 h-2 rounded-full" style="width: {{ $ram_percent }}%"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Uso de Disco --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-slate-600 dark:text-slate-200 text-xs font-medium">Disco (/)</h4>
                                    <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">
                                        {{ trim(shell_exec("df -h | awk '\$NF==\"/\"{printf \"%s\", \$5}'") ?: 'N/A') }}
                                    </span>
                                </div>
                                <div class="text-sm text-slate-500 dark:text-slate-400">
                                    @php
                                        $disk_used = trim(shell_exec("df -h | awk '\$NF==\"/\"{print \$3}'") ?: '0');
                                        $disk_total = trim(shell_exec("df -h | awk '\$NF==\"/\"{print \$2}'") ?: '0');
                                        $disk_percent = trim(shell_exec("df -h | awk '\$NF==\"/\"{gsub(\"%\",\"\",\$5); print \$5}'") ?: '0');
                                    @endphp
                                    <div class="flex justify-between text-xs mb-1">
                                        <span>Usado: {{ $disk_used }}</span>
                                        <span>Total: {{ $disk_total }}</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $disk_percent }}%"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Carga de CPU --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4">
                                <h4 class="text-slate-600 dark:text-slate-200 text-xs font-medium mb-2">Uso de CPU</h4>
                                <div class="flex items-center">
                                    @php
                                        $cpu_usage = trim(shell_exec("top -bn1 | grep '%Cpu(s):' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{printf \"%.0f\", 100 - \$1}'") ?: '0');
                                    @endphp
                                    <div class="flex-1 mr-3">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span>Uso actual</span>
                                            <span>{{ $cpu_usage }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                            <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $cpu_usage }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Load Average --}}
                            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4">
                                <h4 class="text-slate-600 dark:text-slate-200 text-xs font-medium mb-2">Carga del Sistema</h4>
                                <div class="space-y-2">
                                    @php
                                        $load_avg = $data['loadAverage'] ?? [0, 0, 0];
                                        $load_avg_display = array_map(function($value) {
                                            return number_format((float)$value, 2);
                                        }, $load_avg);
                                    @endphp
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-500 dark:text-slate-400">1 min</span>
                                        <span class="font-medium {{ $load_avg[0] > 1 ? 'text-red-500' : 'text-green-500' }}">
                                            {{ $load_avg_display[0] ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-500 dark:text-slate-400">5 min</span>
                                        <span class="font-medium {{ $load_avg[1] > 1 ? 'text-orange-500' : 'text-green-500' }}">
                                            {{ $load_avg_display[1] ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-slate-500 dark:text-slate-400">15 min</span>
                                        <span class="font-medium {{ $load_avg[2] > 1 ? 'text-yellow-500' : 'text-green-500' }}">
                                            {{ $load_avg_display[2] ?? 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> {{-- End main space-y-8 container --}}


    {{-- Combined JavaScript --}}
    @push('scripts')
        {{-- Load Chart.js --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        {{-- Load jQuery from CDN --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        {{-- Load jQuery Mousewheel Plugin (Required by jVectorMap for zoom) - CORRECTED SRI HASH --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js" integrity="sha512-rCjfoab9CVKOH/w/T6GbBxnAH5Azhy4+q1EXW5XEURefHbIkRbQ++ZR+GBClo3/d3q583X/gO4FKmOFuhkKrdA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

        {{-- jVectorMap dependencies (from Dashboard 2) --}}
        {{-- Ensure these paths are correct relative to your public/build directory if using Vite build --}}
        {{-- Or adjust vite.config.js input array --}}
        @vite(['resources/js/plugins/jquery-jvectormap-2.0.5.min.js'])
        @vite(['resources/js/plugins/jquery-jvectormap-world-mill-en.js'])

        {{-- ** START: Javascript Block with Persistent Error Display ** --}}
        <script>
            // Control Horario Functionality
            // Asegurarse de que jQuery esté disponible antes de ejecutar el código
            function inicializarControlHorario() {
                console.log('Intentando inicializar control horario...');
                try {
                    // Check if jQuery is available
                    if (typeof jQuery !== 'undefined') {
                        console.log('jQuery detectado correctamente, versión:', jQuery.fn.jquery);
                        // Rest of your code here
                    } else {
                        console.error('jQuery no detectado. No se puede inicializar control horario.');
                    }
                } catch (error) {
                    console.error('Error inicializando control horario:', error);
                }
            }

            // Wait for the DOM to be fully loaded
            document.addEventListener('DOMContentLoaded', function() {
                inicializarControlHorario();
            });
        </script>

        {{-- ** END: Javascript Block with Persistent Error Display ** --}}
                const loadAverageDataRaw = {{ Illuminate\Support\Js::from($data['loadAverage'] ?? null) }};

                // Flag to prevent multiple simultaneous updates
                let isUpdatingTimeControl = false;
                let timeControlIntervalId = null; // To store the interval ID

                // --- References to UI elements ---
                const buttonsContainer = $("#buttons-container"); // jQuery object
                const loadingOverlay = $("#loading-overlay"); // jQuery object
                const errorMessageDiv = $("#time-control-error-message"); // jQuery object

                // --- Geolocation Tracking using watchPosition ---
                let latestLat = null;
                let latestLong = null;
                let watchId = null;
                let initialLocationObtained = false; // Flag to know if we got at least one location
                let geolocationSupported = ('geolocation' in navigator);


                // Function to show persistent error messages and hide buttons
                function showPersistentError(message) {
                    console.error("Persistent Error:", message); // Log error for debugging
                    if(errorMessageDiv.length) {
                        errorMessageDiv.text(message).show(); // Set text and show error div
                    }
                    if(buttonsContainer.length) {
                        buttonsContainer.hide(); // Hide buttons
                    }
                     if(loadingOverlay.length) {
                        loadingOverlay.hide(); // Ensure loading is hidden
                    }
                }

                 // Function to hide persistent error messages and show buttons
                function hidePersistentError() {
                    if(errorMessageDiv.length) {
                        errorMessageDiv.hide().text(''); // Hide error div and clear text
                    }
                     if(buttonsContainer.length) {
                         // Only show buttons if not currently updating (to avoid race conditions)
                         if (!isUpdatingTimeControl) {
                            buttonsContainer.css('display', 'grid'); // Show buttons using grid
                         }
                    }
                }


                if (geolocationSupported) {
                    console.log('Iniciando seguimiento de ubicación...'); // Log location start
                    watchId = navigator.geolocation.watchPosition(
                        (position) => { // Success callback for watchPosition
                            latestLat = position.coords.latitude;
                            latestLong = position.coords.longitude;
                            if (!initialLocationObtained) {
                                initialLocationObtained = true;
                                console.log('Ubicación inicial obtenida:', latestLat, latestLong);
                                hidePersistentError(); // Hide any previous error message once location is obtained
                            } else {
                               console.log('Ubicación actualizada:', latestLat, latestLong);
                            }
                        },
                        (error) => { // Error callback for watchPosition
                            console.error("Error vigilando posición:", error.message, `(Code: ${error.code})`);
                            latestLat = null; // Invalidate location on error
                            latestLong = null;
                            initialLocationObtained = false; // Reset flag

                            // Show specific message for location denied/unavailable
                            if (error.code === error.PERMISSION_DENIED || error.code === error.POSITION_UNAVAILABLE) {
                                showPersistentError("La ubicación está desactivada o denegada. Por favor, actívala para poder fichar.");
                            } else {
                                // Show generic error for other geolocation issues
                                showPersistentError(`Error obteniendo ubicación: ${error.message}`);
                            }
                        },
                        { // Options for watchPosition
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                } else {
                    console.warn("Geolocalización no soportada.");
                    showPersistentError('Geolocalización no soportada por este navegador. No se puede fichar.');
                }
                // --- End Geolocation Tracking ---


                // --- Chart Initializations (Using Vanilla JS) ---
                // Restore FULL chart configurations here
                try {
                    // Revenue Report Chart (Main Bar Chart)
                    const barChartOneEl = $q("#barChartOne");
                    if (barChartOneEl && dashboardData.revenueReport) {
                        let revenueReportChartConfig = {
                            series: [
                                { name: dashboardData.revenueReport.revenue?.title ?? 'Revenue', data: dashboardData.revenueReport.revenue?.data ?? [] },
                                { name: dashboardData.revenueReport.netProfit?.title ?? 'Net Profit', data: dashboardData.revenueReport.netProfit?.data ?? [] },
                                { name: dashboardData.revenueReport.cashFlow?.title ?? 'Cash Flow', data: dashboardData.revenueReport.cashFlow?.data ?? [] },
                            ],
                            chart: { type: "bar", height: 350, width: "100%", toolbar: { show: false }, background: 'transparent' },
                            plotOptions: { bar: { horizontal: false, columnWidth: "45%", borderRadius: 4 } },
                            legend: { show: true, position: "top", horizontalAlign: "right", fontSize: "12px", fontFamily: "Inter", offsetY: 0, markers: { width: 8, height: 8, radius: '50%' }, itemMargin: { horizontal: 10, vertical: 5 }, labels: { colors: document.body.classList.contains('dark') ? '#cbd5e1' : '#475569' } },
                            title: { text: "Revenue Report", align: "left", style: { fontSize: "18px", fontWeight: "500", fontFamily: "Inter", color: document.body.classList.contains('dark') ? '#cbd5e1' : '#1e293b' } },
                            dataLabels: { enabled: false },
                            stroke: { show: true, width: 2, colors: ["transparent"] },
                            yaxis: { labels: { style: { colors: document.body.classList.contains('dark') ? '#94a3b8' : '#64748b', fontFamily: "Inter" } } },
                            xaxis: { categories: dashboardData.revenueReport.month ?? [], labels: { style: { colors: document.body.classList.contains('dark') ? '#94a3b8' : '#64748b', fontFamily: "Inter" } }, axisBorder: { show: false }, axisTicks: { show: false } },
                            fill: { opacity: 1 },
                            tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', y: { formatter: (val) => `$ ${val}k` } },
                            colors: ["#4669FA", "#0CE7FA", "#FA916B"],
                            grid: { show: true, borderColor: document.body.classList.contains('dark') ? '#334155' : '#e2e8f0', strokeDashArray: 5, position: "back" },
                            responsive: [ { breakpoint: 600, options: { legend: { position: "bottom", offsetY: 10 }, plotOptions: { bar: { columnWidth: "80%" } } } } ],
                        };
                        new ApexCharts(barChartOneEl, revenueReportChartConfig).render();
                    } else { console.warn("Element #barChartOne or dashboardData.revenueReport not found."); }

                    // Small Top Card Charts Helper
                    const createSmallAreaChart = (selector, seriesData, categories, color) => {
                        const element = $q(selector);
                        const cleanData = Array.isArray(seriesData) ? seriesData.map(v => Number(v) || 0) : [];
                        const cleanCategories = Array.isArray(categories) ? categories : [];
                        if (element && cleanData.length > 0 && cleanCategories.length === cleanData.length) {
                            let config = {
                                chart: { type: "area", height: "48", toolbar: { show: false }, sparkline: { enabled: true }, background: 'transparent' },
                                dataLabels: { enabled: false }, stroke: { curve: "smooth", width: 2 }, colors: [color],
                                tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', x: { show: false }, y: { title: { formatter: () => '' }, formatter: (val) => val } },
                                grid: { show: false, padding: { left: 0, right: 0 } }, yaxis: { show: false },
                                fill: { type: "gradient", gradient: { opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 100] } },
                                legend: { show: false },
                                xaxis: { categories: cleanCategories, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                                series: [{ data: cleanData }],
                            };
                            new ApexCharts(element, config).render();
                        } else { console.warn(`Small area chart element or valid data/categories not found for: ${selector}`); }
                    };
                    // Initialize Small Top Card Charts
                    createSmallAreaChart("#EChart", dashboardData.yearlyRevenue?.revenue, dashboardData.yearlyRevenue?.year, "#00EBFF");
                    createSmallAreaChart("#EChart2", dashboardData.productSold?.quantity, dashboardData.productSold?.year, "#5743BE");
                    createSmallAreaChart("#EChart3", dashboardData.growth?.perYearRate, dashboardData.growth?.year, "#fd5693");

                    // --- Statistics Charts ---
                    const columnChartEl = $q("#columnChart");
                    if (columnChartEl && dashboardData.lastWeekOrder) {
                        const orderData = Array.isArray(dashboardData.lastWeekOrder.data) ? dashboardData.lastWeekOrder.data.map(v => Number(v) || 0) : [];
                        if(orderData.length > 0) {
                             let lastWeekOrderChartConfig = {
                                series: [{ name: dashboardData.lastWeekOrder.name ?? 'Orders', data: orderData }],
                                chart: { type: "bar", height: 50, toolbar: { show: false }, sparkline: { enabled: true }, background: 'transparent' },
                                plotOptions: { bar: { columnWidth: "60%", borderRadius: 2 } }, legend: { show: false }, dataLabels: { enabled: false },
                                stroke: { width: 0 }, fill: { opacity: 1 },
                                tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', x: { show: false }, y: { title: { formatter: () => '' }, formatter: (val) => `${val}k` } },
                                yaxis: { show: false }, xaxis: { show: false, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                                colors: ["#EDB899"], grid: { show: false },
                            };
                            new ApexCharts(columnChartEl, lastWeekOrderChartConfig).render();
                         } else { console.warn("No valid data for #columnChart"); }
                    } else { console.warn("Element #columnChart or dashboardData.lastWeekOrder not found."); }

                    const lineChartEl = $q("#lineChart");
                    if (lineChartEl && dashboardData.lastWeekProfit) {
                         const profitData = Array.isArray(dashboardData.lastWeekProfit.data) ? dashboardData.lastWeekProfit.data.map(v => Number(v) || 0) : [];
                         if (profitData.length > 0) {
                            let lastWeekProfitChartConfig = {
                                series: [{ name: dashboardData.lastWeekProfit.name ?? 'Profit', data: profitData }],
                                chart: { height: 50, toolbar: { show: false }, sparkline: { enabled: true }, background: 'transparent' },
                                stroke: { width: [2], curve: "straight" }, dataLabels: { enabled: false }, markers: { size: 0 },
                                yaxis: { show: false }, xaxis: { show: false, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                                grid: { show: false }, colors: ["#4669FA"],
                                tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', x: { show: false }, y: { title: { formatter: () => '' }, formatter: (val) => `${val}k` } },
                            };
                            new ApexCharts(lineChartEl, lastWeekProfitChartConfig).render();
                         } else { console.warn("No valid data for #lineChart"); }
                    } else { console.warn("Element #lineChart or dashboardData.lastWeekProfit not found."); }

                    const donutChartEl = $q("#donutChart");
                    if (donutChartEl && dashboardData.lastWeekOverview) {
                        const overviewData = Array.isArray(dashboardData.lastWeekOverview.data) ? dashboardData.lastWeekOverview.data.map(v => Number(v) || 0) : [];
                        if (overviewData.length > 0 && overviewData.some(v => v > 0)) {
                            let lastWeekOverviewChartConfig = {
                                series: overviewData,
                                chart: { type: 'donut', height: 150, width: '100%', background: 'transparent' },
                                labels: dashboardData.lastWeekOverview.labels ?? [], dataLabels: { enabled: false }, colors: ["#0CE7FA", "#FA916B"],
                                legend: { show: true, position: "bottom", fontSize: "12px", fontFamily: "Inter", fontWeight: 400, offsetY: 5, itemMargin: { horizontal: 5 }, markers: { width: 8, height: 8, radius: '50%' }, labels: { colors: document.body.classList.contains('dark') ? '#cbd5e1' : '#475569' } },
                                plotOptions: { pie: { donut: { size: "65%" } } },
                                tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', fillSeriesColor: false },
                                responsive: [ { breakpoint: 480, options: { chart: { height: 130 }, legend: { position: "bottom" } } } ],
                            };
                            new ApexCharts(donutChartEl, lastWeekOverviewChartConfig).render();
                        } else { console.warn("No valid data for #donutChart"); }
                    } else { console.warn("Element #donutChart or dashboardData.lastWeekOverview not found."); }

                    // Overview Radial Bar Chart
                     const radialBarEl = $q("#radial-bar");
                    if (radialBarEl && productGrowthOverviewData) {
                         const radialData = Array.isArray(productGrowthOverviewData.data) ? productGrowthOverviewData.data.map(v => Number(v) || 0) : [];
                         if (radialData.length > 0) {
                            let radialBarConfig = {
                                series: radialData, chart: { height: 350, type: 'radialBar', background: 'transparent' },
                                plotOptions: { radialBar: { offsetY: 0, startAngle: 0, endAngle: 270, hollow: { margin: 5, size: '30%', background: 'transparent' }, dataLabels: { name: { show: false }, value: { show: false } } } },
                                colors: ['#1ab7ea', '#0084ff', '#39539E', '#0077B5'], labels: productGrowthOverviewData.productNames ?? [],
                                legend: { show: true, floating: true, fontSize: '12px', position: 'left', offsetX: 50, offsetY: 15, labels: { useSeriesColors: true, colors: document.body.classList.contains('dark') ? '#cbd5e1' : '#475569' }, markers: { size: 0 }, formatter: (seriesName, opts) => `${seriesName}: ${opts.w.globals.series[opts.seriesIndex]}`, itemMargin: { vertical: 3 } },
                                tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', y: { formatter: (val) => val } },
                                responsive: [ { breakpoint: 480, options: { legend: { show: false } } } ]
                            };
                            new ApexCharts(radialBarEl, radialBarConfig).render();
                         } else { console.warn("No valid data for #radial-bar"); }
                    } else { console.warn("Element #radial-bar or productGrowthOverviewData not found."); }

                    // Server Monitor Load Average Pie Chart
                    const cpuLoadChartEl = $q("#cpu-load-chart");
                    if (cpuLoadChartEl && loadAverageDataRaw) {
                        let loadAverageData = Array.isArray(loadAverageDataRaw) ? loadAverageDataRaw.map(item => parseFloat(item) || 0) : [0, 0, 0];
                        if (loadAverageData.some(v => v >= 0) && loadAverageData.length === 3) { // Allow zeros
                            let cpuLoadConfig = {
                                chart: { type: 'pie', height: 200, background: 'transparent' },
                                labels: ['Load 1 min', 'Load 5 min', 'Load 15 min'], series: loadAverageData, colors: ["#50C878", "#FDB813", "#ED544A"],
                                legend: { position: 'bottom', fontSize: '12px', fontFamily: 'Inter', itemMargin: { horizontal: 5 }, markers: { width: 8, height: 8, radius: '50%' }, labels: { colors: document.body.classList.contains('dark') ? '#cbd5e1' : '#475569' } },
                                tooltip: { theme: document.body.classList.contains('dark') ? 'dark' : 'light', y: { formatter: (val) => val.toFixed(2) } },
                                dataLabels: { enabled: false }
                            };
                            new ApexCharts(cpuLoadChartEl, cpuLoadConfig).render();
                        } else { console.warn("Invalid data for #cpu-load-chart"); }
                    } else { console.warn("Element #cpu-load-chart or loadAverageDataRaw not found."); }

                } catch (chartError) {
                    console.error("Error initializing ApexCharts:", chartError);
                }


                // --- jVectorMap Initialization (Using jQuery, with setTimeout) ---
                try {
                    setTimeout(() => {
                        if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.vectorMap === 'function' && $q("#world-map")) {
                             window.jQuery("#world-map").vectorMap({
                                map: "world_mill_en", normalizeFunction: "polynomial", hoverOpacity: 0.7, hoverColor: false,
                                regionStyle: { initial: { fill: "#8092FF" }, hover: { fill: "#4669fa", "fill-opacity": 1 } },
                                backgroundColor: "transparent",
                            });
                        } else {
                             console.warn("jQuery, jVectorMap plugin, or #world-map element not found (deferred check).");
                        }
                    }, 150);
                } catch (mapError) {
                     console.error("Error initializing jVectorMap:", mapError);
                }


                // --- Control Horario Section ---
                try {
                    // Ensure jQuery is loaded
                    if (typeof window.jQuery !== 'undefined') {
                        const $ = window.jQuery; // Use jQuery alias
                        
                        // Referencias a los elementos DOM
                        const buttonsContainer = $('#buttons-container');
                        const errorMessageDiv = $('#time-control-error-message');
                        const loadingOverlay = $('#loading-overlay');
                        
                        // Verificar que los elementos existen en el DOM
                        console.log('Inicializando Control Horario - Estado de los elementos DOM:', {
                            buttonsContainer: buttonsContainer.length > 0 ? 'Encontrado' : 'No encontrado',
                            errorMessageDiv: errorMessageDiv.length > 0 ? 'Encontrado' : 'No encontrado',
                            loadingOverlay: loadingOverlay.length > 0 ? 'Encontrado' : 'No encontrado'
                        });
                        
                        // Variables de estado
                        let isUpdatingTimeControl = false;
                        let geolocationSupported = 'geolocation' in navigator;
                        let latestLat = null;
                        let latestLong = null;
                        let initialLocationObtained = false;
                        let watchId = null;
                        
                        // Función para mostrar errores (sin ocultar botones)
                        function showPersistentError(message) {
                            console.error('Error en control horario:', message);
                            errorMessageDiv.text(message).show();
                            // NO ocultar los botones - solo mostrar el mensaje de error
                            console.log('Mostrando error pero manteniendo botones visibles');
                        }
                        
                        // Función para mostrar errores críticos (oculta botones)
                        function showCriticalError(message) {
                            console.error('Error crítico en control horario:', message);
                            errorMessageDiv.text(message).show();
                            buttonsContainer.hide();
                        }
                        
                        // Función para ocultar errores
                        function hidePersistentError() {
                            errorMessageDiv.hide().text('');
                            buttonsContainer.show();
                            console.log('Error ocultado, botones mostrados');
                        }
                        
                        // Inicializar la geolocalización inmediatamente
                        if (geolocationSupported) {
                            console.log('Inicializando servicio de geolocalización...');
                            // Intentar obtener la ubicación inicial
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    latestLat = position.coords.latitude;
                                    latestLong = position.coords.longitude;
                                    initialLocationObtained = true;
                                    console.log('Ubicación inicial obtenida correctamente:', latestLat, latestLong);
                                    
                                    // Si hay un error visible, ocultarlo ahora que tenemos ubicación
                                    if (errorMessageDiv.is(':visible')) {
                                        hidePersistentError();
                                    }
                                    
                                    // Asegurarse de que los botones estén visibles
                                    buttonsContainer.show();
                                },
                                (error) => {
                                    console.error('Error obteniendo ubicación inicial:', error.message, `(Code: ${error.code})`);
                                    
                                    // Mostrar mensaje de error apropiado
                                    if (error.code === 1) { // PERMISSION_DENIED
                                        showPersistentError('Permiso de ubicación denegado. Por favor, activa la ubicación para usar el control horario.');
                                    } else if (error.code === 2) { // POSITION_UNAVAILABLE
                                        showPersistentError('Ubicación no disponible. Verifica que tu dispositivo tenga GPS o conexión de red.');
                                    } else if (error.code === 3) { // TIMEOUT
                                        showPersistentError('Tiempo de espera agotado al obtener la ubicación. Inténtalo de nuevo.');
                                    } else {
                                        showPersistentError(`Error de geolocalización: ${error.message}`);
                                    }
                                },
                                {
                                    enableHighAccuracy: true,
                                    timeout: 10000,
                                    maximumAge: 0
                                }
                            );
                            
                            // Configurar watchPosition para actualizar la ubicación continuamente
                            watchId = navigator.geolocation.watchPosition(
                                (position) => {
                                    latestLat = position.coords.latitude;
                                    latestLong = position.coords.longitude;
                                    
                                    if (!initialLocationObtained) {
                                        initialLocationObtained = true;
                                        console.log('Ubicación inicial obtenida (vía watchPosition):', latestLat, latestLong);
                                        
                                        // Si hay un error visible, ocultarlo ahora que tenemos ubicación
                                        if (errorMessageDiv.is(':visible')) {
                                            hidePersistentError();
                                        }
                                    } else {
                                        console.log('Ubicación actualizada:', latestLat, latestLong);
                                    }
                                },
                                (error) => {
                                    console.error('Error en watchPosition:', error.message, `(Code: ${error.code})`);
                                    
                                    // Solo mostrar error si no tenemos ubicación inicial
                                    if (!initialLocationObtained) {
                                        if (error.code === 1) { // PERMISSION_DENIED
                                            showPersistentError('Permiso de ubicación denegado. Por favor, activa la ubicación para usar el control horario.');
                                        } else if (error.code === 2) { // POSITION_UNAVAILABLE
                                            showPersistentError('Ubicación no disponible. Verifica que tu dispositivo tenga GPS o conexión de red.');
                                        } else {
                                            showPersistentError(`Error de geolocalización: ${error.message}`);
                                        }
                                    }
                                },
                                {
                                    enableHighAccuracy: true,
                                    timeout: 30000,
                                    maximumAge: 60000
                                }
                            );
                        } else {
                            console.error('Geolocalización no soportada en este navegador');
                            showCriticalError('Tu navegador no soporta geolocalización. No podrás usar el control horario.');
                        }

                        // Function to refresh the time control section via AJAX GET
                        function refreshTimeControlSection(options = {}) {
                            const { isInterval = false } = options;

                            // Prevent overlapping interval calls ONLY
                            if (isInterval && isUpdatingTimeControl) {
                                console.log('Previous time control update still in progress, skipping interval refresh.');
                                return;
                            }

                            if (!buttonsContainer.length) {
                                console.error('El contenedor de botones no existe en el DOM');
                                return;
                            }

                            // Set flag *before* the request
                            isUpdatingTimeControl = true;
                            console.log('Refrescando sección Control Horario...', { isInterval }); // Log refresh start

                            // Don't show overlay for automatic interval refresh
                            if (!isInterval) {
                                loadingOverlay.show();
                                buttonsContainer.hide();
                            }

                            $.ajax({
                                url: '/get-time-control-section', // GET Route
                                method: 'GET',
                                dataType: 'json',
                                success: function(response) {
                                    console.log('Respuesta GET recibida:', response); // Log the full response
                                    if (response && response.html !== undefined) {
                                        hidePersistentError(); // Hide any previous error message
                                        buttonsContainer.html(response.html); // Update content
                                        buttonsContainer.show(); // Ensure buttons are visible
                                        console.log('Sección Control Horario refrescada con éxito.');
                                    } else {
                                        console.warn('No se recibió HTML para refrescar Control Horario.');
                                        showPersistentError("Error: No se pudo obtener el estado actual de los botones.");
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Error refrescando sección Control Horario:', status, error);
                                    // Check for network error on GET request
                                    if (xhr.status === 0) {
                                        console.error('Fallo de conexión al refrescar.'); // Log network error
                                        showPersistentError("Sin conexión a internet. No se pudo actualizar el estado.");
                                    } else {
                                        // Show generic error for other GET failures
                                        showPersistentError(`Error ${xhr.status} al refrescar estado.`);
                                    }
                                },
                                complete: function() {
                                    // Always hide loading overlay
                                    loadingOverlay.hide();
                                    // Only show buttons if no error is displayed
                                    if (!errorMessageDiv.is(':visible')) {
                                        buttonsContainer.css('display', 'grid');
                                    }
                                    isUpdatingTimeControl = false; // Reset flag
                                    console.log('Refresco completado, UI restaurada, flag reseteado.'); // Log completion
                                }
                            });
                        }


                        // --- Event Handler for Button Clicks (POST Request) ---
                        // Verificar que los elementos existen antes de configurar eventos
                        console.log('Configurando event handlers para botones de control horario...');
                        console.log('Contenedor .control-horario encontrado:', $('.control-horario').length);
                        console.log('Botones .attendance-button encontrados:', $('.attendance-button').length);
                        
                        // Eliminar cualquier manejador previo para evitar duplicados
                        $(document).off('click', '.attendance-button');
                        
                        // Registrar manejador de eventos con delegación
                        $(document).on('click', '.attendance-button', function(event) {
                            console.log('¡Click detectado en botón de control horario!');
                            console.log('Botón clickeado:', this);
                            console.log('Status ID:', $(this).data('status-id'));
                            event.preventDefault();
                            event.stopPropagation();
                            
                            console.log('Procesando click en botón de control horario...');
                            console.log('Geolocalización soportada:', geolocationSupported);
                            console.log('Ubicación inicial obtenida:', initialLocationObtained);
                            console.log('Coordenadas actuales:', { lat: latestLat, long: latestLong });
                            
                            // Intentar obtener ubicación actual en el momento del click
                            if (geolocationSupported) {
                                console.log('Solicitando ubicación actual para el fichaje...');
                                navigator.geolocation.getCurrentPosition(
                                    function(position) {
                                        console.log('Ubicación obtenida para el fichaje:', position.coords.latitude, position.coords.longitude);
                                    },
                                    function(error) {
                                        console.error('Error obteniendo ubicación para el fichaje:', error);
                                    }
                                );
                            }

                            // Check if geolocation is supported first
                            if (!geolocationSupported) {
                                console.error('Geolocalización no soportada');
                                showCriticalError('Geolocalización no soportada por este navegador. No se puede fichar.');
                                return;
                            }

                            // Check if location is available from watchPosition
                            if (!initialLocationObtained || latestLat === null || latestLong === null) {
                                // If watchPosition hasn't provided a location yet, show the persistent error.
                                showPersistentError('Ubicación actual no disponible todavía. Asegúrate de tener permisos y la ubicación activada.');
                                console.warn('Intento de fichaje rechazado: Ubicación no disponible aún.');
                                return; // Prevent clock-in if location isn't ready
                            }


                            if (isUpdatingTimeControl) {
                                console.log('Actualización de Control Horario en progreso, por favor espera.');
                                return;
                            }
                            isUpdatingTimeControl = true; // Set flag for user update

                            // Hide potential previous errors and show loading
                            hidePersistentError();
                            loadingOverlay.css('display', 'flex'); // Show loading using jQuery
                            buttonsContainer.css('display', 'none'); // Hide buttons using jQuery

                            let button = $(this);
                            let statusId = button.data('status-id');
                            let csrfToken = $('meta[name="csrf-token"]').attr('content');

                            if (!csrfToken) {
                                console.error('Token CSRF no encontrado!');
                                showPersistentError('Error de configuración: falta el token CSRF.');
                                isUpdatingTimeControl = false; // Reset flag before returning
                                // Hide loading overlay and show buttons if CSRF is missing
                                loadingOverlay.hide();
                                buttonsContainer.css('display', 'grid');
                                return;
                            }

                            // Use the latest coordinates obtained by watchPosition
                             console.log(`Enviando fichaje con estado ${statusId} en ubicación: ${latestLat}, ${latestLong}`);

                            $.ajax({
                                url: '/add-new-time-control', // POST Route
                                method: 'POST',
                                data: {
                                    _token: csrfToken,
                                    status_id: statusId,
                                    lat: latestLat, // Use stored value
                                    long: latestLong // Use stored value
                                },
                                dataType: 'json',
                                success: function(response) {
                                    console.log('Respuesta POST recibida.'); // Log POST success receive
                                    if (response.success) {
                                        console.log('Fichaje registrado con éxito en servidor!');
                                        // Refresh the section AFTER successful POST
                                        // The refresh function will handle hiding overlay, showing buttons, and resetting the flag
                                        refreshTimeControlSection({ isInterval: false });
                                    } else {
                                        console.error('Error del servidor al fichar:', response.message || 'Error desconocido');
                                        // Show persistent error from server message
                                        showPersistentError(response.message || 'Error al guardar el registro.');
                                        isUpdatingTimeControl = false; // Reset flag on server error
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Error enviando petición AJAX (POST):', status, error, xhr.status);
                                    let errorMsg = 'Error al enviar la solicitud.';
                                    // Check for network error
                                    if (xhr.status === 0) {
                                        errorMsg = "Fallo de conexión a internet. Revisa tu conexión e inténtalo de nuevo.";
                                        console.error('Fallo de conexión a internet detectado (POST).');
                                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMsg += ` ${xhr.responseJSON.message}`;
                                    } else if (xhr.responseText) {
                                        errorMsg += ` Respuesta servidor: ${xhr.responseText.substring(0, 100)}`;
                                    }
                                    // Show persistent error
                                    showPersistentError(errorMsg);
                                    isUpdatingTimeControl = false; // Reset flag on AJAX error
                                }
                                // No 'complete' needed here for POST, success/error handle flag reset or trigger refresh which handles it.
                            });
                           // Removed geolocation.getCurrentPosition call from here
                        }); // End click handler

                        //reobtener la geolocalizacio
                        function attemptLocationUpdate() {
                            if (!geolocationSupported) {
                                console.warn("Geolocation not supported, cannot update location.");
                                // Ensure error is shown if not already
                                if (!errorMessageDiv.is(':visible') || errorMessageDiv.text() !== 'Geolocalización no soportada. No se puede fichar.') {
                                    showPersistentError('Geolocalización no soportada. No se puede fichar.');
                                }
                                return; // Stop if not supported
                            }

                            console.log('Intentando obtener ubicación actual...');
                            navigator.geolocation.getCurrentPosition(
                                (position) => { // Success
                                    latestLat = position.coords.latitude;
                                    latestLong = position.coords.longitude;
                                    if (!initialLocationObtained) {
                                        initialLocationObtained = true;
                                        console.log('Ubicación inicial obtenida (vía getCurrentPosition):', latestLat, latestLong);
                                        hidePersistentError(); // Hide errors now that we have a location
                                    } else {
                                        console.log('Ubicación actualizada (vía getCurrentPosition):', latestLat, latestLong);
                                        // If an error was previously shown, hide it now
                                        if (errorMessageDiv.is(':visible')) {
                                            hidePersistentError();
                                        }
                                    }
                                },
                                (error) => { // Error
                                    console.error("Error obteniendo ubicación actual:", error.message, `(Code: ${error.code})`);
                                    latestLat = null; // Invalidate
                                    latestLong = null;
                                    initialLocationObtained = false;
                                    if (error.code === error.PERMISSION_DENIED || error.code === error.POSITION_UNAVAILABLE) {
                                        showPersistentError("La ubicación está desactivada o denegada. Por favor, actívala para poder fichar.");
                                    } else {
                                        showPersistentError(`Error obteniendo ubicación: ${error.message}`);
                                    }
                                },
                                { // Options
                                    enableHighAccuracy: true,
                                    timeout: 10000, // Give 10 seconds for this attempt
                                    maximumAge: 60000 // Allow using a location up to 1 minute old if needed quickly
                                }
                            );
                        }
                    //finalizar

                        // --- Auto-Refresh Setup ---
                        if (buttonsContainer.length) {
                            console.log('Iniciando intervalo de auto-refresco para Control Horario (5s).');
                            if (window.timeControlIntervalId) { clearInterval(window.timeControlIntervalId); }
                            // Pass flag indicating it IS from interval
                            // Function to run in interval
                            const intervalTask = () => {
                                // Attempt to get current location
                                attemptLocationUpdate();
                                // Refresh buttons (will run even if location fails, uses last known good location for clicks)
                                refreshTimeControlSection({ isInterval: true });
                            };
                            window.timeControlIntervalId = setInterval(() => refreshTimeControlSection({ isInterval: true }), 5000);
                        }
                        
                        // Log de confirmación de inicialización completa
                        console.log('=== CONTROL HORARIO INICIALIZADO COMPLETAMENTE ===');
                        console.log('Estado final:', {
                            geolocationSupported: geolocationSupported,
                            initialLocationObtained: initialLocationObtained,
                            buttonsContainer: buttonsContainer.length,
                            errorMessageDiv: errorMessageDiv.length,
                            loadingOverlay: loadingOverlay.length,
                            watchId: watchId
                        });
                        
                        // Forzar un refresh inicial de los botones
                        console.log('Ejecutando refresh inicial de botones...');
                        refreshTimeControlSection({ isInterval: false });
                        
                        // Asegurar que los botones estén visibles después de la inicialización
                        setTimeout(() => {
                            console.log('Verificación final de visibilidad de botones...');
                            console.log('Estado de elementos:', {
                                buttonsContainer_visible: buttonsContainer.is(':visible'),
                                buttonsContainer_display: buttonsContainer.css('display'),
                                errorMessageDiv_visible: errorMessageDiv.is(':visible'),
                                botones_encontrados: $('.attendance-button').length
                            });
                            
                            // Si hay botones pero no están visibles, mostrarlos
                            if ($('.attendance-button').length > 0 && !buttonsContainer.is(':visible')) {
                                console.log('Forzando visibilidad de botones...');
                                buttonsContainer.show();
                                buttonsContainer.css('display', 'grid');
                            }
                        }, 1000);

                    } else { // End document ready check
                        console.warn("jQuery (window.jQuery) no cargado. Funcionalidad Control Horario desactivada.");
                    }
                } catch (controlHorarioError) {
                    console.error("Error configurando Control Horario:", controlHorarioError);
                     // Try to restore UI just in case
                     hidePersistentError(); // Hide error message div
                     if(buttonsContainer.length) buttonsContainer.css('display', 'grid'); // Show buttons
                     if(loadingOverlay.length) loadingOverlay.hide(); // Hide loading
                }

                // Initialize Task Statistics Chart
                const taskCtx = document.getElementById('taskStatsChart')?.getContext('2d');
                if (taskCtx) {
                    new Chart(taskCtx, {
                        type: 'doughnut',
                        data: {
                            labels: @json($analyticChartData['taskStats']['labels'] ?? []),
                            datasets: [{
                                data: @json($analyticChartData['taskStats']['data'] ?? []),
                                backgroundColor: @json($analyticChartData['taskStats']['colors'] ?? []),
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Initialize Project Progress Chart
                const projectCtx = document.getElementById('projectProgressChart')?.getContext('2d');
                if (projectCtx) {
                    const projectData = {
                        completed: {{ $analyticChartData['projectStats']['completed'] ?? 0 }},
                        inProgress: {{ $analyticChartData['projectStats']['active'] ?? 0 }},
                        overdue: {{ $analyticChartData['projectStats']['overdue'] ?? 0 }}
                    };
                    
                    const totalProjects = {{ $analyticChartData['projectStats']['total'] ?? 1 }} || 1;
                    
                    new Chart(projectCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Progreso'],
                            datasets: [
                                {
                                    label: 'Completados',
                                    data: [(projectData.completed / totalProjects) * 100],
                                    backgroundColor: '#10B981',
                                    barPercentage: 0.6
                                },
                                {
                                    label: 'En Progreso',
                                    data: [(projectData.inProgress / totalProjects) * 100],
                                    backgroundColor: '#F59E0B',
                                    barPercentage: 0.6
                                },
                                {
                                    label: 'Atrasados',
                                    data: [(projectData.overdue / totalProjects) * 100],
                                    backgroundColor: '#EF4444',
                                    barPercentage: 0.6
                                }
                            ]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    stacked: true,
                                    max: 100,
                                    grid: { display: false },
                                    ticks: { display: false }
                                },
                                y: {
                                    stacked: true,
                                    display: false
                                }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                }


                // Initialize Sales Chart
                const salesCtx = document.getElementById('salesChart')?.getContext('2d');
                if (salesCtx) {
                    const salesData = @json($analyticChartData['yearlyRevenue']['revenue'] ?? []);
                    const months = @json($analyticChartData['yearlyRevenue']['year'] ?? []);
                    
                    if (salesData.length > 0 && months.length > 0) {
                        new Chart(salesCtx, {
                            type: 'line',
                            data: {
                                labels: months,
                                datasets: [{
                                    data: salesData,
                                    borderColor: '#3B82F6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: true,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#3B82F6',
                                    pointBorderWidth: 2,
                                    pointRadius: 3,
                                    pointHoverRadius: 5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                const value = context.raw || 0;
                                                return '€' + value.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false,
                                            drawBorder: false
                                        },
                                        ticks: {
                                            maxRotation: 0,
                                            autoSkipPadding: 10
                                        }
                                    },
                                    y: {
                                        display: false,
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                }
        {{-- ** END: Javascript Block ** --}}
    @endpush

    {{-- Script para arreglar el problema de los botones de control horario --}}
    <script src="{{ asset('js/time-control-fix.js') }}?v={{ time() }}"></script>
</x-app-layout>
