<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Quote; // <-- AÑADIR ESTA LÍNEA DE IMPORTACIÓN
use App\Models\Project; // <-- AÑADIR ESTA LÍNEA DE IMPORTACIÓN
use App\Models\InvoiceItem; // <-- AÑADIR ESTA LÍNEA DE IMPORTACIÓN
use App\Models\Discount ; // <-- AÑADIR ESTA LÍNEA DE IMPORTACIÓN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // Para reglas de validación
use App\Models\Service;
use App\Services\VeriFactuService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InvoiceSentMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage; // Para manejo de archivos
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Para generar códigos QR

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
            'project_id' => 'nullable|exists:projects,id', // Puede haber varias facturas por proyecto
            'currency' => 'required|string|max:3',
            'discount_id_invoice' => 'nullable|exists:discounts,id', // Para el selector de descuento
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
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

            $invoiceData = $request->only([
                'client_id', 'quote_id', 'project_id', 'invoice_number', 'invoice_date', 'due_date',
                'status', 'currency', 'payment_terms', 'notes_to_client', 'internal_notes',
                'subtotal', /* 'discount_amount' se calculará o tomará del request */ 'tax_amount', 'total_amount'
            ]);
            $invoiceData['quote_id'] = $request->filled('quote_id') ? $request->input('quote_id') : null;
            $invoiceData['project_id'] = $request->filled('project_id') ? $request->input('project_id') : null;

            // Calcular discount_amount basado en el discount_id_invoice seleccionado
            $calculatedDiscountAmount = 0;
            if ($request->filled('discount_id_invoice')) {
                $selectedDiscount = Discount::find($request->input('discount_id_invoice'));
                if ($selectedDiscount) {
                    $subtotalForDiscount = $request->input('subtotal', 0); // Usar el subtotal antes de descuento
                    if ($selectedDiscount->type == 'percentage') {
                        $calculatedDiscountAmount = $subtotalForDiscount * ($selectedDiscount->value / 100);
                    } else { // fixed_amount
                        $calculatedDiscountAmount = $selectedDiscount->value;
                    }
                    $calculatedDiscountAmount = min($subtotalForDiscount, $calculatedDiscountAmount); // No exceder el subtotal
                }
            }
            $invoiceData['discount_id']   = $request->input('discount_id_invoice'); // 👈
            $invoiceData['discount_amount'] = $request->input('discount_amount', $calculatedDiscountAmount); // Usar el del input si existe, si no el calculado


            // Generar ID único para Veri*factu
            $verifactuId = 'FAC-' . date('Ymd-His-') . strtoupper(uniqid());
            
            // Generar datos para el código QR según normativa española
            $qrData = [
                'id' => $verifactuId,
                'fecha' => now()->format('d-m-Y'),
                'total' => number_format($request->input('total_amount', 0), 2, ',', ''),
                'nif_emisor' => config('app.company_vat', ''), // Usamos APP_COMPANY_VAT del .env
                'nif_receptor' => $client->tax_number ?? '',
                'importe_base' => number_format($request->input('subtotal', 0), 2, ',', ''),
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
                $lineTaxAmount = $itemSubtotal * ($itemTaxRate / 100); // Asumiendo que el descuento global no afecta la base imponible de cada línea
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
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\View\View
     */
    public function show(Invoice $invoice)
    {
        $user = Auth::user();
        $canView = $user->can('invoices show');
        $isOwner = $user->hasRole('customer') && $invoice->client && $invoice->client->user_id == $user->id;

        if ($isOwner && $user->can('invoices view_own')) {
            $canView = true;
        }
        if (!$canView && !$isOwner) {
             abort(403, __('This action is unauthorized.'));
        }

        // Cargar relaciones y asegurarse de que los campos del QR estén disponibles
        $invoice->load(['client', 'items.service', 'quote', 'project']);
        
        // Si no hay código QR, generarlo (para facturas antiguas)
        if (!$invoice->verifactu_qr_code_data) {
            $this->generateQrCodeForInvoice($invoice);
            $invoice->refresh(); // Recargar el modelo con los nuevos datos
        }

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Invoices'), 'url' => route('invoices.index')],
            ['name' => $invoice->invoice_number, 'url' => route('invoices.show', $invoice->id)],
        ];
        return view('invoices.show', compact('invoice', 'breadcrumbItems'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\View\View
     */
    public function edit(Invoice $invoice)
    {
        /* ----------- 1) Autorización ----------- */
        $user = Auth::user();
        if (!$user->can('invoices update') || $user->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }
    
        // Solo editar borradores (ajusta si quieres otra lógica)
        if ($invoice->status !== 'draft') {
            return redirect()
                ->route('invoices.show', $invoice->id)
                ->with('error', __('This invoice cannot be edited because it is not in draft status.'));
        }
    
        /* ----------- 2) Datos para selects ----------- */
        // → clientes con vat_rate para el JS
        $clients = Client::orderBy('name')
                         ->get(['id', 'name', 'vat_rate']);
    
        // → presupuestos: permitir el ya asignado o los aceptados sin factura
        $availableQuotes = Quote::where('status', 'accepted')
            ->with('client:id,name')
            ->where(function ($q) use ($invoice) {
                $q->whereDoesntHave('invoices')
                  ->orWhere('id', $invoice->quote_id);
            })
            ->orderBy('quote_number')
            ->get(['id', 'quote_number', 'client_id', 'total_amount']);
    
        // → proyectos: idem
        $availableProjects = Project::whereIn('status', ['completed', 'in_progress'])
            ->with('client:id,name')
            ->where(function ($q) use ($invoice) {
                $q->whereDoesntHave('invoices')
                  ->orWhere('id', $invoice->project_id);
            })
            ->orderBy('project_title')
            ->get(['id', 'project_title', 'client_id']);
    
        // → descuentos activos
        $discounts = Discount::where('is_active', true)
                             ->orderBy('name')
                             ->get();
    
        // → catálogo de servicios
        $services = Service::orderBy('name')
                           ->get(['id', 'name', 'default_price', 'unit', 'description']);
    
        /* ----------- 3) Items ya guardados ----------- */
        $invoice->load('items'); // items + relaciones si las quieres
        $invoiceItems = $invoice->items->map(function ($item) {
            return [
                'id'               => $item->id,
                'service_id'       => $item->service_id,
                'item_description' => $item->item_description,
                'quantity'         => (float) $item->quantity,
                'unit_price'       => (float) $item->unit_price,
                'tax_rate'         => (float) $item->tax_rate,
            ];
        });
    
        /* ----------- 4) Migas de pan ----------- */
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Invoices'),  'url' => route('invoices.index')],
            ['name' => $invoice->invoice_number, 'url' => route('invoices.show', $invoice->id)],
            ['name' => __('Edit'), 'url' => route('invoices.edit', $invoice->id)],
        ];
    
        /* ----------- 5) Render ----------- */
        return view('invoices.edit', compact(
            'invoice',
            'breadcrumbItems',
            'clients',
            'availableQuotes',
            'availableProjects',
            'discounts',
            'services',
            'invoiceItems'     // 👈 se usará en el JS del Blade
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Invoice $invoice)
    {
        if (!Auth::user()->can('invoices update') || Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return redirect()->route('invoices.show', $invoice->id)->with('error', __('This invoice cannot be edited.'));
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number,'.$invoice->id,
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => ['required', Rule::in(['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])],
            'quote_id' => 'nullable|exists:quotes,id|unique:invoices,quote_id,'.$invoice->id,
            'project_id' => 'nullable|exists:projects,id|unique:invoices,project_id,'.$invoice->id,
            'currency' => 'required|string|max:3',
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoices.edit', $invoice->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to update invoice. Please check the errors.'));
        }

        // Check if status is changing from draft to sent/paid
        $isStatusChanging = $request->has('status') && 
                          $invoice->status === 'draft' && 
                          in_array($request->status, ['sent', 'paid']);

        DB::beginTransaction();
        try {
            $invoiceData = $request->only([
                'client_id', 'quote_id', 'project_id', 'invoice_number', 'invoice_date', 'due_date',
                'status', 'currency', 'payment_terms', 'notes', 'subtotal',
                'discount_amount', 'tax_amount', 'total_amount'
            ]);
            
            // Generate digital fingerprint if status is changing from draft to sent/paid
            if ($isStatusChanging && !$invoice->verifactu_hash) {
                $this->generateQrCodeForInvoice($invoice);
            }
            
            $invoice->update($invoiceData);

            $existingItemIds = $invoice->items()->pluck('id')->toArray();
            $newItemIds = [];

            foreach ($request->input('items', []) as $itemData) {
                $itemSubtotal = ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0);
                $lineTaxAmount = $itemSubtotal * (($itemData['tax_rate'] ?? 0) / 100);
                $lineTotal = $itemSubtotal + $lineTaxAmount;

                $itemPayload = [
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'item_subtotal' => $itemSubtotal,
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'tax_amount_per_item' => $itemData['quantity'] ? ($lineTaxAmount / $itemData['quantity']) : 0,
                    'line_tax_total' => $lineTaxAmount,
                    'line_total' => $lineTotal,
                ];

                if (isset($itemData['id']) && $itemData['id'] && in_array($itemData['id'], $existingItemIds)) {
                    $item = $invoice->items()->find($itemData['id']);
                    if ($item) {
                        $item->update($itemPayload);
                        $newItemIds[] = (int)$item->id;
                    }
                } else {
                    $newItem = $invoice->items()->create($itemPayload);
                    $newItemIds[] = (int)$newItem->id;
                }
            }
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            if (!empty($itemsToDelete)) {
                $invoice->items()->whereIn('id', $itemsToDelete)->delete();
            }

            DB::commit();
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Invoice $invoice)
    {
        if (!Auth::user()->can('invoices delete') || Auth::user()->hasRole('customer')) {
            return response()->json(['error' => __('This action is unauthorized.')], 403);
        }
        if ($invoice->status === 'paid') {
            return response()->json(['error' => __('Cannot delete a paid invoice. Consider cancelling it instead.')], 403);
        }

        DB::beginTransaction();
        try {
            $invoice->items()->delete();
            $invoice->delete();
            DB::commit();
            return response()->json(['success' => __('Invoice deleted successfully!')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting invoice: '.$e->getMessage());
            return response()->json(['error' => __('An error occurred while deleting the invoice.')], 500);
        }
    }
    /**
     * Export the invoice to PDF.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    /**
     * Generate digital fingerprint and QR code for an invoice
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    private function generateQrCodeForInvoice(Invoice $invoice)
    {
        try {
            $veriFactuService = new VeriFactuService();
            $fingerprintData = $veriFactuService->generateDigitalFingerprint($invoice);
            
            // Generar código QR en formato PNG como base64
            $qrCodeData = json_encode([
                'id' => $fingerprintData['verifactu_id'],
                'hash' => $fingerprintData['verifactu_hash'],
                'signature' => $fingerprintData['verifactu_signature'],
                'timestamp' => now()->toIso8601String()
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Generar el código QR como PNG en memoria
            $qrCodePng = QrCode::format('png')
                ->size(200)
                ->margin(1)
                ->generate($qrCodeData);
                
            // Convertir a base64 para almacenar en la base de datos
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodePng);
            
            // Actualizar la factura con todos los datos
            $invoice->update([
                'verifactu_id' => $fingerprintData['verifactu_id'],
                'verifactu_hash' => $fingerprintData['verifactu_hash'],
                'verifactu_signature' => $fingerprintData['verifactu_signature'],
                'verifactu_qr_code_data' => $qrCodeBase64,
                'verifactu_timestamp' => $fingerprintData['verifactu_timestamp']
            ]);
            
            return $fingerprintData;
            
        } catch (\Exception $e) {
            Log::error('Error generating digital fingerprint for invoice #' . $invoice->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export the invoice to PDF.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Invoice $invoice)
    {
        $user = Auth::user();
        $canView = $user->can('invoices export_pdf'); // O un permiso más general como 'invoices show'
        $isOwner = $user->hasRole('customer') && $invoice->client && $invoice->client->user_id == $user->id;

        if ($isOwner && $user->can('invoices view_own')) { // Clientes pueden descargar sus propias facturas
            $canView = true;
        }
        if (!$canView && !$isOwner) {
             abort(403, __('This action is unauthorized.'));
        }

        // Cargar relaciones necesarias para el PDF
        $invoice->load(['client', 'items.service']);
        
        // Asegurarse de que la factura tenga una huella digital y código QR
        try {
            // Generar o actualizar el código QR si no existe o está en formato antiguo (SVG)
            if (!$invoice->verifactu_hash || !$invoice->verifactu_qr_code_data || 
                strpos($invoice->verifactu_qr_code_data, 'data:image/png;base64,') !== 0) {
                $this->generateQrCodeForInvoice($invoice);
                $invoice->refresh(); // Recargar el modelo con los nuevos datos
            }
        } catch (\Exception $e) {
            Log::error('Error generating digital fingerprint for PDF: ' . $e->getMessage());
            // Continuar incluso si hay error, pero registrar el problema
            Log::warning('Proceeding with PDF generation without QR code due to error: ' . $e->getMessage());
        }

        // Datos de la empresa (ejemplo, podrías tenerlos en config o una tabla de settings)
        $companyData = [
            'name' => config('app.company_name', 'Your Company Name'),
            'address' => config('app.company_address', '123 Main St'),
            'city_zip_country' => config('app.company_city_zip_country', 'Anytown, 12345, USA'),
            'phone' => config('app.company_phone', '+1 234 567 890'),
            'email' => config('app.company_email', 'contact@yourcompany.com'),
            'vat' => config('app.company_vat', ''), // Usar el valor de APP_COMPANY_VAT del .env
            'logo_path' => public_path('images/logo_color.png') // Asegúrate que esta ruta sea correcta
        ];
        if (!file_exists($companyData['logo_path'])) {
            $companyData['logo_path'] = null; // Evitar error si el logo no existe
            Log::warning('Company logo not found for PDF export: ' . public_path('images/logo_color.png'));
        }


        try {
            // Pasar $invoice y $companyData a la vista del PDF
            $pdf = Pdf::loadView('invoices.pdf_template', compact('invoice', 'companyData'));
            return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            Log::error("Error generating PDF for invoice #{$invoice->id}: " . $e->getMessage());
            return back()->with('error', __('Could not generate PDF. Please check logs.'));
        }
    }
    /**
     * Send the invoice email to the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendEmail(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        if (!$user->can('invoices send_email') || $user->hasRole('customer')) { // Clientes no pueden enviar facturas
            abort(403, __('This action is unauthorized.'));
        }

        if (!$invoice->client || !$invoice->client->email) {
            return back()->with('error', __('Client does not have an email address.'));
        }

        $invoice->load('client', 'items.service');

        $companyData = [
            'name' => config('app.company_name', 'Your Company Name'),
            'address' => config('app.company_address', "123 Main St\nAnytown, USA"),
            'city_zip_country' => config('app.company_city_zip_country', 'Anytown, 12345, USA'),
            'phone' => config('app.company_phone', '+1 234 567 890'),
            'email' => config('app.company_email', 'contact@yourcompany.com'),
            'vat' => config('app.company_vat', null),
            'logo_path' => config('app.company_logo_path') ? public_path(config('app.company_logo_path')) : null,
        ];
         if ($companyData['logo_path'] && !file_exists($companyData['logo_path'])) {
            $companyData['logo_path'] = null;
        }

        try {
            // Generar el PDF y guardarlo temporalmente
            $pdf = Pdf::loadView('invoices.pdf_template', compact('invoice', 'companyData'));
            $pdfFileName = 'invoice-' . $invoice->invoice_number . '.pdf';
            // Guardar en storage/app/temp o un disco temporal configurado
            Storage::put('temp/' . $pdfFileName, $pdf->output());
            Storage::disk(config('filesystems.default'))->put($pdfFileName, $pdf->output());
            // or use a different disk like this
            // Storage::disk('local')->put($pdfFileName, $pdf->output());
            $pdfPath = storage_path('app/temp/' . $pdfFileName);

            Mail::to($invoice->client->email)->send(new InvoiceSentMail($invoice, $companyData, $pdfPath));

            // Eliminar el archivo PDF temporal después de enviarlo
            if (Storage::exists('temp/' . $pdfFileName)) {
                Storage::delete('temp/' . $pdfFileName);
            }

            // Actualizar estado de la factura si estaba en borrador
            if ($invoice->status == 'draft') {
                $invoice->status = 'sent';
                $invoice->sent_at = now(); // Opcional: campo para registrar cuándo se envió
                $invoice->save();
            }

            return back()->with('success', __('Invoice sent successfully to client!'));

        } catch (\Exception $e) {
            Log::error("Error sending invoice #{$invoice->id} email: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            // Asegurarse de eliminar el PDF temporal si algo falla
            if (isset($pdfFileName) && Storage::exists('temp/' . $pdfFileName)) {
                Storage::delete('temp/' . $pdfFileName);
            }
            return back()->with('error', __('An error occurred while sending the email. Please check logs.'));
        }
    }
    public function lock(Invoice $invoice)
    {
        try {
            $invoice->lock();
            return redirect()->back()
                ->with('success', 'Factura bloqueada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Desbloquear una factura (solo super-admin)
     */
    public function unlock(Invoice $invoice)
    {
        try {
            $invoice->unlock();
            return redirect()->back()
                ->with('success', 'Factura desbloqueada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
