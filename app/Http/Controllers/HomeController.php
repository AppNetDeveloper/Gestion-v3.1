<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{

    /**
     * Analytic Dashboard
     */
    /**
     * Get task counts by status
     */
    protected function getTaskCounts(): array
    {
        if (!class_exists('App\Models\Task')) {
            return [
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'total' => 0
            ];
        }

        $counts = [
            'pending' => \App\Models\Task::where('status', 'pending')->count(),
            'in_progress' => \App\Models\Task::where('status', 'in_progress')->count(),
            'completed' => \App\Models\Task::where('status', 'completed')->count(),
            'total' => \App\Models\Task::count()
        ];

        return $counts;
    }

    /**
     * Get recent tasks
     */
    protected function getRecentTasks(int $limit = 5): array
    {
        if (!class_exists('App\Models\Task')) {
            return [];
        }

        return \App\Models\Task::with(['project' => function($query) {
                $query->select('id', 'project_title');
            }])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'due_date' => $task->due_date ? $task->due_date->format('d/m/Y') : null,
                    'project' => $task->project->project_title ?? null,
                    'created_at' => $task->created_at->diffForHumans()
                ];
            })
            ->toArray();
    }

    /**
     * Obtener estadísticas de tareas por estado
     */
    protected function getTaskStats()
    {
        if (!class_exists('App\Models\Task')) {
            return [
                'labels' => ['Pendientes', 'En Progreso', 'Completadas'],
                'data' => [0, 0, 0],
                'colors' => ['#3B82F6', '#F59E0B', '#10B981']
            ];
        }

        $stats = [
            'Pendientes' => \App\Models\Task::where('status', 'pending')->count(),
            'En Progreso' => \App\Models\Task::where('status', 'in_progress')->count(),
            'Completadas' => \App\Models\Task::where('status', 'completed')->count()
        ];

        return [
            'labels' => array_keys($stats),
            'data' => array_values($stats),
            'colors' => ['#3B82F6', '#F59E0B', '#10B981']
        ];
    }

    /**
     * Obtener estadísticas de proyectos
     */
    protected function getProjectStats()
    {
        if (!class_exists('App\Models\Project')) {
            return [
                'total' => 0,
                'active' => 0,
                'completed' => 0,
                'overdue' => 0
            ];
        }

        $today = now()->toDateString();
        
        return [
            'total' => \App\Models\Project::count(),
            'active' => \App\Models\Project::where('status', 'in_progress')->count(),
            'completed' => \App\Models\Project::where('status', 'completed')->count(),
            'overdue' => \App\Models\Project::where('due_date', '<', $today)
                ->where('status', '!=', 'completed')
                ->count()
        ];
    }

    /**
     * Obtener estadísticas de ventas
     */
    protected function getSalesStats()
    {
        // Usar los últimos 6 meses
        $months = collect();
        $salesData = collect();
        
        // Obtener datos reales de facturas
        $invoices = \App\Models\Invoice::select(
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy(DB::raw('MIN(created_at)'))
            ->get();
        
        // Rellenar los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $months->push($monthYear);
            
            // Buscar datos reales para este mes
            $monthData = $invoices->firstWhere('month', $monthYear);
            $salesData->push($monthData ? $monthData->total_amount : 0);
        }
        
        // Calcular crecimiento respecto al mes anterior
        $growth = 0;
        if ($salesData->count() > 1) {
            $currentMonth = $salesData->last();
            $previousMonth = $salesData->slice(-2, 1)->first();
            
            if ($previousMonth > 0) {
                $growth = (($currentMonth - $previousMonth) / $previousMonth) * 100;
            } elseif ($currentMonth > 0) {
                $growth = 100; // 100% de crecimiento si no había ventas el mes anterior
            }
        }
        
        return [
            'months' => $months,
            'sales' => $salesData,
            'total' => $invoices->sum('total_amount'),
            'growth' => $growth,
            'total_orders' => $invoices->sum('count')
        ];
    }

    public function unifiedDashboard(Request $request): \Illuminate\Contracts\View\View
    {
        // Initialize growth array
        $growthData = [
            'preSymbol' => '+',
            'postSymbol' => '%',
            'value' => 0
        ];
        
        // Obtener total de ventas
        $totalSales = $this->getTotalSales();
        
        // Obtener conteo total de facturas (no solo las pagadas)
        $totalInvoices = \App\Models\Invoice::count();
        
        // Obtener actividad reciente
        $recentActivities = $this->getRecentActivities(5);
        
        // Obtener pedidos recientes
        $recentOrders = $this->getRecentOrders(5);
        
        // Obtener contadores de tareas
        $taskCounts = $this->getTaskCounts();
        
        // Obtener tareas recientes
        $recentTasks = $this->getRecentTasks(5);
        
        // Obtener estadísticas
        $taskStats = $this->getTaskStats();
        $projectStats = $this->getProjectStats();
        $salesStats = $this->getSalesStats();
        
        // Formatear datos de ventas para gráficos
        $yearlyRevenue = [
            'year' => $salesStats['months']->toArray(),
            'revenue' => $salesStats['sales']->map(fn($amount) => (float) number_format($amount, 2, '.', ''))->toArray(),
            'total' => number_format($salesStats['total'], 2, ',', '.'),
            'growth' => round($salesStats['growth'], 2),
            'currencySymbol' => '€',
        ];

        // Calcular beneficio (asumiendo un margen del 30% como ejemplo)
        $profit = $salesStats['total'] * 0.3; // Ajustar según tu lógica de negocio
        
        // Calcular crecimiento de pedidos
        $orderGrowth = 0;
        if (isset($salesStats['total_orders_previous_period']) && $salesStats['total_orders_previous_period'] > 0) {
            $orderGrowth = (($salesStats['total_orders'] - $salesStats['total_orders_previous_period']) / $salesStats['total_orders_previous_period']) * 100;
        } elseif ($salesStats['total_orders'] > 0) {
            $orderGrowth = 100; // 100% de crecimiento si no había pedidos en el período anterior
        }

        // Preparar estadísticas para la vista
        $viewData = [
            'pageTitle' => 'Dashboard',
            'totalSales' => number_format($salesStats['total'], 2, ',', '.'),
            'totalOrders' => $salesStats['total_orders'],
            'totalProfit' => number_format($profit, 2, ',', '.'),
            'salesGrowth' => round($salesStats['growth'], 2),
            'orderGrowth' => round($orderGrowth, 2),
            'profitGrowth' => round($salesStats['growth'], 2), // Mismo crecimiento que ventas por defecto
            'recentActivities' => $recentActivities,
            'recentOrders' => $recentOrders,
            'taskCounts' => $taskCounts,
            'recentTasks' => $recentTasks,
            'analyticChartData' => [
                'yearlyRevenue' => $yearlyRevenue,
                'totalSales' => number_format($salesStats['total'], 2, ',', '.'),
                'totalOrders' => $salesStats['total_orders'],
                'recentActivities' => $recentActivities,
                'recentOrders' => $recentOrders,
                'taskStats' => $taskStats,
                'projectStats' => $projectStats,
                'taskCounts' => $taskCounts,
            ],
            'stats' => [
                'sales' => [
                    'value' => number_format($salesStats['total_orders']),
                    'growth' => round($orderGrowth, 1),
                    'trend' => $orderGrowth >= 0 ? 'up' : 'down',
                    'label' => 'Pedidos',
                    'icon' => 'shopping-cart',
                    'prefix' => ''
                ],
                'revenue' => [
                    'value' => number_format($salesStats['total'], 2, ',', '.'),
                    'growth' => round($salesStats['growth'], 1),
                    'trend' => $salesStats['growth'] >= 0 ? 'up' : 'down',
                    'label' => 'Ingresos',
                    'icon' => 'euro-sign',
                    'prefix' => '€'
                ],
                'profit' => [
                    'value' => number_format($profit, 2, ',', '.'),
                    'growth' => round($salesStats['growth'], 1), // Mismo crecimiento que ventas
                    'trend' => $salesStats['growth'] >= 0 ? 'up' : 'down',
                    'label' => 'Beneficio',
                    'icon' => 'chart-line',
                    'prefix' => '€'
                ]
            ]
        ];

        // Obtener usuarios con paginación
        try {
            if (class_exists('App\Models\User')) {
                $viewData['users'] = \App\Models\User::query()
                    ->whereNull('deleted_at') // Solo usuarios no eliminados
                    ->latest()
                    ->paginate(5, ['*'], 'users_page');
            } else {
                $viewData['users'] = collect();
            }
        } catch (\Exception $e) {
            \Log::error('Error al cargar usuarios: ' . $e->getMessage());
            $viewData['users'] = collect();
        }

        // Add additional data required by the view
        $viewData['revenueReport'] = [
            'month' => ["Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct"],
            'revenue' => [
                'title' => 'Revenue',
                'data' => [76, 85, 101, 98, 87, 105, 91, 114, 94],
            ],
            'netProfit' => [
                'title' => 'Net Profit',
                'data' => [35, 41, 36, 26, 45, 48, 52, 53, 41],
            ]
        ];

        // Set growth data
        $viewData['growth'] = $growthData;

        // Add cash flow data
        $viewData['cashFlow'] = [
            'title' => 'Cash Flow',
            'data' => [44, 55, 57, 56, 61, 58, 63, 60, 66]
        ];

        // Add product growth overview
        $viewData['productGrowthOverview'] = [
            'productNames' => ["Books", "Pens", "Pencils", "Box"],
            'data' => [88, 77, 66, 55]
        ];

        // Add this year growth data
        $viewData['thisYearGrowth'] = [
            'label' => ['Yearly Growth'],
            'data' => [66]
        ];

        // Add investment data
        $viewData['investmentAmount'] = [
            [
                'title' => 'Investment Alpha',
                'amount' => 1000,
                'currencySymbol' => '$',
                'profit' => 10,
                'profitPercentage' => 50,
                'loss' => 0,
                'lossPercentage' => 0
            ],
            [
                'title' => 'Investment Beta',
                'amount' => 2500,
                'currencySymbol' => '$',
                'profit' => 150,
                'profitPercentage' => 6,
                'loss' => 0,
                'lossPercentage' => 0
            ],
            [
                'title' => 'Investment Gamma',
                'amount' => 500,
                'currencySymbol' => '$',
                'profit' => 0,
                'profitPercentage' => 0,
                'loss' => 20,
                'lossPercentage' => 4
            ]
        ];

        // Add e-commerce specific data
        $viewData['lastWeekOrder'] = [
            'name' => 'Last Week Order',
            'data' => [44, 55, 57, 56, 61, 10],
            'total' => '10k+',
            'percentage' => 100,
            'preSymbol' => '-'
        ];

        $viewData['lastWeekProfit'] = [
            'name' => 'Last Week Profit',
            'data' => [44, 55, 57, 56, 61, 10],
            'total' => '10k+',
            'percentage' => 100,
            'preSymbol' => '+'
        ];

        $viewData['lastWeekOverview'] = [
            'labels' => ["Success", "Return"],
            'data' => [60, 40],
            'title' => 'Profit',
            'amount' => '650k+'
        ];

        // Add the same data to analyticChartData for backward compatibility
        $viewData['analyticChartData'] = array_merge($viewData['analyticChartData'], [
            'cashFlow' => $viewData['cashFlow'],
            'productGrowthOverview' => $viewData['productGrowthOverview'],
            'thisYearGrowth' => $viewData['thisYearGrowth'],
            'investmentAmount' => $viewData['investmentAmount'],
            'lastWeekOrder' => $viewData['lastWeekOrder'],
            'lastWeekProfit' => $viewData['lastWeekProfit'],
            'lastWeekOverview' => $viewData['lastWeekOverview']
        ]);

        // Add any remaining data needed by the view
        $viewData['lastWeekOverview']['percentage'] = 0.02;

        // Add load average information
        $loadAverage = ['N/A', 'N/A', 'N/A'];
        
        try {
            // shell_exec is generally preferred for capturing direct output
            // Make sure the 'uptime' command is available and allowed in your environment
            $uptimeOutput = shell_exec('uptime');
            if ($uptimeOutput && preg_match('/load average: ([\d.]+),\s*([\d.]+),\s*([\d.]+)/', $uptimeOutput, $matches)) {
                // Capture the three load average values
                $loadAverage = array_map('trim', [$matches[1], $matches[2], $matches[3]]);
            } else if ($uptimeOutput && preg_match('/load average: (.*)/', $uptimeOutput, $matches)) {
                // Fallback if the format is slightly different (e.g., only one value or unexpected format)
                $loadAverage = array_map('trim', explode(',', $matches[1]));
                // Fill with N/A if there are less than 3 values
                while(count($loadAverage) < 3) $loadAverage[] = 'N/A';
                $loadAverage = array_slice($loadAverage, 0, 3); // Ensure there are only 3 values
            }
        } catch (\Throwable $e) { // Catch any errors (including if shell_exec is disabled)
            \Illuminate\Support\Facades\Log::warning("Could not execute or parse 'uptime' command: " . $e->getMessage());
            // $loadAverage already has the default value ['N/A', 'N/A', 'N/A']
        }

        
        // Add load average to the view data
        $viewData['loadAverage'] = $loadAverage;

        // Add top customers data
        $viewData['topCustomers'] = [
            [
                'serialNo' => 1, 
                'name' => 'Elena García', 
                'totalPoint' => 50.5, 
                'progressBarPoint' => 50,
                'progressBarColor' => 'green', 
                'backgroundColor' => 'sky', 
                'isMvpUser' => true, 
                'photo' => '/images/customer1.png',
            ],
            [
                'serialNo' => 2, 
                'name' => 'Carlos Rodríguez', 
                'totalPoint' => 45.2, 
                'progressBarPoint' => 45,
                'progressBarColor' => 'sky', 
                'backgroundColor' => 'orange', 
                'isMvpUser' => false, 
                'photo' => '/images/customer2.png',
            ],
            [
                'serialNo' => 3, 
                'name' => 'Ana Martínez', 
                'totalPoint' => 40.8, 
                'progressBarPoint' => 41,
                'progressBarColor' => 'orange', 
                'backgroundColor' => 'green', 
                'isMvpUser' => false, 
                'photo' => '/images/customer3.png',
            ],
            [
                'serialNo' => 4, 
                'name' => 'Javier López', 
                'totalPoint' => 38.0, 
                'progressBarPoint' => 38,
                'progressBarColor' => 'green', 
                'backgroundColor' => 'sky', 
                'isMvpUser' => false, 
                'photo' => '/images/customer4.png',
            ],
            [
                'serialNo' => 5, 
                'name' => 'Sofía Fernández', 
                'totalPoint' => 35.5, 
                'progressBarPoint' => 36,
                'progressBarColor' => 'sky', 
                'backgroundColor' => 'orange', 
                'isMvpUser' => false, 
                'photo' => '/images/customer5.png',
            ],
        ];

        // Add recent orders data
        $viewData['ecommerceOrders'] = [
            [
                'companyName' => 'TecnoSoluciones SL', 
                'email' => 'contacto@tecnosoluciones.es', 
                'productType' => 'Hardware',
                'invoiceNo' => 'INV-2025-001', 
                'amount' => 1250.75, 
                'currencySymbol' => '€', 
                'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Libros del Saber', 
                'email' => 'pedidos@librosdelsaber.com', 
                'productType' => 'Libros',
                'invoiceNo' => 'INV-2025-002', 
                'amount' => 85.50, 
                'currencySymbol' => '€', 
                'paymentStatus' => 'due',
            ]
        ];
        
        // Add the same data to analyticChartData for backward compatibility
        $viewData['analyticChartData'] = array_merge($viewData['analyticChartData'], [
            'loadAverage' => $viewData['loadAverage'],
            'topCustomers' => $viewData['topCustomers'],
            'ecommerceOrders' => $viewData['ecommerceOrders']
        ]);
        
        // Add more ecommerce orders if needed
        $viewData['ecommerceOrders'][] = [
            'companyName' => 'Moda Actual', 
            'email' => 'ventas@modaactual.net', 
            'productType' => 'Ropa',
            'invoiceNo' => 'INV-2025-003', 
            'amount' => 320.00, 
            'currencySymbol' => '€', 
            'paymentStatus' => 'paid'
        ];
        
        // Update the analyticChartData with the latest ecommerce orders
        $viewData['analyticChartData']['ecommerceOrders'] = $viewData['ecommerceOrders'];
        
        // Add more ecommerce orders
        $viewData['ecommerceOrders'][] = [
            'companyName' => 'Consultoría Global', 
            'email' => 'info@consultoriaglobal.org', 
            'productType' => 'Servicios',
            'invoiceNo' => 'INV-2025-004', 
            'amount' => 5000.00, 
            'currencySymbol' => '€', 
            'paymentStatus' => 'paid'
        ];
        
        $viewData['ecommerceOrders'][] = [
            'companyName' => 'TecnoSoluciones SL', 
            'email' => 'contacto@tecnosoluciones.es', 
            'productType' => 'Software',
            'invoiceNo' => 'INV-2025-005', 
            'amount' => 750.00, 
            'currencySymbol' => '€', 
            'paymentStatus' => 'due'
        ];

        // Update the analyticChartData with the latest ecommerce orders
        $viewData['analyticChartData']['ecommerceOrders'] = $viewData['ecommerceOrders'];
        
        // --- User Permissions (from Ecommerce Logic) ---
        $user = \Illuminate\Support\Facades\Auth::user(); // Get authenticated user
        $allowedButtons = []; // Array to store allowed buttons
        $allowedAddButtons = false; // Flag for add permission (e.g., time control)
        
        // Check if there is an authenticated user before accessing their methods/properties
        if ($user) {
            // Try to get allowed buttons. Assumes the method exists on the User model.
            if (method_exists($user, 'getAllowedButtons')) {
                $allowedButtons = $user->getAllowedButtons();
            } else {
                \Illuminate\Support\Facades\Log::warning("Method getAllowedButtons() not found on User model for user ID: " . $user->id);
                // You can assign default permissions or leave it empty if the method doesn't exist
            }

            // Check if the 'time_control_enable' property exists and assign it. Use ?? for default false value.
            $allowedAddButtons = $user->time_control_enable ?? false;
        } else {
            \Illuminate\Support\Facades\Log::info("Unified dashboard accessed by non-authenticated user.");
            // You can define default values for non-authenticated users if needed
        }
        
        // Add user permissions to view data
        $viewData['allowedButtons'] = $allowedButtons;
        $viewData['allowedAddButtons'] = $allowedAddButtons;
        
        // Add users data if not already added
        if (!isset($viewData['users'])) {
            $viewData['users'] = class_exists('App\Models\User') ? 
                \App\Models\User::latest()->take(5)->get() : collect();
        }
        
        // Add the same data to analyticChartData for backward compatibility
        $viewData['analyticChartData'] = array_merge($viewData['analyticChartData'], [
            'allowedButtons' => $allowedButtons,
            'allowedAddButtons' => $allowedAddButtons,
            'users' => $viewData['users']
        ]);
        
        // Add recent orders data to view data
        $viewData['recentOrders'] = $viewData['ecommerceOrders']; // Using ecommerce orders as recent orders
        
        // Add any additional data needed by the view
        $viewData['data'] = collect($viewData['analyticChartData']);
        
        return view('dashboards.unified', $viewData);
    }
     public function analyticDashboard()
    {
        $chartData = [
            'yearlyRevenue' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'revenue' => [350, 500, 950, 700, 900],
                'total' => 3500,
                'currencySymbol' => '$',
            ],
            'productSold' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'quantity' => [800, 600, 1000, 800, 900],
                'total' => 4000,
            ],
            'growth' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'perYearRate' => [10, 20, 30, 40, 100],
                'total' => 10,
                'preSymbol' => '+',
                'postSymbol' => '%',
            ],
            'revenueReport' => [
                'month' => ["Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct"],
                'revenue' => [
                    'title' => 'Revenue',
                    'data' => [76, 85, 101, 98, 87, 105, 91, 114, 94],
                ],
                'netProfit' => [
                    'title' => 'Net Profit',
                    'data' => [35, 41, 36, 26, 45, 48, 52, 53, 41],
                ],
                'cashFlow' => [
                    'title' => 'Cash Flow',
                    'data' => [44, 55, 57, 56, 61, 58, 63, 60, 66],
                ],
            ],
            'productGrowthOverview' => [
                'productNames' => ["Books", "Pens", "Pencils", "Box"],
                'data' => [88, 77, 66, 55],
            ],
            'thisYearGrowth' => [
                'label' => ['Yearly Growth'],
                'data' => [66],
            ],
            'investmentAmount' => [
                [
                    'title' => 'Investment',
                    'amount' => 1000,
                    'currencySymbol' => '$',
                    'profit' => 10,
                    'profitPercentage' => 50,
                    'loss' => 0,
                    'lossPercentage' => 0,
                ],
                [
                    'title' => 'Investment',
                    'amount' => 1000,
                    'currencySymbol' => '$',
                    'profit' => 10,
                    'profitPercentage' => 50,
                    'loss' => 0,
                    'lossPercentage' => 0,
                ],
                [
                    'title' => 'Investment',
                    'amount' => 1000,
                    'currencySymbol' => '$',
                    'profit' => 0,
                    'profitPercentage' => 0,
                    'loss' => 20,
                    'lossPercentage' => 30,
                ]
            ],
            'users' => User::latest()->paginate(5),
        ];
            // Obtener tiempo de carga del sistema
            $uptime = exec('uptime');
            preg_match('/load average: (.*)/', $uptime, $matches);
            $loadAverage = explode(',', $matches[1]);

            // Agregar tiempo de carga a $chartData
            $chartData['loadAverage'] = $loadAverage;

        return view('dashboards.analytic', [
            'pageTitle' => 'Analytic Dashboard',
            'data' => collect($chartData),
        ]);
    }

    /**
     * Ecommerce Dashboard
     */
    public function ecommerceDashboard()
    {
        $chartData = [
            'revenueReport' => [
                'month' => ["Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct"],
                'revenue' => [
                    'title' => 'Revenue',
                    'data' => [76, 85, 101, 98, 87, 105, 91, 114, 94],
                ],
                'netProfit' => [
                    'title' => 'Net Profit',
                    'data' => [35, 41, 36, 26, 45, 48, 52, 53, 41],
                ],
                'cashFlow' => [
                    'title' => 'Cash Flow',
                    'data' => [44, 55, 57, 56, 61, 58, 63, 60, 66],
                ],
            ],
            'revenue' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'data' => [350, 500, 950, 700, 100],
                'total' => 4000,
                'currencySymbol' => '$',
            ],
            'productSold' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'quantity' => [800, 600, 1000, 50, 100],
                'total' => 100,
            ],
            'growth' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'perYearRate' => [10, 20, 30, 40, 10],
                'total' => 10,
                'preSymbol' => '+',
                'postSymbol' => '%',
            ],
            'lastWeekOrder' => [
                'name' => 'Last Week Order',
                'data' => [44, 55, 57, 56, 61, 10],
                'total' => '10k+',
                'percentage' => 100,
                'preSymbol' => '-',
            ],
            'lastWeekProfit' => [
                'name' => 'Last Week Profit',
                'data' => [44, 55, 57, 56, 61, 10],
                'total' => '10k+',
                'percentage' => 100,
                'preSymbol' => '+',
            ],
            'lastWeekOverview' => [
                'labels' => ["Success", "Return"],
                'data' => [60, 40],
                'title' => 'Profit',
                'amount' => '650k+',
                'percentage' => 0.02,
            ],
        ];
        $topCustomers = [
            [
                'serialNo' => 1,
                'name' => 'Danniel Smith',
                'totalPoint' => 50.5,
                'progressBarPoint' => 50,
                'progressBarColor' => 'green',
                'backgroundColor' => 'sky',
                'isMvpUser' => true,
                'photo' => '/images/customer.png',
            ],
            [
                'serialNo' => 2,
                'name' => 'Danniel Smith',
                'totalPoint' => 50.5,
                'progressBarPoint' => 50,
                'progressBarColor' => 'sky',
                'backgroundColor' => 'orange',
                'isMvpUser' => false,
                'photo' => '/images/customer.png',
            ],
            [
                'serialNo' => 3,
                'name' => 'Danniel Smith',
                'totalPoint' => 50.5,
                'progressBarPoint' => 50,
                'progressBarColor' => 'orange',
                'backgroundColor' => 'green',
                'isMvpUser' => false,
                'photo' => '/images/customer.png',
            ],
            [
                'serialNo' => 4,
                'name' => 'Danniel Smith',
                'totalPoint' => 50.5,
                'progressBarPoint' => 50,
                'progressBarColor' => 'green',
                'backgroundColor' => 'sky',
                'isMvpUser' => true,
                'photo' => '/images/customer.png',
            ],
            [
                'serialNo' => 5,
                'name' => 'Danniel Smith',
                'totalPoint' => 50.5,
                'progressBarPoint' => 50,
                'progressBarColor' => 'sky',
                'backgroundColor' => 'orange',
                'isMvpUser' => false,
                'photo' => '/images/customer.png',
            ],
            [
                'serialNo' => 6,
                'name' => 'Danniel Smith',
                'totalPoint' => 50.5,
                'progressBarPoint' => 50,
                'progressBarColor' => 'orange',
                'backgroundColor' => 'green',
                'isMvpUser' => false,
                'photo' => '/images/customer.png',
            ],
        ];
        $recentOrders = [
            [
                'companyName' => 'Biffco Enterprises Ltd.',
                'email' => 'Biffco@biffco.com',
                'productType' => 'Technology',
                'invoiceNo' => 'INV-0001',
                'amount' => 1000,
                'currencySymbol' => '$',
                'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Biffco Enterprises Ltd.',
                'email' => 'Biffco@biffco.com',
                'productType' => 'Technology',
                'invoiceNo' => 'INV-0001',
                'amount' => 1000,
                'currencySymbol' => '$',
                'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Biffco Enterprises Ltd.',
                'email' => 'Biffco@biffco.com',
                'productType' => 'Technology',
                'invoiceNo' => 'INV-0001',
                'amount' => 1000,
                'currencySymbol' => '$',
                'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Biffco Enterprises Ltd.',
                'email' => 'Biffco@biffco.com',
                'productType' => 'Technology',
                'invoiceNo' => 'INV-0001',
                'amount' => 1000,
                'currencySymbol' => '$',
                'paymentStatus' => 'due',
            ],
            [
                'companyName' => 'Biffco Enterprises Ltd.',
                'email' => 'Biffco@biffco.com',
                'productType' => 'Technology',
                'invoiceNo' => 'INV-0001',
                'amount' => 1000,
                'currencySymbol' => '$',
                'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Biffco Enterprises Ltd.',
                'email' => 'Biffco@biffco.com',
                'productType' => 'Technology',
                'invoiceNo' => 'INV-0001',
                'amount' => 1000,
                'currencySymbol' => '$',
                'paymentStatus' => 'due',
            ],
        ];

        $user = Auth::user();
        $allowedButtons = $user->getAllowedButtons();



        return view('dashboards.ecommerce', [
            'pageTitle' => 'Ecommerce Dashboard',
            'data' => $chartData,
            'topCustomers' => $topCustomers,
            'recentOrders' => $recentOrders,
            'allowedButtons' => $allowedButtons,
            'allowedAddButtons' => $user->time_control_enable

        ]);
    }

    public function getTimeControlSection() {
        $user = Auth::user();
        if (!$user) {
            // Devuelve HTML indicando no autenticado o un error JSON
            return response()->json(['html' => '<p class="text-danger-500">Usuario no autenticado.</p>'], 401);
        }

        try {
            // Lógica para obtener botones permitidos (igual que en unifiedDashboard)
            $allowedButtons = $user->getAllowedButtons();
            $allowedAddButtons = $user->time_control_enable ?? false;

            // Log para depuración
            \Log::info('getTimeControlSection - Datos obtenidos', [
                'userId' => $user->id,
                'allowedButtons' => $allowedButtons,
                'allowedAddButtons' => $allowedAddButtons
            ]);

            // Renderiza la vista parcial con los datos actuales
            $html = view('partials._control_horario_buttons', compact('allowedButtons', 'allowedAddButtons'))->render();

            // Devuelve el HTML renderizado como JSON
            return response()->json(['html' => $html, 'success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error en getTimeControlSection: ' . $e->getMessage(), [
                'userId' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'html' => '<p class="text-danger-500">Error al cargar los botones: ' . $e->getMessage() . '</p>',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene el total de ventas
     */
    protected function getTotalSales()
    {
        $result = DB::table('invoices')
            ->select(
                DB::raw('COUNT(invoices.id) as total_invoices'),
                DB::raw('COALESCE(SUM(invoices.total_amount), 0) as total_amount')
            )
            ->where('invoices.status', 'paid')
            ->where('invoices.invoice_date', '>=', now()->subYear())
            ->first();

        return [
            'total_invoices' => (int) ($result->total_invoices ?? 0),
            'total_amount' => (float) ($result->total_amount ?? 0)
        ];
    }
    
    /**
     * Obtiene la actividad reciente
     */
    protected function getRecentActivities($limit = 5)
    {
        // Obtener actividades de facturas
        $invoiceActivities = DB::table('invoices')
            ->select(
                'id',
                'invoice_number',
                'created_at',
                'status',
                DB::raw("'invoice' as type")
            )
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Obtener actividades de presupuestos
        $quoteActivities = DB::table('quotes')
            ->select(
                'id',
                'quote_number as invoice_number',
                'created_at',
                'status',
                DB::raw("'quote' as type")
            )
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Unir y ordenar todas las actividades
        return $invoiceActivities->union($quoteActivities)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                $activity->description = $this->getActivityDescription($activity);
                $activity->time_ago = Carbon::parse($activity->created_at)->diffForHumans();
                return (array) $activity;
            });
    }
    
    /**
     * Obtiene la descripción de la actividad
     */
    protected function getActivityDescription($activity)
    {
        if ($activity->type === 'invoice') {
            return __('Factura :invoice creada', ['invoice' => $activity->invoice_number]);
        } elseif ($activity->type === 'quote') {
            return __('Presupuesto :quote creado', ['quote' => $activity->invoice_number]);
        }
        
        return '';
    }
    
    /**
     * Obtiene los pedidos recientes
     */
    protected function getRecentOrders($limit = 5)
    {
        // Obtener facturas recientes
        $invoices = Invoice::with('client')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'type' => 'invoice',
                    'client_name' => $invoice->client ? $invoice->client->name : 'Cliente eliminado',
                    'amount' => $invoice->total_amount,
                    'date' => $invoice->created_at->format('d/m/Y'),
                    'sort_date' => $invoice->created_at, // Keep the original datetime for sorting
                    'status' => $this->getStatusBadge($invoice->status)
                ];
            })->toArray(); // Convert to array
            
        // Obtener presupuestos recientes
        $quotes = Quote::with('client')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($quote) {
                return [
                    'id' => $quote->id,
                    'number' => $quote->quote_number,
                    'type' => 'quote',
                    'client_name' => $quote->client ? $quote->client->name : 'Cliente eliminado',
                    'amount' => $quote->total_amount,
                    'date' => $quote->created_at->format('d/m/Y'),
                    'sort_date' => $quote->created_at, // Keep the original datetime for sorting
                    'status' => $this->getStatusBadge($quote->status, true)
                ];
            })->toArray(); // Convert to array
            
        // Combinar arrays
        $combined = array_merge($invoices, $quotes);
        
        // Ordenar por fecha de creación (más reciente primero)
        usort($combined, function($a, $b) {
            return $b['sort_date'] <=> $a['sort_date'];
        });
        
        // Tomar los primeros $limit elementos
        return array_slice($combined, 0, $limit);
    }
    
    /**
     * Obtiene el badge de estado
     */
    protected function getStatusBadge($status, $isQuote = false)
    {
        $statuses = $isQuote 
            ? [
                'draft' => ['class' => 'bg-warning-500', 'text' => 'Borrador'],
                'sent' => ['class' => 'bg-info-500', 'text' => 'Enviado'],
                'accepted' => ['class' => 'bg-success-500', 'text' => 'Aceptado'],
                'rejected' => ['class' => 'bg-danger-500', 'text' => 'Rechazado'],
                'expired' => ['class' => 'bg-secondary-500', 'text' => 'Expirado'],
            ]
            : [
                'draft' => ['class' => 'bg-warning-500', 'text' => 'Borrador'],
                'sent' => ['class' => 'bg-info-500', 'text' => 'Enviada'],
                'paid' => ['class' => 'bg-success-500', 'text' => 'Pagada'],
                'overdue' => ['class' => 'bg-danger-500', 'text' => 'Vencida'],
                'cancelled' => ['class' => 'bg-secondary-500', 'text' => 'Cancelada'],
            ];
            
        $statusData = $statuses[$status] ?? ['class' => 'bg-gray-500', 'text' => ucfirst($status)];
        
        return [
            'class' => $statusData['class'],
            'text' => $statusData['text']
        ];
    }
}
