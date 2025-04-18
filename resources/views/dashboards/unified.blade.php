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
        {{-- El contenido interno (#buttons-container) se actualizará con AJAX --}}
        <section class="control-horario card p-6 relative"> {{-- Added relative positioning for overlay --}}
            <h2 class="text-xl font-medium text-slate-900 dark:text-white mb-4">Panel de Control de Horario</h2>

            {{-- Container for buttons - This div's content will be replaced --}}
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4" id="buttons-container">
                 {{-- Initial content rendered by the server (or load the partial here initially) --}}
                 {{-- Make sure the variables are passed correctly if using @include --}}
                 @include('partials._control_horario_buttons', ['allowedButtons' => $allowedButtons ?? [], 'allowedAddButtons' => $allowedAddButtons ?? false])
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
                <div class="w-full"> {{-- Adjusted width --}}
                    <h3 class="font-Inter font-normal text-white text-lg">
                        {{ __('Good evening') }}, {{-- Consider dynamic greeting based on time --}}
                    </h3>
                    <h3 class="font-Inter font-medium text-white text-2xl pb-2">
                        {{ auth()->user()?->name ?? 'Invitado' }}
                    </h3>
                    <p class="font-Inter text-base text-white font-normal">
                        {{ __('Welcome to AppNet Developer') }} {{-- Updated welcome message --}}
                    </p>
                </div>
            </div>
            {{-- Total Revenue Card --}}
            <div class="bg-white dark:bg-slate-800 rounded-md px-5 py-4 flex justify-between items-center"> {{-- Use flex for alignment --}}
                <div class="pl-14 relative">
                    <div class="w-10 h-10 rounded-full bg-sky-100 text-sky-800 text-base flex items-center justify-center absolute left-0 top-1/2 -translate-y-1/2"> {{-- Centered icon --}}
                        <iconify-icon icon="ph:shopping-cart-simple-bold"></iconify-icon>
                    </div>
                    <h4 class="font-Inter font-normal text-sm text-textColor dark:text-white pb-1">
                        {{ __('Total revenue') }}
                    </h4>
                    <p class="font-Inter text-xl text-black dark:text-white font-medium">
                        {{-- Use 'yearlyRevenue' from unified data --}}
                        {{ ($data['yearlyRevenue']['currencySymbol'] ?? '$') . number_format($data['yearlyRevenue']['total'] ?? 0, 2) }}
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
                        {{-- Orders --}}
                        <div class="statisticsChartCard">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]"> {{ __('Orders') }} </h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">{{ $data['lastWeekOrder']['total'] ?? 'N/A' }}</h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="{{ ($data['lastWeekOrder']['preSymbol'] ?? '') == '+' ? 'text-success-500' : 'text-danger-500' }}">
                                        {{ $data['lastWeekOrder']['preSymbol'] ?? '' }}{{ $data['lastWeekOrder']['percentage'] ?? 0 }}%
                                    </span>
                                    {{ __('From last week.') }}
                                </p>
                            </div>
                            <div id="columnChart" class="mt-1"></div> {{-- Target for orders column chart --}}
                        </div>
                        {{-- Profit --}}
                        <div class="statisticsChartCard">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]">{{ __('Profit') }}</h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">
                                    {{ $data['lastWeekProfit']['total'] ?? 'N/A' }}
                                </h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="{{ ($data['lastWeekProfit']['preSymbol'] ?? '') == '+' ? 'text-success-500' : 'text-danger-500' }}">
                                       {{ $data['lastWeekProfit']['preSymbol'] ?? '' }}{{ $data['lastWeekProfit']['percentage'] ?? 0 }}%
                                    </span>
                                    {{ __('From last week.') }}
                                </p>
                            </div>
                            <div id="lineChart" class="mt-1"></div> {{-- Target for profit line chart --}}
                        </div>
                        {{-- Overview Donut --}}
                        <div class="statisticsChartCard py-4 md:col-span-2 md:flex items-center justify-between">
                            <div>
                                <h5 class="text-sm text-slate-600 dark:text-slate-300 mb-[6px]">{{ $data['lastWeekOverview']['title'] ?? 'Overview' }}</h5>
                                <h3 class="text-lg text-slate-900 dark:text-slate-300 font-medium mb-[6px]">{{ $data['lastWeekOverview']['amount'] ?? 'N/A' }}</h3>
                                <p class="font-normal text-xs text-slate-600 dark:text-slate-300">
                                    <span class="text-indigo-500"> {{-- Consistent color --}}
                                        {{ number_format(($data['lastWeekOverview']['percentage'] ?? 0) * 100, 1) }}%
                                    </span>
                                    {{ __('From last Week') }}
                                </p>
                            </div>
                            <div id="donutChart"></div> {{-- Target for overview donut chart --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer & Orders Row --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- Top Customer Area (from Dashboard 1 - structure kept, data source is $topCustomers) --}}
            <div class="xl:col-span-6 col-span-12">
                <div class="card h-full">
                    <div class="card-header flex justify-between items-center">
                        <h4 class="card-title">Top Customers</h4>
                        {{-- Dropdown can be added back if needed --}}
                    </div>
                    <div class="card-body p-6">
                        {{-- Check if $topCustomers exists and is not empty --}}
                        {{-- Make sure $topCustomers is passed as an array or Traversable from controller --}}
                        @if(isset($topCustomers) && (is_array($topCustomers) || $topCustomers instanceof \Illuminate\Support\Collection) && count($topCustomers) > 0)
                            <div class="grid md:grid-cols-3 grid-cols-1 gap-5 pb-2">
                                {{-- Display top 3 customers prominently --}}
                                @foreach(collect($topCustomers)->take(3) as $customer) {{-- Ensure it's a collection for take() --}}
                                <div class="relative z-[1] text-center p-4 rounded before:w-full before:h-[calc(100%-60px)] before:absolute before:left-0 before:top-[60px] before:rounded before:z-[-1] before:bg-opacity-[0.1] before:bg-{{ $customer['backgroundColor'] ?? 'info' }}-500">
                                    <div class="h-[70px] w-[70px] rounded-full mx-auto mb-4 relative {{ $customer['isMvpUser'] ? 'ring-2 ring-amber-500' : '' }}">
                                        @if($customer['isMvpUser'])
                                        <span class="crown absolute -top-[24px] left-1/2 -translate-x-1/2">
                                            <img src="{{ asset('images/icon/crown.svg') }}" alt="MVP">
                                        </span>
                                        @endif
                                        <img src="{{ asset($customer['photo'] ?? 'images/users/user-1.jpg') }}" alt="{{ $customer['name'] }}" class="w-full h-full rounded-full object-cover">
                                        <span class="h-[27px] w-[27px] absolute right-0 bottom-0 rounded-full bg-[#FFC155] border border-white flex flex-col items-center justify-center text-white text-xs font-medium">
                                            {{ $customer['serialNo'] }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm text-slate-600 dark:text-slate-300 font-semibold mb-4">
                                        {{ $customer['name'] }}
                                    </h4>
                                    <div class="inline-block bg-slate-900 dark:bg-slate-700 text-white px-[10px] py-[6px] text-xs font-medium rounded-full min-w-[60px]">
                                        {{ $customer['totalPoint'] }} pts
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-sm font-normal dark:text-slate-300 mb-3 mt-4">
                                            <span>Progress</span>
                                            <span class="font-normal">{{ $customer['progressBarPoint'] }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-xl overflow-hidden">
                                            <div class="progress-bar bg-{{ $customer['progressBarColor'] ?? 'info' }}-500 h-full rounded-xl" style="width: {{ $customer['progressBarPoint'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                             {{-- Display remaining customers in a list --}}
                            @if(count($topCustomers) > 3)
                            <div class="grid grid-cols-1 gap-4 mt-5">
                                @foreach(collect($topCustomers)->slice(3) as $customer) {{-- Ensure it's a collection for slice() --}}
                                <div class="relative z-[1] p-4 rounded md:flex items-center bg-slate-50 dark:bg-slate-900 md:space-x-4 rtl:space-x-reverse md:space-y-0 space-y-3">
                                    <div class="flex-none h-10 w-10 rounded-full relative">
                                        <img src="{{ asset($customer['photo'] ?? 'images/users/user-1.jpg') }}" alt="{{ $customer['name'] }}" class="w-full h-full rounded-full object-cover">
                                        <span class="h-4 w-4 absolute right-0 bottom-0 rounded-full bg-[#FFC155] border border-white flex flex-col items-center justify-center text-white text-[10px] font-medium">
                                            {{ $customer['serialNo'] }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm text-slate-600 dark:text-slate-300 font-semibold flex-none md:w-32">
                                        {{ $customer['name'] }}
                                    </h4>
                                    <div class="inline-block text-center bg-slate-900 dark:bg-slate-700 text-white px-[10px] py-[6px] text-xs font-medium rounded-full min-w-[60px]">
                                        {{ $customer['totalPoint'] }} pts
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between text-sm font-normal dark:text-slate-300 mb-1">
                                            <span>Progress</span>
                                            <span class="font-normal">{{ $customer['progressBarPoint'] }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-xl overflow-hidden">
                                            <div class="progress-bar bg-{{ $customer['progressBarColor'] ?? 'info' }}-500 h-full rounded-xl" style="width: {{ $customer['progressBarPoint'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        @else
                            <p class="text-slate-500 dark:text-slate-400">No customer data available.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Orders Table (from Dashboard 1 - structure kept, data source is $recentOrders) --}}
            <div class="xl:col-span-6 col-span-12">
                <div class="card h-full">
                    <div class="card-header flex justify-between items-center">
                        <h4 class="card-title">Recent Orders</h4>
                         {{-- Dropdown can be added back if needed --}}
                    </div>
                    <div class="card-body p-6">
                        <div class="overflow-x-auto -mx-6">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden ">
                                     {{-- Check if $recentOrders exists and is not empty --}}
                                     {{-- Make sure $recentOrders is passed as an array or Traversable from controller --}}
                                    @if(isset($recentOrders) && (is_array($recentOrders) || $recentOrders instanceof \Illuminate\Support\Collection) && count($recentOrders) > 0)
                                    <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                                        <thead class="bg-slate-200 dark:bg-slate-700">
                                            <tr>
                                                <th scope="col" class="table-th">Company</th>
                                                <th scope="col" class="table-th">Product Type</th>
                                                <th scope="col" class="table-th">Invoice</th>
                                                <th scope="col" class="table-th">Amount</th>
                                                <th scope="col" class="table-th">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                            @foreach($recentOrders as $order)
                                            <tr>
                                                <td class="table-td">
                                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $order['companyName'] }}</span><br>
                                                    <span class="text-xs text-slate-500">{{ $order['email'] }}</span>
                                                </td>
                                                <td class="table-td">{{ $order['productType'] }}</td>
                                                <td class="table-td">{{ $order['invoiceNo'] }}</td>
                                                <td class="table-td">{{ $order['currencySymbol'] ?? '$' }}{{ number_format($order['amount'] ?? 0, 2) }}</td>
                                                <td class="table-td">
                                                    @php
                                                        $statusClass = match(strtolower($order['paymentStatus'] ?? '')) {
                                                            'paid' => 'bg-success-500 text-success-500',
                                                            'due' => 'bg-warning-500 text-warning-500',
                                                            'pending' => 'bg-info-500 text-info-500',
                                                            'cancled', 'cancelled' => 'bg-danger-500 text-danger-500',
                                                            'shipped' => 'bg-primary-500 text-primary-500',
                                                            default => 'bg-slate-500 text-slate-500',
                                                        };
                                                    @endphp
                                                    <div class="inline-block px-3 min-w-[90px] text-center mx-auto py-1 rounded-[999px] bg-opacity-25 {{ $statusClass }}">
                                                        {{ ucfirst($order['paymentStatus'] ?? 'Unknown') }}
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @else
                                        <p class="text-slate-500 dark:text-slate-400 p-4">No recent orders found.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
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
                        <h4 class="card-title">Users</h4>
                         {{-- Dropdown can be added back if needed --}}
                    </header>
                    <div class="card-body p-6">
                         {{-- Check if $users exists and has items --}}
                         {{-- Make sure $users is a Paginator instance from controller --}}
                        @if(isset($users) && $users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->count() > 0)
                        <div class="overflow-x-auto -mx-6">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden ">
                                    <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                                        <thead class="bg-slate-200 dark:bg-slate-700">
                                            <tr>
                                                <th scope="col" class="table-th">{{ __('NAME') }}</th>
                                                <th scope="col" class="table-th">{{ __('EMAIL') }}</th>
                                                <th scope="col" class="table-th">{{ __('MEMBER SINCE') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                            @foreach($users as $userItem) {{-- Renamed variable to avoid conflict --}}
                                            @php
                                                // Logic to get profile image - Adapt if your media handling is different
                                                $profileImageUrl = $userItem->profile_photo_url ?? null; // Example using Jetstream convention
                                                if (!$profileImageUrl && class_exists(\Laravolt\Avatar\Facade::class)) {
                                                    // Fallback using UI Avatars if Facade/Helper is configured
                                                     try { $profileImageUrl = \Laravolt\Avatar\Facade::create($userItem->name)->toBase64(); } catch (\Exception $e) { $profileImageUrl = null; }
                                                }
                                                if (!$profileImageUrl) {
                                                    // Basic placeholder if UI Avatars isn't used or fails
                                                    $profileImageUrl = 'https://ui-avatars.com/api/?name=' . urlencode($userItem->name) . '&color=7F9CF5&background=EBF4FF';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="table-td">
                                                    <div class="flex items-center">
                                                        <div class="flex-none">
                                                            <div class="w-8 h-8 rounded-full ltr:mr-3 rtl:ml-3">
                                                                <img class="w-full h-full rounded-full object-cover"
                                                                     src="{{ $profileImageUrl }}"
                                                                     alt="{{ $userItem->name }}"/>
                                                            </div>
                                                        </div>
                                                        <div class="flex-1 text-start">
                                                            <h4 class="text-sm font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                                                {{ $userItem->name }}
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="table-td">{{ $userItem->email }}</td>
                                                <td class="table-td ">{{ $userItem->created_at ? $userItem->created_at->diffForHumans() : 'N/A' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- Pagination Links --}}
                        <div class="pagination-area flex flex-wrap gap-3 items-center justify-center pt-8 px-8">
                            {{ $users->links('vendor.pagination.tailwind') }} {{-- Use Tailwind pagination view --}}
                        </div>
                        @else
                            <p class="text-slate-500 dark:text-slate-400 p-4">No users found.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Activity (from Dashboard 2 - Static example) --}}
            <div class="lg:col-span-4 col-span-12">
                <div class="card h-full">
                    <div class="card-header">
                        <h4 class="card-title">Recent Activity</h4>
                    </div>
                    <div class="card-body p-6">
                        {{-- This is static content, replace with dynamic data if needed --}}
                        <div>
                            <ul class="list-item space-y-3 h-full overflow-x-auto max-h-[400px]"> {{-- Added max-height --}}
                                <li class="flex items-center space-x-3 rtl:space-x-reverse border-b border-slate-100 dark:border-slate-700 last:border-b-0 pb-3 last:pb-0">
                                    <div><div class="w-8 h-8 rounded-full"><img src="{{ asset('images/users/user-1.jpg') }}" alt="" class="w-full h-full rounded-full object-cover"></div></div>
                                    <div class="text-start overflow-hidden text-ellipsis whitespace-nowrap max-w-[63%]"><div class="text-sm text-slate-600 dark:text-slate-300 overflow-hidden text-ellipsis whitespace-nowrap">User John Doe logged in.</div></div>
                                    <div class="flex-1 ltr:text-right rtl:text-left"><div class="text-sm font-light text-slate-400 dark:text-slate-400">1 hour ago</div></div>
                                </li>
                                <li class="flex items-center space-x-3 rtl:space-x-reverse border-b border-slate-100 dark:border-slate-700 last:border-b-0 pb-3 last:pb-0">
                                    <div><div class="w-8 h-8 rounded-full"><img src="{{ asset('images/users/user-2.jpg') }}" alt="" class="w-full h-full rounded-full object-cover"></div></div>
                                    <div class="text-start overflow-hidden text-ellipsis whitespace-nowrap max-w-[63%]"><div class="text-sm text-slate-600 dark:text-slate-300 overflow-hidden text-ellipsis whitespace-nowrap">New order #INV-2025-004 received.</div></div>
                                    <div class="flex-1 ltr:text-right rtl:text-left"><div class="text-sm font-light text-slate-400 dark:text-slate-400">2 hours ago</div></div>
                                </li>
                                <li class="flex items-center space-x-3 rtl:space-x-reverse border-b border-slate-100 dark:border-slate-700 last:border-b-0 pb-3 last:pb-0">
                                    <div><div class="w-8 h-8 rounded-full"><img src="{{ asset('images/users/user-3.jpg') }}" alt="" class="w-full h-full rounded-full object-cover"></div></div>
                                    <div class="text-start overflow-hidden text-ellipsis whitespace-nowrap max-w-[63%]"><div class="text-sm text-slate-600 dark:text-slate-300 overflow-hidden text-ellipsis whitespace-nowrap">Password changed for Jane Smith.</div></div>
                                    <div class="flex-1 ltr:text-right rtl:text-left"><div class="text-sm font-light text-slate-400 dark:text-slate-400">3 hours ago</div></div>
                                </li>
                                {{-- Add more static or dynamic items --}}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Map & Server Monitor Row --}}
        <div class="grid grid-cols-12 gap-6">
            {{-- Most Sales Map (from Dashboard 2 - Static example) --}}
            <div class="lg:col-span-8 col-span-12">
                <div class="card h-full">
                    <div class="card-header">
                        <h4 class="card-title">Sales by Region</h4> {{-- Renamed title --}}
                    </div>
                    <div class="card-body p-6">
                        {{-- This section uses jVectorMap, ensure JS is loaded --}}
                        <div class="md:flex items-center">
                            <div class="grow-0">
                                <h4 class="text-slate-600 dark:text-slate-200 text-sm font-normal mb-[6px]">
                                    Total earnings
                                </h4>
                                <div class="text-lg font-medium mb-[6px] dark:text-white text-slate-900">
                                    $12,65,647.87 {{-- Example data --}}
                                </div>
                                <div class="text-xs font-light dark:text-slate-200">
                                    <span class="text-primary-500">+08%</span> From last month {{-- Example data --}}
                                </div>
                                {{-- Simplified stats list --}}
                                <ul class="bg-slate-50 dark:bg-slate-900 rounded p-4 min-w-[184px] space-y-3 mt-4">
                                    <li class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <span class="flex space-x-2 rtl:space-x-reverse items-center"><span class="inline-flex h-[6px] w-[6px] bg-primary-500 rounded-full"></span><span>USA</span></span>
                                        <span>$125k</span>
                                    </li>
                                    <li class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <span class="flex space-x-2 rtl:space-x-reverse items-center"><span class="inline-flex h-[6px] w-[6px] bg-success-500 rounded-full"></span><span>Canada</span></span>
                                        <span>$95k</span>
                                    </li>
                                     <li class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <span class="flex space-x-2 rtl:space-x-reverse items-center"><span class="inline-flex h-[6px] w-[6px] bg-info-500 rounded-full"></span><span>Mexico</span></span>
                                        <span>$75k</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="grow">
                                <div class="h-[360px] w-full ltr:pl-10 rtl:pr-10">
                                    <div id="world-map" class="h-full w-full"></div> {{-- Target for jVectorMap --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Server Monitor (REVERTED to original direct execution style) --}}
            <div class="lg:col-span-4 col-span-12">
                <div class="card h-full">
                    <div class="card-header">
                        <h4 class="card-title">Monitor de Servidor</h4>
                    </div>
                    <div class="card-body p-6">
                        {{-- ApexCharts Pie chart for Load Average --}}
                        <div id="cpu-load-chart" class="mb-4"></div> {{-- Target for load average pie chart --}}

                        {{-- Server stats using direct shell_exec (similar to original, with basic fallback) --}}
                        <div class="bg-slate-50 dark:bg-slate-900 rounded p-4 flex justify-between flex-wrap gap-4">
                            <div class="space-y-1 basis-1/2 sm:basis-auto">
                                <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">Memoria RAM</h4>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{-- Execute command directly, trim output, provide fallback 'N/A' --}}
                                    {{-- Ensure $ variables for awk are escaped --}}
                                    {{ trim(shell_exec("free -m | awk 'NR==2{printf \"%.1f%%\", \$3*100/\$2 }'")) ?: 'N/A' }}
                                </div>
                                <div class="text-slate-500 dark:text-slate-300 text-xs font-normal">
                                    {{ trim(shell_exec("free -h | awk '/^Mem:/{print \$2}'")) ?: 'N/A' }} /
                                    {{ trim(shell_exec("free -h | awk '/^Mem:/{print \$3}'")) ?: 'N/A' }} used
                                </div>
                            </div>
                            <div class="space-y-1 basis-1/2 sm:basis-auto">
                                <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">Uso de Disco (/)</h4>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                     {{ trim(shell_exec("df -h | awk '\$NF==\"/\"{printf \"%s\", \$5}'")) ?: 'N/A' }}
                                </div>
                            </div>
                            <div class="space-y-1 basis-1/2 sm:basis-auto">
                                <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">Carga de CPU</h4>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                     {{ trim(shell_exec("top -bn1 | grep '%Cpu(s):' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{printf \"%.1f%%\", 100 - \$1}'")) ?: 'N/A' }}
                                </div>
                            </div>
                             <div class="space-y-1 basis-full sm:basis-auto">
                                <h4 class="text-slate-600 dark:text-slate-200 text-xs font-normal">Load Avg (1, 5, 15m)</h4>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{-- Load average still comes from controller data for consistency --}}
                                    {{ implode(', ', $data['loadAverage'] ?? ['N/A', 'N/A', 'N/A']) }}
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
        {{-- Load jQuery from CDN FIRST --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        {{-- Load jQuery Mousewheel Plugin (Required by jVectorMap for zoom) - CORRECTED SRI HASH --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js" integrity="sha512-rCjfoab9CVKOH/w/T6GbBxnAH5Azhy4+q1EXW5XEURefHbIkRbQ++ZR+GBClo3/d3q583X/gO4FKmOFuhkKrdA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

        {{-- jVectorMap dependencies (from Dashboard 2) --}}
        {{-- Ensure these paths are correct relative to your public/build directory if using Vite build --}}
        {{-- Or adjust vite.config.js input array --}}
        @vite(['resources/js/plugins/jquery-jvectormap-2.0.5.min.js'])
        @vite(['resources/js/plugins/jquery-jvectormap-world-mill-en.js'])

        {{-- ** START: Javascript Block with AJAX Refresh & Interval (Flag Logic Corrected) ** --}}
        <script type="module">
            // Wait for the DOM to be fully loaded
            document.addEventListener('DOMContentLoaded', function() {

                // Helper function to query DOM elements (using vanilla JS)
                const $q = (selector) => document.querySelector(selector);
                const $$q = (selector) => document.querySelectorAll(selector);

                // Get data passed from the controller using Js::from for safety
                const dashboardData = {{ Illuminate\Support\Js::from($data ?? []) }};
                const productGrowthOverviewData = {{ Illuminate\Support\Js::from($data['productGrowthOverview'] ?? null) }};
                const loadAverageDataRaw = {{ Illuminate\Support\Js::from($data['loadAverage'] ?? null) }};

                // Flag to prevent multiple simultaneous updates
                let isUpdatingTimeControl = false;
                let timeControlIntervalId = null; // To store the interval ID

                // --- Chart Initializations (Using Vanilla JS) ---
                // Wrapped in try...catch for better error isolation
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
                                legend: { show: true, floating: true, fontSize: '12px', position: 'left', offsetX: 50, offsetY: 15, labels: { useSeriesColors: true }, markers: { size: 0 }, formatter: (seriesName, opts) => `${seriesName}: ${opts.w.globals.series[opts.seriesIndex]}`, itemMargin: { vertical: 3 } },
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

                        // Function to refresh the time control section via AJAX GET
                        function refreshTimeControlSection(options = {}) {
                            const { isInterval = false } = options;

                             // Prevent overlapping interval calls ONLY
                             if (isInterval && isUpdatingTimeControl) {
                                 console.log('Previous time control update still in progress, skipping interval refresh.');
                                 return;
                             }
                             // ** REMOVED faulty check: if (isUpdatingTimeControl && !isInterval) { ... return; } **

                            const buttonsContainer = $("#buttons-container");
                            const loadingOverlay = $("#loading-overlay");

                            if (!buttonsContainer.length) return;

                            // Set flag *before* the request
                            isUpdatingTimeControl = true; // Set flag indicating an update (either user or interval) is starting
                            console.log('Refreshing time control section...', { isInterval });

                            // Don't show overlay for automatic interval refresh
                            // Show overlay only if triggered by user click (isInterval === false)
                            // Note: The click handler ALREADY shows the overlay before calling this function when isInterval is false.
                            // So, no need to show overlay here.

                            $.ajax({
                                url: '/get-time-control-section', // GET Route
                                method: 'GET',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.html !== undefined) {
                                        buttonsContainer.html(response.html); // Update content
                                        console.log('Time control section refreshed successfully.');
                                    } else {
                                         console.warn('No HTML received for time control refresh.');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Error refreshing time control section:', status, error);
                                    // Optionally display a non-intrusive error
                                },
                                complete: function() {
                                    // ALWAYS ensure UI is in correct state after refresh attempt
                                    buttonsContainer.css('display', 'grid'); // Show buttons
                                    loadingOverlay.hide(); // Hide loading
                                    isUpdatingTimeControl = false; // Reset flag
                                    console.log('Refresh complete, UI restored, flag reset.');
                                }
                            });
                        }

                        // Function to show error alerts (can be customized)
                        function showErrorAlert(message) {
                            alert(`Error: ${message}`);
                        }

                        // --- Event Handler for Button Clicks (POST Request) ---
                        // Use event delegation
                        $('.control-horario').on('click', '.attendance-button', function(event) {
                            event.preventDefault();

                            if (isUpdatingTimeControl) {
                                console.log('Time control update in progress, please wait.');
                                return;
                            }
                            isUpdatingTimeControl = true; // Set flag for user update

                            const loadingOverlay = $("#loading-overlay");
                            const buttonsContainer = $("#buttons-container");

                            loadingOverlay.css('display', 'flex'); // Show loading using jQuery
                            buttonsContainer.css('display', 'none'); // Hide buttons using jQuery

                            let button = $(this);
                            let statusId = button.data('status-id');
                            let csrfToken = $('meta[name="csrf-token"]').attr('content');

                            if (!csrfToken) {
                                console.error('CSRF token not found!');
                                showErrorAlert('Error de configuración: falta el token CSRF.');
                                loadingOverlay.hide();
                                buttonsContainer.css('display', 'grid');
                                isUpdatingTimeControl = false;
                                return;
                            }

                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(function(position) {
                                    let lat = position.coords.latitude;
                                    let long = position.coords.longitude;

                                    $.ajax({
                                        url: '/add-new-time-control', // POST Route
                                        method: 'POST',
                                        data: { _token: csrfToken, status_id: statusId, lat: lat, long: long },
                                        dataType: 'json',
                                        success: function(response) {
                                            if (response.success) {
                                                console.log('Fichaje registrado!');
                                                // Refresh the section AFTER successful POST
                                                // The refresh function will handle hiding overlay, showing buttons, and resetting the flag
                                                refreshTimeControlSection({ isInterval: false });
                                            } else {
                                                console.error('Error from server:', response.message || 'Unknown error');
                                                showErrorAlert(response.message || 'Error al guardar el registro.');
                                                loadingOverlay.hide();
                                                buttonsContainer.css('display', 'grid'); // Show buttons on server error
                                                isUpdatingTimeControl = false; // Reset flag on error
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Error sending AJAX request:', status, error);
                                            let errorMsg = 'Error al enviar la solicitud.';
                                            if (xhr.responseJSON && xhr.responseJSON.message) { errorMsg += ` ${xhr.responseJSON.message}`; }
                                            else if (xhr.responseText) { errorMsg += ` Server response: ${xhr.responseText.substring(0, 100)}`;}
                                            showErrorAlert(errorMsg);
                                            loadingOverlay.hide();
                                            buttonsContainer.css('display', 'grid'); // Show buttons on AJAX error
                                            isUpdatingTimeControl = false; // Reset flag on error
                                        }
                                    });
                                }, function(error) { // Geolocation error
                                    console.error("Error getting location:", error.message);
                                    let errorMsg = 'Error al obtener la ubicación: ';
                                     switch(error.code) {
                                        case error.PERMISSION_DENIED: errorMsg += "Permiso denegado."; break;
                                        case error.POSITION_UNAVAILABLE: errorMsg += "Posición no disponible."; break;
                                        case error.TIMEOUT: errorMsg += "Tiempo de espera agotado."; break;
                                        default: errorMsg += "Error desconocido."; break;
                                    }
                                    showErrorAlert(errorMsg);
                                    loadingOverlay.hide();
                                    buttonsContainer.css('display', 'grid');
                                    isUpdatingTimeControl = false; // Reset flag
                                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
                            } else {
                                console.log("Geolocation not supported.");
                                showErrorAlert('Geolocalización no soportada.');
                                loadingOverlay.hide();
                                buttonsContainer.css('display', 'grid');
                                isUpdatingTimeControl = false; // Reset flag
                            }
                        }); // End click handler

                        // --- Auto-Refresh Setup ---
                        if ($("#buttons-container").length) {
                             console.log('Starting time control auto-refresh interval (5s).');
                             if (window.timeControlIntervalId) { clearInterval(window.timeControlIntervalId); }
                             // Pass flag indicating it IS from interval
                             window.timeControlIntervalId = setInterval(() => refreshTimeControlSection({ isInterval: true }), 5000);
                        }

                    } else { // End document ready check
                        console.warn("jQuery (window.jQuery) not loaded. Control Horario functionality disabled.");
                    }
                } catch (controlHorarioError) {
                    console.error("Error setting up Control Horario:", controlHorarioError);
                     const loadingOverlay = $q("#loading-overlay");
                     const buttonsContainer = $q("#buttons-container");
                     if (loadingOverlay) loadingOverlay.style.display = 'none';
                     if (buttonsContainer) buttonsContainer.style.display = 'grid';
                }

            }); // End DOMContentLoaded
        </script>
        {{-- ** END: Javascript Block ** --}}
    @endpush

</x-app-layout>
