<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View; // Importa la fachada View
use Illuminate\Support\Facades\Log; // Importa Log para registrar errores si es necesario

class HomeController extends Controller
{

    /**
     * Analytic Dashboard
     */
    public function unifiedDashboard(Request $request): \Illuminate\Contracts\View\View
    {
        // --- Data primarily from Analytic Dashboard Logic ---
        $analyticChartData = [
            'yearlyRevenue' => [
                'year' => [1991, 1992, 1993, 1994, 1995],
                'revenue' => [350, 500, 950, 700, 900],
                'total' => 3500, // Considerar calcular dinámicamente si los datos no son estáticos
                'currencySymbol' => '$',
            ],
            'productSold' => [ // Prioritized from Analytic data
                'year' => [1991, 1992, 1993, 1994, 1995],
                'quantity' => [800, 600, 1000, 800, 900],
                'total' => 4000, // Considerar calcular dinámicamente
            ],
            'growth' => [ // Prioritized from Analytic data
                'year' => [1991, 1992, 1993, 1994, 1995],
                'perYearRate' => [10, 20, 30, 40, 100],
                'total' => 10, // Este 'total' parece representar una tasa o un valor específico, no una suma. Revisar lógica.
                'preSymbol' => '+',
                'postSymbol' => '%',
            ],
            'revenueReport' => [ // Identical in both original methods
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
                // Ejemplo de datos de inversión
                [
                    'title' => 'Investment Alpha', 'amount' => 1000, 'currencySymbol' => '$',
                    'profit' => 10, 'profitPercentage' => 50, 'loss' => 0, 'lossPercentage' => 0,
                ],
                [
                    'title' => 'Investment Beta', 'amount' => 2500, 'currencySymbol' => '$',
                    'profit' => 150, 'profitPercentage' => 6, 'loss' => 0, 'lossPercentage' => 0,
                ],
                [
                    'title' => 'Investment Gamma', 'amount' => 500, 'currencySymbol' => '$',
                    'profit' => 0, 'profitPercentage' => 0, 'loss' => 20, 'lossPercentage' => 4,
                ]
            ],
        ];

        // --- Data Specific to Ecommerce Dashboard Logic ---
        $ecommerceSpecificChartData = [
            'lastWeekOrder' => [
                'name' => 'Last Week Order',
                'data' => [44, 55, 57, 56, 61, 10], // Datos de ejemplo
                'total' => '10k+', // Valor de ejemplo
                'percentage' => 100, // Valor de ejemplo
                'preSymbol' => '-', // Símbolo de ejemplo
            ],
            'lastWeekProfit' => [
                'name' => 'Last Week Profit',
                'data' => [44, 55, 57, 56, 61, 10], // Datos de ejemplo
                'total' => '10k+', // Valor de ejemplo
                'percentage' => 100, // Valor de ejemplo
                'preSymbol' => '+', // Símbolo de ejemplo
            ],
            'lastWeekOverview' => [
                'labels' => ["Success", "Return"],
                'data' => [60, 40], // Datos de ejemplo
                'title' => 'Profit',
                'amount' => '650k+', // Valor de ejemplo
                'percentage' => 0.02, // Valor de ejemplo
            ],
            // Las claves 'revenue', 'productSold', 'growth' ya están incluidas desde $analyticChartData
        ];

        // --- Combine Chart Data ---
        // Fusiona los datos específicos de ecommerce en la estructura principal
        $combinedChartData = array_merge($analyticChartData, $ecommerceSpecificChartData);

        // --- Standalone Data Structures & System Info ---

        // System Load Average (from Analytic Logic)
        $loadAverage = ['N/A', 'N/A', 'N/A']; // Default value in case command fails
        try {
            // shell_exec es generalmente preferible para capturar salida directa
            // Asegúrate de que el comando 'uptime' está disponible y permitido en tu entorno
            $uptimeOutput = shell_exec('uptime');
            if ($uptimeOutput && preg_match('/load average: ([\d.]+),\s*([\d.]+),\s*([\d.]+)/', $uptimeOutput, $matches)) {
                 // Captura los tres valores de carga promedio
                $loadAverage = array_map('trim', [$matches[1], $matches[2], $matches[3]]);
            } else if ($uptimeOutput && preg_match('/load average: (.*)/', $uptimeOutput, $matches)) {
                 // Fallback por si el formato es ligeramente diferente (ej. solo un valor o formato inesperado)
                 $loadAverage = array_map('trim', explode(',', $matches[1]));
                 // Rellena con N/A si no hay 3 valores
                 while(count($loadAverage) < 3) $loadAverage[] = 'N/A';
                 $loadAverage = array_slice($loadAverage, 0, 3); // Asegura que solo haya 3
            }
        } catch (\Throwable $e) { // Captura Throwable para errores más generales (incluyendo si shell_exec está desactivado)
            Log::warning("Could not execute or parse 'uptime' command: " . $e->getMessage());
            // $loadAverage ya tiene el valor por defecto ['N/A', 'N/A', 'N/A']
        }
        // Add load average to the main data array passed to the view
        $combinedChartData['loadAverage'] = $loadAverage;


        // Latest Users (from Analytic Logic)
        // Obtiene los 5 usuarios más recientes con paginación
        $users = User::latest()->paginate(5);

        // Top Customers (from Ecommerce Logic)
        // Datos estáticos de ejemplo para los mejores clientes
        $topCustomers = [
            [
                'serialNo' => 1, 'name' => 'Elena García', 'totalPoint' => 50.5, 'progressBarPoint' => 50,
                'progressBarColor' => 'green', 'backgroundColor' => 'sky', 'isMvpUser' => true, 'photo' => '/images/customer1.png', // Usa rutas de imagen reales
            ],
            [
                'serialNo' => 2, 'name' => 'Carlos Rodríguez', 'totalPoint' => 45.2, 'progressBarPoint' => 45,
                'progressBarColor' => 'sky', 'backgroundColor' => 'orange', 'isMvpUser' => false, 'photo' => '/images/customer2.png',
            ],
            [
                'serialNo' => 3, 'name' => 'Ana Martínez', 'totalPoint' => 40.8, 'progressBarPoint' => 41,
                'progressBarColor' => 'orange', 'backgroundColor' => 'green', 'isMvpUser' => false, 'photo' => '/images/customer3.png',
            ],
             [
                'serialNo' => 4, 'name' => 'Javier López', 'totalPoint' => 38.0, 'progressBarPoint' => 38,
                'progressBarColor' => 'green', 'backgroundColor' => 'sky', 'isMvpUser' => false, 'photo' => '/images/customer4.png',
            ],
             [
                'serialNo' => 5, 'name' => 'Sofía Fernández', 'totalPoint' => 35.5, 'progressBarPoint' => 36,
                'progressBarColor' => 'sky', 'backgroundColor' => 'orange', 'isMvpUser' => false, 'photo' => '/images/customer5.png',
            ],
        ];

        // Recent Orders (from Ecommerce Logic)
        // Datos estáticos de ejemplo para pedidos recientes
        $recentOrders = [
            [
                'companyName' => 'TecnoSoluciones SL', 'email' => 'contacto@tecnosoluciones.es', 'productType' => 'Hardware',
                'invoiceNo' => 'INV-2025-001', 'amount' => 1250.75, 'currencySymbol' => '€', 'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Libros del Saber', 'email' => 'pedidos@librosdelsaber.com', 'productType' => 'Libros',
                'invoiceNo' => 'INV-2025-002', 'amount' => 85.50, 'currencySymbol' => '€', 'paymentStatus' => 'due',
            ],
            [
                'companyName' => 'Moda Actual', 'email' => 'ventas@modaactual.net', 'productType' => 'Ropa',
                'invoiceNo' => 'INV-2025-003', 'amount' => 320.00, 'currencySymbol' => '€', 'paymentStatus' => 'paid',
            ],
            [
                'companyName' => 'Consultoría Global', 'email' => 'info@consultoriaglobal.org', 'productType' => 'Servicios',
                'invoiceNo' => 'INV-2025-004', 'amount' => 5000.00, 'currencySymbol' => '€', 'paymentStatus' => 'paid',
            ],
             [
                'companyName' => 'TecnoSoluciones SL', 'email' => 'contacto@tecnosoluciones.es', 'productType' => 'Software',
                'invoiceNo' => 'INV-2025-005', 'amount' => 750.00, 'currencySymbol' => '€', 'paymentStatus' => 'due',
            ],
        ];

        // --- User Permissions (from Ecommerce Logic) ---
        $user = Auth::user(); // Obtiene el usuario autenticado
        $allowedButtons = []; // Array para almacenar botones permitidos
        $allowedAddButtons = false; // Flag para permiso de añadir (ej: control de tiempo)

        // Verifica si hay un usuario autenticado antes de acceder a sus métodos/propiedades
        if ($user) {
            // Intenta obtener los botones permitidos. Asume que el método existe en el modelo User.
            if (method_exists($user, 'getAllowedButtons')) {
                $allowedButtons = $user->getAllowedButtons();
            } else {
                Log::warning("Method getAllowedButtons() not found on User model for user ID: " . $user->id);
                // Puedes asignar permisos por defecto o dejarlo vacío si el método no existe
            }

            // Verifica si la propiedad 'time_control_enable' existe y la asigna. Usa ?? para valor por defecto false.
            $allowedAddButtons = $user->time_control_enable ?? false;
        } else {
             Log::info("Unified dashboard accessed by non-authenticated user.");
             // Puedes definir valores por defecto para usuarios no autenticados si es necesario
        }


        // --- Return Combined Data to the View ---
        // Retorna la vista 'dashboards.unified' con todos los datos compilados
        return view('dashboards.unified', [
            'pageTitle' => 'Dashboard',        // Título de la página para la vista
            'data' => collect($combinedChartData),      // Datos principales (gráficos, etc.), como colección
            'users' => $users,                          // Lista paginada de usuarios
            'topCustomers' => $topCustomers,            // Lista de mejores clientes
            'recentOrders' => $recentOrders,            // Lista de pedidos recientes
            'allowedButtons' => $allowedButtons,        // Botones permitidos para el usuario
            'allowedAddButtons' => $allowedAddButtons,  // Permiso específico de 'añadir'
        ]);
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
            return response()->json(['html' => '<p class=\"text-danger-500\">Usuario no autenticado.</p>'], 401);
        }

        // Lógica para obtener botones permitidos (igual que en unifiedDashboard)
        $allowedButtons = $user->getAllowedButtons();
        $allowedAddButtons = $user->time_control_enable ?? false;

        // Renderiza la vista parcial con los datos actuales
        $html = view('partials._control_horario_buttons', compact('allowedButtons', 'allowedAddButtons'))->render();

        // Devuelve el HTML renderizado como JSON
        return response()->json(['html' => $html]);
    }

    // No olvides definir la ruta GET en routes/web.php
    // Route::get('/get-time-control-section', [HomeController::class, 'getTimeControlSection'])->middleware('auth');

}
