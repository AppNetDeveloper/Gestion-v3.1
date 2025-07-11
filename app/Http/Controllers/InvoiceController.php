<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Project;
use App\Models\InvoiceItem;
use App\Models\Discount;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\VeriFactuService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InvoiceEmail;
use App\Mail\InvoiceSentMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Permitir acceso a super-admin sin verificar permisos específicos
        if ($user->hasRole('super-admin')) {
            $breadcrumbItems = [
                ['name' => __('Dashboard'), 'url' => '/dashboard'],
                ['name' => __('Invoices'), 'url' => route('invoices.index')],
            ];
            return view('invoices.index', compact('breadcrumbItems'));
        }
        
        // Para otros usuarios, verificar permisos normales
        if (!$user->can('invoices index') && !$user->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Invoices'), 'url' => route('invoices.index')],
        ];
        return view('invoices.index', compact('breadcrumbItems'));
    }

    /**
     * Fetch data for DataTables.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $isCustomer = $user->hasRole('customer');

            $query = Invoice::with(['client', 'quote', 'project'])->latest();

            if ($isCustomer) {
                $clientProfile = Client::where('user_id', $user->id)->first();
                if ($clientProfile) {
                    $query->where('client_id', $clientProfile->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif (!$user->can('invoices index')) {
                 $query->whereRaw('1 = 0');
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('client_name', function($row){
                    return $row->client ? $row->client->name : __('N/A');
                })
                ->addColumn('quote_number', function($row){
                    return $row->quote ? $row->quote->quote_number : __('N/A');
                })
                ->addColumn('project_title', function($row){
                    return $row->project ? $row->project->project_title : __('N/A');
                })
                ->editColumn('total_amount', function($row) {
                    return number_format($row->total_amount ?? 0, 2, ',', '.') . ' ' . ($row->currency ?? 'EUR');
                })
                ->editColumn('status', function($row) {
                    $status = ucfirst($row->status ?? 'draft');
                    $color = 'text-slate-500 dark:text-slate-400';
                     switch ($row->status) {
                        case 'sent': $color = 'text-blue-500 dark:text-blue-400'; break;
                        case 'paid': $color = 'text-green-500 dark:text-green-400'; break;
                        case 'partially_paid': $color = 'text-yellow-500 dark:text-yellow-400'; break;
                        case 'overdue': $color = 'text-red-500 dark:text-red-400'; break;
                        case 'cancelled': $color = 'text-gray-500 dark:text-gray-400'; break;
                        case 'draft': $color = 'text-orange-500 dark:text-orange-400'; break;
                    }
                    return "<span class='{$color} font-medium'>".__($status)."</span>";
                })
                ->editColumn('invoice_date', function ($row) {
                    return $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('d/m/Y') : '-';
                })
                ->editColumn('due_date', function ($row) {
                    return $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('d/m/Y') : '-';
                })
                ->addColumn('action', function($row) use ($user, $isCustomer){
                    $actions = '<div class="flex items-center justify-center space-x-1">';
                    $isOwner = $isCustomer && $row->client && $row->client->user_id == $user->id;

                    if ($user->can('invoices show') || ($isOwner && $user->can('invoices view_own'))) {
                        $actions .= '<a href="'.route('invoices.show', $row->id).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="'.__('View Invoice').'"><iconify-icon icon="heroicons:eye" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }
                    if ($user->can('invoices update') && !$isCustomer && !in_array($row->status, ['paid', 'cancelled'])) {
                         $actions .= '<a href="'.route('invoices.edit', $row->id).'" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="'.__('Edit Invoice').'"><iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }
                    if ($user->can('invoices delete') && !$isCustomer && !in_array($row->status, ['paid'])) {
                         $actions .= '<button class="deleteInvoice text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1" data-id="'.$row->id.'" title="'.__('Delete Invoice').'"><iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon></button>';
                    }
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
        return abort(403, 'Unauthorized action.');
    }

    /**
     * Calculate IRPF amount based on subtotal, discount and IRPF rate
     * 
     * @param float $subtotal
     * @param float $discountAmount
     * @param float $irpfRate
     * @return array [irpfAmount, subtotalAfterDiscount]
     */
    protected function calculateIrpfAmount($subtotal, $discountAmount, $irpfRate)
    {
        $irpfAmount = 0;
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        
        if ($irpfRate > 0) {
            $irpfAmount = $subtotalAfterDiscount * ($irpfRate / 100);
        }
        
        return [
            'irpf_amount' => $irpfAmount,
            'subtotal_after_discount' => $subtotalAfterDiscount
        ];
    }

    /**
     * Generate a sequential invoice number with year prefix
     * Format: YYYY-XXXX (e.g., 2025-0001)
     *
     * @return string
     */
    private function generateInvoiceNumber()
    {
        $currentYear = date('Y');
        $lastInvoice = Invoice::orderBy('id', 'desc')
                            ->where('invoice_number', 'LIKE', $currentYear.'-%')
                            ->first();
        
        // If no invoice exists for the current year, start with 1
        $nextNumber = 1;
        
        if ($lastInvoice) {
            // Extract the sequential number from the last invoice number
            if (preg_match('/^\d{4}-(\d+)$/', $lastInvoice->invoice_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            }
        }
        
        // Format: YYYY-XXXX (e.g., 2025-0001)
        return $currentYear . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        if (!Auth::user()->can('invoices create')) {
            abort(403, __('This action is unauthorized.'));
        }

        $clients = Client::orderBy('name')->get(['id', 'name', 'vat_rate']);
        $availableQuotes = Quote::where('status', 'accepted')
                                ->whereDoesntHave('invoices')
                                ->with('client:id,name')
                                ->orderBy('quote_number')
                                ->get(['id', 'quote_number', 'client_id', 'total_amount']);

        $availableProjects = Project::whereIn('status', ['completed', 'in_progress'])
                                    ->whereDoesntHave('invoices')
                                    ->with('client:id,name')
                                    ->orderBy('project_title')
                                    ->get(['id', 'project_title', 'client_id']);

        $discounts = Discount::where('is_active', true)->orderBy('name')->get();
        $services = Service::orderBy('name')->get(['id', 'name', 'default_price', 'unit', 'description']);
        $nextInvoiceNumber = $this->generateInvoiceNumber();
        
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Invoices'), 'url' => route('invoices.index')],
            ['name' => __('Create'), 'url' => route('invoices.create')],
        ];
        
        return view('invoices.create', compact(
            'breadcrumbItems', 
            'clients', 
            'availableQuotes', 
            'availableProjects', 
            'discounts', 
            'services',
            'nextInvoiceNumber'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Prepare invoice data from request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function prepareInvoiceData(Request $request)
    {
        $client = Client::findOrFail($request->input('client_id'));
        $clientVatRate = $client->vat_rate ?? config('app.vat_rate', 21);
        
        // Get IRPF from request or config
        $irpf = $request->filled('irpf') ? $request->input('irpf') : (float)env('INVOICE_IRPF', 0);

        $invoiceData = $request->only([
            'client_id', 'quote_id', 'project_id', 'invoice_number', 'invoice_date', 'due_date',
            'status', 'currency', 'payment_terms', 'notes_to_client', 'internal_notes',
            'subtotal', 'tax_amount', 'total_amount', 'irpf_amount'
        ]);
        
        $invoiceData['quote_id'] = $request->filled('quote_id') ? $request->input('quote_id') : null;
        $invoiceData['project_id'] = $request->filled('project_id') ? $request->input('project_id') : null;

        // Calculate discount_amount based on selected discount
        $calculatedDiscountAmount = 0;
        if ($request->filled('discount_id_invoice')) {
            $selectedDiscount = Discount::find($request->input('discount_id_invoice'));
            if ($selectedDiscount) {
                $subtotalForDiscount = $request->input('subtotal', 0);
                if ($selectedDiscount->type == 'percentage') {
                    $calculatedDiscountAmount = $subtotalForDiscount * ($selectedDiscount->value / 100);
                } else { // fixed_amount
                    $calculatedDiscountAmount = $selectedDiscount->value;
                }
                $calculatedDiscountAmount = min($subtotalForDiscount, $calculatedDiscountAmount);
            }
        }
        
        // Calculate IRPF amount
        $irpfAmount = 0;
        $subtotalAfterDiscount = $request->input('subtotal', 0) - $calculatedDiscountAmount;
        if ($irpf > 0) {
            $irpfAmount = $subtotalAfterDiscount * ($irpf / 100);
        }
        
        // Set invoice data
        $invoiceData['discount_id'] = $request->input('discount_id_invoice');
        $invoiceData['discount_amount'] = $calculatedDiscountAmount;
        $invoiceData['irpf'] = $irpf;
        $invoiceData['irpf_amount'] = $irpfAmount;
        
        // Calculate total_amount (subtotal - discount + tax - irpf)
        $invoiceData['total_amount'] = $subtotalAfterDiscount + $request->input('tax_amount', 0) - $irpfAmount;
        
        return $invoiceData;
    }
    
    /**
     * Prepare invoice data from request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('invoices create')) {
            abort(403, __('This action is unauthorized.'));
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])],
            'quote_id' => 'nullable|exists:quotes,id|unique:invoices,quote_id',
            'project_id' => 'nullable|exists:projects,id',
            'currency' => 'required|string|max:3',
            'discount_id_invoice' => 'nullable|exists:discounts,id',
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'irpf' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_terms' => 'nullable|string',
            'notes_to_client' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.create')
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create invoice. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $client = Client::findOrFail($request->input('client_id'));
            $clientVatRate = $client->vat_rate ?? config('app.vat_rate', 21);
            
            // Obtener el porcentaje de IRPF del formulario o del .env si está configurado
            $irpf = $request->filled('irpf') ? $request->input('irpf') : (float)env('INVOICE_IRPF', 0);

            $invoiceData = $request->only([
                'client_id', 'quote_id', 'project_id', 'invoice_number', 'invoice_date', 'due_date',
                'status', 'currency', 'payment_terms', 'notes_to_client', 'internal_notes',
                'subtotal', 'tax_amount', 'total_amount'
            ]);
            
            $invoiceData['quote_id'] = $request->filled('quote_id') ? $request->input('quote_id') : null;
            $invoiceData['project_id'] = $request->filled('project_id') ? $request->input('project_id') : null;

            // Calcular discount_amount basado en el discount_id_invoice seleccionado
            $calculatedDiscountAmount = 0;
            if ($request->filled('discount_id_invoice')) {
                $selectedDiscount = Discount::find($request->input('discount_id_invoice'));
                if ($selectedDiscount) {
                    $subtotalForDiscount = $request->input('subtotal', 0);
                    if ($selectedDiscount->type == 'percentage') {
                        $calculatedDiscountAmount = $subtotalForDiscount * ($selectedDiscount->value / 100);
                    } else { // fixed_amount
                        $calculatedDiscountAmount = $selectedDiscount->value;
                    }
                    $calculatedDiscountAmount = min($subtotalForDiscount, $calculatedDiscountAmount);
                }
            }
            
            // Calcular el monto de IRPF
            $irpfAmount = 0;
            $subtotalAfterDiscount = $request->input('subtotal', 0) - $calculatedDiscountAmount;
            if ($irpf > 0) {
                $irpfAmount = $subtotalAfterDiscount * ($irpf / 100);
            }
            
            // Asignar valores a los datos de la factura
            $invoiceData['discount_id'] = $request->input('discount_id_invoice');
            $invoiceData['discount_amount'] = $request->input('discount_amount', $calculatedDiscountAmount);
            $invoiceData["irpf_amount"] = $irpfAmount;
            $invoiceData['irpf'] = $irpf;
            
            // Ajustar el total_amount para incluir el IRPF (el IRPF se resta del total)
            $invoiceData['total_amount'] = $subtotalAfterDiscount + $request->input('tax_amount', 0) - $irpfAmount;

            // Generar ID único para Veri*factu
            $verifactuId = 'FAC-' . date('Ymd-His-') . strtoupper(uniqid());
            
            // Generar datos para el código QR según normativa española
            $qrData = [
                'id' => $verifactuId,
                'fecha' => now()->format('d-m-Y'),
                'total' => number_format($invoiceData['total_amount'], 2, ',', ''),
                'nif_emisor' => config('app.company_vat', ''),
                'nif_receptor' => $client->tax_number ?? '',
                'importe_base' => number_format($subtotalAfterDiscount, 2, ',', ''),
                'iva' => number_format($request->input('tax_amount', 0), 2, ',', ''),
            ];
            
            // Generar cadena para el QR
            $qrString = collect($qrData)->map(function($value, $key) {
                return "$key=$value";
            })->implode('|');
            
            // Generar código QR como SVG
            $qrCode = QrCode::format('svg')
                ->size(200)
                ->generate($qrString);
                
            // Añadir datos al array de la factura
            $invoiceData['verifactu_id'] = $verifactuId;
            $invoiceData['verifactu_qr_code_data'] = $qrCode;
            
            $invoice = Invoice::create($invoiceData);

            foreach ($request->input('items', []) as $itemData) {
                $itemSubtotal = ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0);
                $itemTaxRate = $itemData['tax_rate'] ?? $clientVatRate;
                $lineTaxAmount = $itemSubtotal * ($itemTaxRate / 100);
                $lineTotal = $itemSubtotal + $lineTaxAmount;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'item_subtotal' => $itemSubtotal,
                    'tax_rate' => $itemTaxRate,
                    'tax_amount_per_item' => $itemData['quantity'] ? ($lineTaxAmount / $itemData['quantity']) : 0,
                    'line_tax_total' => $lineTaxAmount,
                    'line_total' => $lineTotal,
                ]);
            }

            if ($invoice->quote_id) {
                $quote = Quote::find($invoice->quote_id);
                if ($quote) {
                    $quote->status = 'invoiced';
                    $quote->save();
                }
            }

            DB::commit();
            Log::info("Invoice #{$invoice->id} created.");
            return redirect()->route('invoices.show', $invoice->id)->with('success', __('Invoice created successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('invoices.create')
                        ->withInput()
                        ->with('error', __('An error occurred while creating the invoice.'));
        }
    }
    
    /**
     * Update the specified invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        
        // Log de depuración
        \Log::info('Verificando permisos para actualizar factura', [
            'user_id' => $user->id,
            'is_authenticated' => Auth::check(),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'invoice_status' => $invoice->status
        ]);
        
        // Verificar si es super-admin o tiene permiso para editar facturas
        $canEdit = $user->hasRole('super-admin') || 
                 ($user->can('invoices edit') && $invoice->status === 'draft');
        
        // Si no tiene permisos, denegar acceso
        if (!$canEdit) {
            $message = __('This action is unauthorized or invoice is not in draft status.');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $message], 403);
            }
            abort(403, $message);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => ['required', 'string', 'max:255', Rule::unique('invoices')->ignore($invoice->id)],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])],
            'quote_id' => ['nullable', 'exists:quotes,id', Rule::unique('invoices')->ignore($invoice->id)],
            'project_id' => 'nullable|exists:projects,id',
            'currency' => 'required|string|max:3',
            'discount_id_invoice' => 'nullable|exists:discounts,id',
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'irpf' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_terms' => 'nullable|string',
            'notes_to_client' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.edit', $invoice->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to update invoice. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $client = Client::findOrFail($request->input('client_id'));
            $clientVatRate = $client->vat_rate ?? config('app.vat_rate', 21);
            
            // Obtener el porcentaje de IRPF del formulario o del .env si está configurado
            $irpf = $request->filled('irpf') ? $request->input('irpf') : (float)env('INVOICE_IRPF', 0);

            $invoiceData = $request->only([
                'client_id', 'quote_id', 'project_id', 'invoice_number', 'invoice_date', 'due_date',
                'status', 'currency', 'payment_terms', 'notes_to_client', 'internal_notes',
                'subtotal', 'tax_amount', 'total_amount'
            ]);
            
            $invoiceData['quote_id'] = $request->filled('quote_id') ? $request->input('quote_id') : null;
            $invoiceData['project_id'] = $request->filled('project_id') ? $request->input('project_id') : null;

            // Calcular discount_amount basado en el discount_id_invoice seleccionado
            $calculatedDiscountAmount = 0;
            if ($request->filled('discount_id_invoice')) {
                $selectedDiscount = Discount::find($request->input('discount_id_invoice'));
                if ($selectedDiscount) {
                    $subtotalForDiscount = $request->input('subtotal', 0);
                    if ($selectedDiscount->type == 'percentage') {
                        $calculatedDiscountAmount = $subtotalForDiscount * ($selectedDiscount->value / 100);
                    } else { // fixed_amount
                        $calculatedDiscountAmount = $selectedDiscount->value;
                    }
                    $calculatedDiscountAmount = min($subtotalForDiscount, $calculatedDiscountAmount);
                }
            }
            
            // Calcular el monto de IRPF
            $irpfAmount = 0;
            $subtotalAfterDiscount = $request->input('subtotal', 0) - $calculatedDiscountAmount;
            if ($irpf > 0) {
                $irpfAmount = $subtotalAfterDiscount * ($irpf / 100);
            }
            
            // Asignar valores a los datos de la factura
            $invoiceData['discount_id'] = $request->input('discount_id_invoice');
            $invoiceData['discount_amount'] = $request->input('discount_amount', $calculatedDiscountAmount);
            $invoiceData["irpf_amount"] = $irpfAmount;
            $invoiceData['irpf'] = $irpf;
            
            // Ajustar el total_amount para incluir el IRPF (el IRPF se resta del total)
            $invoiceData['total_amount'] = $subtotalAfterDiscount + $request->input('tax_amount', 0) - $irpfAmount;

            // Actualizar los datos de la factura
            $invoice->update($invoiceData);

            // Eliminar items antiguos
            $invoice->items()->delete();

            // Crear nuevos items
            foreach ($request->input('items', []) as $itemData) {
                $itemSubtotal = ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0);
                $itemTaxRate = $itemData['tax_rate'] ?? $clientVatRate;
                $lineTaxAmount = $itemSubtotal * ($itemTaxRate / 100);
                $lineTotal = $itemSubtotal + $lineTaxAmount;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'item_subtotal' => $itemSubtotal,
                    'tax_rate' => $itemTaxRate,
                    'tax_amount_per_item' => $itemData['quantity'] ? ($lineTaxAmount / $itemData['quantity']) : 0,
                    'line_tax_total' => $lineTaxAmount,
                    'line_total' => $lineTotal,
                ]);
            }

            // Actualizar estado del presupuesto relacionado si es necesario
            if ($invoice->quote_id) {
                $quote = Quote::find($invoice->quote_id);
                if ($quote) {
                    $quote->status = 'invoiced';
                    $quote->save();
                }
            }

            DB::commit();
            Log::info("Invoice #{$invoice->id} updated.");
            return redirect()->route('invoices.show', $invoice->id)->with('success', __('Invoice updated successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating invoice: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('invoices.edit', $invoice->id)
                        ->withInput()
                        ->with('error', __('An error occurred while updating the invoice.'));
        }
    }

    /**
     * Display the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        $user = Auth::user();
        
        // Cargar relaciones necesarias incluyendo items.service
        $invoice->load([
            'client',
            'items.service',  // Asegurar que se cargue la relación service
            'payments',
            'quote',
            'project'
        ]);
        
        // Permitir acceso a super-admin sin verificar permisos específicos
        if ($user->hasRole('super-admin')) {
            return view('invoices.show', compact('invoice'));
        }
        
        // Verificar permisos normales para otros usuarios
        if (!$user->can('invoices view')) {
            // Si el usuario es el cliente, permitir la visualización
            if ($user->hasRole('client') && $user->client_id == $invoice->client_id) {
                return view('invoices.show', compact('invoice'));
            }
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => __('This action is unauthorized.')], 403);
            }
            
            abort(403, __('This action is unauthorized.'));
        }
        
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified invoice.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if (!$user->hasRole('super-admin') && (!$user->can('invoices edit') || $invoice->status !== 'draft')) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['error' => __('This action is unauthorized or invoice is not in draft status.')], 403);
            }
            abort(403, __('This action is unauthorized or invoice is not in draft status.'));
        }

        // Cargar datos necesarios
        $clients = Client::where('is_active', true)->get();
        $availableProjects = Project::with('client')
            ->where('status', 'active')
            ->orWhereNull('status')
            ->get();
        $services = Service::all();
        $discounts = Discount::where('is_active', true)->get();
        $currencies = config('currencies');
        $defaultCurrency = config('app.currency', 'EUR');
        $defaultVatRate = config('app.vat_rate', 21);
        $defaultIrpf = (float)env('INVOICE_IRPF', 0);

        // Cargar cotizaciones aprobadas que no tengan factura asignada o que sean la cotización actual
        $availableQuotes = Quote::where('status', 'approved')
            ->where(function($query) use ($invoice) {
                // Si hay una cotización asociada a esta factura, la incluimos
                if ($invoice->quote_id) {
                    $query->where('id', $invoice->quote_id);
                }
                
                // También incluimos cotizaciones aprobadas que no tengan factura
                $query->orWhereDoesntHave('invoices');
            })
            ->with(['client', 'project'])
            ->orderBy('quote_number')
            ->get();

        // Cargar la factura con todas sus relaciones necesarias
        $invoice->load([
            'client',
            'items.service',
            'payments',
            'quote',
            'project'
        ]);
        
        // Preparar los elementos de la factura para el JavaScript
        $invoiceItems = $invoice->items->map(function($item) {
            // Determinar el tipo de descuento basado en qué campo tiene valor
            $discountType = $item->line_discount_percentage !== null ? 'percentage' : 'fixed';
            $discountValue = $discountType === 'percentage' ? 
                (float)$item->line_discount_percentage : 
                (float)$item->line_discount_amount;
                
            return [
                'id' => $item->id,
                'service_id' => $item->service_id,
                'item_description' => $item->item_description,
                'quantity' => (float)$item->quantity,
                'unit_price' => (float)$item->unit_price,
                'tax_rate' => (float)$item->tax_rate,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'total' => (float)$item->line_total
            ];
        });
        
        // Pasar el valor de IRPF a la vista
        $irpf = old('irpf', $invoice->irpf ?? $defaultIrpf);
        
        return view('invoices.edit', compact(
            'invoice', 
            'clients', 
            'availableProjects', 
            'services', 
            'discounts',
            'currencies',
            'defaultCurrency',
            'defaultVatRate',
            'defaultIrpf',
            'irpf',
            'availableQuotes',
            'invoiceItems'
        ));
    }

    /**
     * Remove the specified invoice from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Invoice $invoice)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if (!$user->hasRole('super-admin') && !$user->can('invoices delete')) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This action is unauthorized.')
                ], 403);
            }
            abort(403, __('This action is unauthorized.'));
        }

        // No se permite eliminar facturas que no estén en estado borrador
        if ($invoice->status !== 'draft') {
            $message = __('Only draft invoices can be deleted.');
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 422);
            }
            return redirect()->route('invoices.index')
                ->with('error', $message);
        }

        DB::beginTransaction();
        try {
            // Si hay un presupuesto asociado, actualizar su estado
            if ($invoice->quote_id) {
                $quote = Quote::find($invoice->quote_id);
                if ($quote) {
                    $quote->status = 'draft'; // O el estado apropiado según tu lógica de negocio
                    $quote->save();
                }
            }

            // Eliminar pagos asociados
            if (method_exists($invoice, 'payments')) {
                $invoice->payments()->delete();
            }
            
            // Eliminar ítems
            if (method_exists($invoice, 'items')) {
                $invoice->items()->delete();
            }
            
            // Guardar el ID para el mensaje de log después de eliminar
            $invoiceId = $invoice->id;
            
            // Finalmente, eliminar la factura
            $invoice->delete();

            DB::commit();
            
            Log::info("Invoice #{$invoiceId} deleted by user #{$user->id}");
            
            // Respuesta para peticiones AJAX
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Invoice deleted successfully.'),
                    'redirect' => route('invoices.index')
                ]);
            }
            
            return redirect()->route('invoices.index')
                ->with('success', __('Invoice deleted successfully.'));
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting invoice: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            
            $errorMessage = __('An error occurred while deleting the invoice: ') . $e->getMessage();
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->route('invoices.index')
                ->with('error', $errorMessage);
        }
    }

    /**
     * Export invoice as PDF.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */

/**
 * Send invoice email to client.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \App\Models\Invoice  $invoice
 * @return \Illuminate\Http\RedirectResponse
 */
    /**
     * Export invoice as PDF.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Invoice $invoice)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if (!$user->hasRole('super-admin') && !$user->can('invoices view')) {
            abort(403, __('This action is unauthorized.'));
        }

        // Cargar relaciones necesarias
        $invoice->load(['client', 'items.service', 'payments']);
        
        // Obtener la información de la empresa desde la configuración
        $companyData = [
            'name' => config('app.company_name', 'AppNet Developer'),
            'address' => config('app.company_address', 'Calle San Felix 18 2E'),
            'phone' => config('app.company_phone', '+34619929305'),
            'email' => config('app.company_email', 'info@appnet.dev'),
            'vat' => config('app.company_vat', 'ES123456789'),
            'city_zip_country' => config('app.company_city_zip_country', 'Madrid, 28038, España'),
            'logo_path' => config('app.company_logo_path') ? public_path(config('app.company_logo_path')) : public_path('images/logo.png')
        ];
        
        // Obtener la información bancaria desde la configuración
        $bankInfo = config('invoice.bank');
        
        // Generar PDF con los datos necesarios y aplicar el idioma del usuario
        $currentLocale = app()->getLocale();
        
        // Generar PDF con los datos necesarios
        $pdf = PDF::loadView('invoices.pdf_template', compact('invoice', 'companyData', 'bankInfo'));
        
        // Si la factura no tiene número, usar el ID con traducción
        $filename = $invoice->invoice_number ?: __('invoice') . '-' . $invoice->id;
        
        return $pdf->download("{$filename}.pdf");
    }

    /**
     * Send invoice email to client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendEmail(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        
        // Permitir acceso a super-admin sin verificar permisos específicos
        if (!$user->hasRole('super-admin') && !$user->can('invoices send_email')) {
            abort(403, __('This action is unauthorized.'));
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $client = $invoice->client;
            
            // Obtener la información de la empresa desde la configuración
            $companyData = [
                'name' => config('app.name'),
                'address' => config('app.address', ''),
                'city_zip_country' => config('app.city_zip_country', ''),
                'phone' => config('app.phone', ''),
                'email' => config('mail.from.address'),
                'vat' => config('app.vat_number', ''),
                'logo_path' => public_path('images/logo.png') // Ajusta la ruta según tu estructura
            ];
            
            // Obtener la información bancaria desde la configuración
            $bankInfo = config('invoice.bank');
            
            // Generar PDF con los datos necesarios
            $pdf = PDF::loadView('invoices.pdf_template', compact('invoice', 'companyData', 'bankInfo'));
            $pdfData = $pdf->output();
            
            $filename = 'invoice-' . ($invoice->invoice_number ?: $invoice->id) . '.pdf';
            
            // Guardar el PDF temporalmente
            $tempPath = storage_path('app/temp/' . $filename);
            file_put_contents($tempPath, $pdfData);
            
            // Enviar correo electrónico
            Mail::to($client->email)
                ->send(new InvoiceSentMail(
                    $invoice,
                    $companyData,
                    $tempPath
                ));
                
            // Eliminar el archivo temporal después de enviar el correo
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            // Actualizar estado si es necesario
            if ($invoice->status === 'draft') {
                $invoice->status = 'sent';
                $invoice->sent_at = now();
                $invoice->save();
            }
            
            Log::info("Invoice #{$invoice->id} sent to client #{$client->id}.");
            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', __('Invoice has been sent successfully.'));
                
        } catch (\Exception $e) {
            Log::error('Error sending invoice email: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', __('An error occurred while sending the email.'));
        }
    }

    /**
     * Lock an invoice to prevent further edits.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function lock(Invoice $invoice)
    {
        if (!Auth::user()->can('invoices edit')) {
            abort(403, __('This action is unauthorized.'));
        }

        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', __('Only draft invoices can be locked.'));
        }

        $invoice->status = 'locked';
        $invoice->save();
        
        Log::info("Invoice #{$invoice->id} locked.");
        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', __('Invoice has been locked and can no longer be edited.'));
    }

    /**
     * Unlock a locked invoice to allow edits.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unlock(Invoice $invoice)
    {
        if (!Auth::user()->can('invoices edit')) {
            abort(403, __('This action is unauthorized.'));
        }

        if ($invoice->status !== 'locked') {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', __('Only locked invoices can be unlocked.'));
        }

        $invoice->status = 'draft';
        $invoice->save();
        
        Log::info("Invoice #{$invoice->id} unlocked.");
        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', __('Invoice has been unlocked and can now be edited.'));
    }

    /**
     * Generate QR code for invoice (for Spanish tax requirements).
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function generateQrCodeForInvoice(Invoice $invoice)
    {
        // Verificar si ya tiene un QR generado
        if ($invoice->verifactu_qr_code_data) {
            return response($invoice->verifactu_qr_code_data, 200, [
                'Content-Type' => 'image/svg+xml',
            ]);
        }

        // Si no tiene QR, generar uno nuevo
        $qrData = [
            'id' => $invoice->verifactu_id,
            'fecha' => $invoice->invoice_date->format('d-m-Y'),
            'total' => number_format($invoice->total_amount, 2, ',', ''),
            'nif_emisor' => config('app.company_vat', ''),
            'nif_receptor' => $invoice->client->tax_number ?? '',
            'importe_base' => number_format($invoice->subtotal - $invoice->discount_amount, 2, ',', ''),
            'iva' => number_format($invoice->tax_amount, 2, ',', ''),
        ];
        
        $qrString = collect($qrData)->map(function($value, $key) {
            return "$key=$value";
        })->implode('|');
        
        $qrCode = QrCode::format('svg')
            ->size(200)
            ->generate($qrString);
            
        // Guardar el QR en la base de datos para futuras referencias
        $invoice->update([
            'verifactu_qr_code_data' => $qrCode
        ]);
        
        return response($qrCode, 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }
}
