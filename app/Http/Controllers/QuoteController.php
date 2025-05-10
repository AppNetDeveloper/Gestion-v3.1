<?php

namespace App\Http\Controllers;

use App\Models\Quote; // Importa tu modelo Quote
use App\Models\Client; // Necesario para el formulario de creación/edición
use App\Models\Service; // Necesario para añadir items
use App\Models\Discount; // Para descuentos
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables; // Si usas Yajra DataTables
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Para transacciones
use Barryvdh\DomPDF\Facade\Pdf; // <--- Asegúrate de tener esta línea
use App\Mail\QuoteSentMail; // <-- Asegúrate que la ruta sea correcta
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Prepara datos básicos para la vista principal
        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'], // Ajusta URL
            ['name' => __('Quotes'), 'url' => route('quotes.index')],
        ];
        // La tabla se carga vía AJAX
        return view('quotes.index', compact('breadcrumbItems'));
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
            // Eager load client relationship for efficiency
            $data = Quote::with('client')->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('client_name', function($row){
                    // Accede al nombre del cliente a través de la relación
                    return $row->client ? $row->client->name : __('Unknown');
                })
                ->editColumn('total_amount', function($row) {
                    // Formatear el total
                    return number_format($row->total_amount ?? 0, 2, ',', '.') . ' €';
                })
                 ->editColumn('status', function($row) {
                    // Podrías añadir formato condicional para el estado (colores, badges)
                    // Ejemplo básico:
                    $status = ucfirst($row->status ?? 'draft');
                    $color = 'text-slate-500'; // default
                    switch ($row->status) {
                        case 'sent': $color = 'text-blue-500'; break;
                        case 'accepted': $color = 'text-green-500'; break;
                        case 'rejected':
                        case 'expired': $color = 'text-red-500'; break;
                        case 'draft': $color = 'text-yellow-500'; break;
                    }
                    return "<span class='{$color}'>{$status}</span>";
                })
                ->editColumn('quote_date', function ($row) {
                    return $row->quote_date ? $row->quote_date->format('d/m/Y') : ''; // Formatear fecha
                })
                ->addColumn('action', function($row){
                    // Botones de acción (Ver, Editar, Eliminar)
                    $viewBtn = '<a href="'.route('quotes.show', $row->id).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="'.__('View Quote').'">
                                    <iconify-icon icon="heroicons:eye" style="font-size: 1.25rem;"></iconify-icon>
                                </a>';
                    $editBtn = '<a href="'.route('quotes.edit', $row->id).'" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="'.__('Edit Quote').'">
                                    <iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon>
                                </a>';
                    $deleteBtn = '<button class="deleteQuote text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1"
                                    data-id="'.$row->id.'" title="'.__('Delete Quote').'">
                                    <iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon>
                                 </button>';
                    return '<div class="flex items-center justify-center space-x-1">'.$viewBtn . $editBtn . $deleteBtn.'</div>';
                })
                ->rawColumns(['action', 'status']) // Permite HTML en 'action' y 'status'
                ->make(true);
        }
        return abort(403, 'Unauthorized action.');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
         // *** CORRECCIÓN AQUÍ: Obtener colección de objetos Client ***
        $clients = Client::orderBy('name')->get(['id', 'name', 'vat_rate']); // Obtener los campos necesarios
        $services = Service::orderBy('name')->get(['id', 'name', 'default_price', 'unit', 'description']); // Añadir descripción
        $discounts = Discount::where('is_active', true)->orderBy('name')->get(); // Para descuentos aplicables

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Quotes'), 'url' => route('quotes.index')],
            ['name' => __('Create'), 'url' => route('quotes.create')],
        ];

        return view('quotes.create', compact('breadcrumbItems', 'clients', 'services', 'discounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validación básica (necesitará ser más compleja para los items)
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'quote_number' => 'required|string|max:255|unique:quotes,quote_number',
            'quote_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:quote_date',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'terms_and_conditions' => 'nullable|string',
            'notes_to_client' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'discount_id' => 'nullable|exists:discounts,id',
            // Validación para los items (ejemplo básico, requiere adaptación a cómo envíes los datos)
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id', // Puede ser null si la descripción es manual
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            // 'items.*.line_discount_percentage' => 'nullable|numeric|min:0|max:100', // Validar si se implementa
            // ... más validaciones para items
        ], [
            'items.required' => __('You must add at least one item to the quote.'), // Mensaje personalizado
            'items.min' => __('You must add at least one item to the quote.'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('quotes.create')
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create quote. Please check the errors.'));
        }

        // Usar transacción para asegurar consistencia entre Quote y QuoteItems
        DB::beginTransaction();
        try {
            // Crear el Quote principal
            $quoteData = $request->only([
                'client_id', 'quote_number', 'quote_date', 'expiry_date', 'status',
                'terms_and_conditions', 'notes_to_client', 'internal_notes', 'discount_id'
            ]);
            // Calcular totales (se recalcularán después de añadir items)
            $quoteData['subtotal'] = 0;
            $quoteData['discount_amount'] = 0;
            $quoteData['tax_amount'] = 0;
            $quoteData['total_amount'] = 0;

            $quote = Quote::create($quoteData);

            // Crear los QuoteItems y calcular totales
            $totalSubtotal = 0;
            $globalDiscountAmount = 0;
            $totalTaxAmount = 0;

            foreach ($request->input('items', []) as $itemData) {
                $itemSubtotal = ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0);
                $lineDiscountAmount = 0; // Calcular descuento de línea aquí si se implementa
                $lineTotal = $itemSubtotal - $lineDiscountAmount;
                $totalSubtotal += $lineTotal; // Sumar al subtotal general (después de descuentos de línea)

                $quote->items()->create([
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'item_subtotal' => $itemSubtotal,
                    // 'discount_id' => $itemData['discount_id'] ?? null, // Si hay descuento por línea
                    // 'line_discount_percentage' => $itemData['line_discount_percentage'] ?? null,
                    'line_discount_amount' => $lineDiscountAmount, // Guardar descuento calculado
                    'line_total' => $lineTotal, // Guardar total de línea
                    'sort_order' => $itemData['sort_order'] ?? 0,
                ]);
            }

            // Calcular descuento global si aplica
            if ($request->filled('discount_id')) {
                $discount = Discount::find($request->input('discount_id'));
                if ($discount) {
                    if ($discount->type == 'percentage') {
                        $globalDiscountAmount = $totalSubtotal * ($discount->value / 100);
                    } else { // fixed_amount
                        $globalDiscountAmount = $discount->value;
                    }
                     // Asegurar que el descuento no sea mayor que el subtotal
                    $globalDiscountAmount = min($totalSubtotal, $globalDiscountAmount);
                }
            }

            // Calcular impuestos sobre la base imponible (subtotal - descuento global)
            $taxableBase = $totalSubtotal - $globalDiscountAmount;
            $client = Client::find($request->input('client_id'));
            $vatRate = $client->vat_rate ?? config('app.vat_rate', 21); // Usar tasa del cliente o por defecto
            $totalTaxAmount = $taxableBase * ($vatRate / 100);

            // Calcular total final
            $finalTotal = $taxableBase + $totalTaxAmount;

            // Actualizar totales del Quote
            $quote->subtotal = $totalSubtotal;
            $quote->discount_amount = $globalDiscountAmount;
            $quote->tax_amount = $totalTaxAmount;
            $quote->total_amount = $finalTotal;
            $quote->save();

            DB::commit(); // Confirmar transacción

            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote created successfully!')); // Redirigir a la vista show

        } catch (\Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error
            Log::error('Error creating quote: '.$e->getMessage());
            return redirect()->route('quotes.create')
                        ->withInput()
                        ->with('error', __('An error occurred while creating the quote.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\View\View
     */
    public function show(Quote $quote)
    {
        // Cargar relaciones necesarias para la vista de detalle
        $quote->load('client', 'items.service', 'items.discount', 'discount');

         $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Quotes'), 'url' => route('quotes.index')],
            ['name' => $quote->quote_number, 'url' => route('quotes.show', $quote->id)],
        ];

        return view('quotes.show', compact('quote', 'breadcrumbItems'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\View\View
     */
    public function edit(Quote $quote)
    {
        // Cargar relaciones y datos necesarios para el formulario de edición
        $quote->load('items'); // Cargar items existentes
        // *** CORRECCIÓN AQUÍ: Obtener colección de objetos Client ***
        $clients = Client::orderBy('name')->get(['id', 'name', 'vat_rate']);
        $services = Service::orderBy('name')->get(['id', 'name', 'default_price', 'unit', 'description']);
        $discounts = Discount::where('is_active', true)->orderBy('name')->get();

         $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
            ['name' => __('Quotes'), 'url' => route('quotes.index')],
            ['name' => $quote->quote_number, 'url' => route('quotes.show', $quote->id)],
            ['name' => __('Edit'), 'url' => route('quotes.edit', $quote->id)],
        ];

        return view('quotes.edit', compact('quote', 'breadcrumbItems', 'clients', 'services', 'discounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Quote $quote)
    {
        // Validación similar a store, pero ignorando unique para el quote actual
         $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'quote_number' => 'required|string|max:255|unique:quotes,quote_number,'.$quote->id,
            'quote_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:quote_date',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'terms_and_conditions' => 'nullable|string',
            'notes_to_client' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'discount_id' => 'nullable|exists:discounts,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer', // Para identificar items existentes
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            // 'items.*.line_discount_percentage' => 'nullable|numeric|min:0|max:100',
            // ... más validaciones para items
        ], [
            'items.required' => __('You must add at least one item to the quote.'),
            'items.min' => __('You must add at least one item to the quote.'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('quotes.edit', $quote->id)
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to update quote. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            // Actualizar datos del Quote principal
             $quoteData = $request->only([
                'client_id', 'quote_number', 'quote_date', 'expiry_date', 'status',
                'terms_and_conditions', 'notes_to_client', 'internal_notes', 'discount_id'
            ]);
            // Los totales se recalcularán después de sincronizar items
            $quoteData['subtotal'] = 0;
            $quoteData['discount_amount'] = 0;
            $quoteData['tax_amount'] = 0;
            $quoteData['total_amount'] = 0;

            $quote->update($quoteData); // Actualizar primero los datos principales

            // Sincronizar items (más complejo: actualizar existentes, añadir nuevos, eliminar viejos)
            $existingItemIds = $quote->items()->pluck('id')->toArray();
            $newItemIds = [];
            $totalSubtotal = 0;

            foreach ($request->input('items', []) as $itemData) {
                 $itemSubtotal = ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0);
                 $lineDiscountAmount = 0; // Calcular
                 $lineTotal = $itemSubtotal - $lineDiscountAmount;
                 $totalSubtotal += $lineTotal;

                $itemPayload = [
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'item_subtotal' => $itemSubtotal,
                    // 'discount_id' => $itemData['discount_id'] ?? null,
                    // 'line_discount_percentage' => $itemData['line_discount_percentage'] ?? null,
                    'line_discount_amount' => $lineDiscountAmount,
                    'line_total' => $lineTotal,
                    'sort_order' => $itemData['sort_order'] ?? 0,
                ];

                if (isset($itemData['id']) && $itemData['id'] && in_array($itemData['id'], $existingItemIds)) {
                    // Actualizar item existente
                    $item = $quote->items()->find($itemData['id']);
                    if ($item) {
                        $item->update($itemPayload);
                        $newItemIds[] = (int)$item->id; // Asegurar que sea int
                    }
                } else {
                    // Crear nuevo item
                    $newItem = $quote->items()->create($itemPayload);
                    $newItemIds[] = (int)$newItem->id; // Asegurar que sea int
                }
            }

            // Eliminar items que ya no están en la petición
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            if (!empty($itemsToDelete)) {
                $quote->items()->whereIn('id', $itemsToDelete)->delete();
            }

             // Recalcular totales después de sincronizar items
            $globalDiscountAmount = 0;
            if ($request->filled('discount_id')) {
                $discount = Discount::find($request->input('discount_id'));
                if ($discount) {
                    if ($discount->type == 'percentage') { $globalDiscountAmount = $totalSubtotal * ($discount->value / 100); }
                    else { $globalDiscountAmount = $discount->value; }
                    $globalDiscountAmount = min($totalSubtotal, $globalDiscountAmount);
                }
            }
            $taxableBase = $totalSubtotal - $globalDiscountAmount;
            $client = Client::find($request->input('client_id')); // Obtener cliente actualizado
            $vatRate = $client->vat_rate ?? config('app.vat_rate', 21);
            $totalTaxAmount = $taxableBase * ($vatRate / 100);
            $finalTotal = $taxableBase + $totalTaxAmount;

            // Actualizar totales del Quote
            $quote->subtotal = $totalSubtotal;
            $quote->discount_amount = $globalDiscountAmount;
            $quote->tax_amount = $totalTaxAmount;
            $quote->total_amount = $finalTotal;
            $quote->save(); // Guardar los totales actualizados


            DB::commit();

            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote updated successfully!'));

        } catch (\Exception $e) {
             DB::rollBack();
            Log::error('Error updating quote: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()); // Log más detallado
            return redirect()->route('quotes.edit', $quote->id)
                        ->withInput()
                        ->with('error', __('An error occurred while updating the quote.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Quote $quote)
    {
        // Los items se borrarán en cascada si se definió onDelete('cascade') en la migración
        try {
            $quote->delete();
            return response()->json(['success' => __('Quote deleted successfully!')]);
        } catch (\Exception $e) {
            Log::error('Error deleting quote: '.$e->getMessage());
            return response()->json(['error' => __('An error occurred while deleting the quote.')], 500);
        }
    }
    // *** AÑADIR ESTE MÉTODO ***
    /**
     * Export the specified quote as PDF.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Quote $quote)
    {
        // Cargar las relaciones necesarias
        $quote->load('client', 'items.service');

        // Datos para pasar a la vista PDF
        $data = [
            'quote' => $quote,
        ];

        try {
            // Generar el PDF usando la vista Blade específica
            $pdf = Pdf::loadView('quotes.pdf_template', $data);

            // Mostrar el PDF en el navegador
            return $pdf->stream('quote-'.$quote->quote_number.'.pdf');

        } catch (\Exception $e) {
            Log::error('Error generating PDF for quote #'.$quote->id.': '.$e->getMessage());
            return redirect()->route('quotes.show', $quote->id)->with('error', __('Could not generate PDF.'));
        }
    }
    /**
     * Send the quote email to the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendEmail(Request $request, Quote $quote)
    {
        // 1. Validar que el cliente tenga email
        if (!$quote->client || !$quote->client->email) {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('Client does not have an email address.'));
        }

        // 2. Cargar relaciones necesarias
        $quote->load('client', 'items.service'); // Asegúrate de cargar todo lo que necesite el PDF y el email

        try {
            // 3. Generar el PDF para adjuntar
            $pdfData = null;
            try {
                 // Usamos la misma vista que para la descarga directa
                 $pdf = Pdf::loadView('quotes.pdf_template', ['quote' => $quote]);
                 $pdfData = $pdf->output(); // Obtener contenido binario del PDF
            } catch (\Exception $pdfError) {
                 Log::error('Error generating PDF for email attachment (Quote #'.$quote->id.'): '.$pdfError->getMessage());
                 // Decidir si enviar sin PDF o fallar
                 // return redirect()->route('quotes.show', $quote->id)->with('error', __('Could not generate PDF attachment. Email not sent.'));
                 // Por ahora, intentaremos enviar sin PDF si falla la generación
            }


            // 4. Enviar el Mailable (DESCOMENTADO)
            Mail::to($quote->client->email)->send(new QuoteSentMail($quote, $pdfData)); // Pasar quote y pdfData

            // 5. (Opcional) Actualizar estado del presupuesto a 'sent' si estaba en 'draft'
            if ($quote->status === 'draft') {
                 $quote->update(['status' => 'sent']);
            }

            Log::info("Quote #{$quote->id} email sent successfully to {$quote->client->email}"); // Log de éxito
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote sent successfully to client!'));

        } catch (\Exception $e) {
            Log::error('Error sending quote email #'.$quote->id.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()); // Log más detallado
            return redirect()->route('quotes.show', $quote->id)->with('error', __('An error occurred while sending the email.'));
        }
    }
        /**
     * Convert the specified quote to an invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function convertToInvoice(Request $request, Quote $quote)
    {
        // 1. Verificar estado del presupuesto
        if ($quote->status !== 'accepted') {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('Only accepted quotes can be converted to invoices.'));
        }

        // 2. (Opcional) Verificar si ya se ha generado una factura para este presupuesto
        if (Invoice::where('quote_id', $quote->id)->exists()) {
             return redirect()->route('quotes.show', $quote->id)->with('error', __('An invoice has already been generated for this quote.'));
        }

        // Cargar relaciones necesarias
        $quote->load('client', 'items.service', 'items.discount');

        DB::beginTransaction();
        try {
            // 3. Crear la Factura
            $invoice = Invoice::create([
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'project_id' => $quote->project_id, // Si el presupuesto está ligado a un proyecto
                'invoice_number' => 'INV-' . date('Ymd') . '-' . rand(1000,9999), // Generar número de factura (ajusta la lógica)
                'invoice_date' => now(),
                'due_date' => now()->addDays(30), // Fecha de vencimiento (ej. 30 días)
                'status' => 'draft', // Estado inicial de la factura
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'currency' => $quote->currency ?? 'EUR', // Asumir EUR si no está en el presupuesto
                'payment_terms' => $quote->terms_and_conditions, // O términos de pago específicos
                'notes_to_client' => $quote->notes_to_client,
                // 'verifactu_id' => null, // Se generaría al emitir/firmar la factura
            ]);

            // 4. Crear los Items de la Factura
            foreach ($quote->items as $quoteItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $quoteItem->service_id,
                    // 'quote_item_id' => $quoteItem->id, // Para trazabilidad
                    'item_description' => $quoteItem->item_description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'item_subtotal' => $quoteItem->item_subtotal,
                    // 'line_discount_percentage' => $quoteItem->line_discount_percentage,
                    'line_discount_amount' => $quoteItem->line_discount_amount ?? 0,
                    'tax_rate' => $quote->client->vat_rate ?? config('app.vat_rate', 21), // Usar IVA del cliente o por defecto
                    'tax_amount_per_item' => 0, // Recalcular si es necesario
                    'line_tax_total' => 0, // Recalcular basado en la base imponible de la línea y la tasa
                    'line_total' => $quoteItem->line_total,
                    'sort_order' => $quoteItem->sort_order,
                ]);
                // Nota: El cálculo de 'tax_amount_per_item' y 'line_tax_total' en InvoiceItem
                // debería hacerse con más precisión aquí, similar a como se hace en el presupuesto.
            }

            // 5. (Opcional) Actualizar estado del presupuesto
            $quote->status = 'invoiced'; // O 'partially_invoiced'
            $quote->save();

            DB::commit();

            Log::info("Quote #{$quote->id} converted to Invoice #{$invoice->id}");
            // Redirigir a la vista de detalle de la nueva factura (necesitarás crearla)
            // return redirect()->route('invoices.show', $invoice->id)->with('success', __('Quote successfully converted to invoice!'));
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote successfully converted to invoice! Invoice #') . $invoice->invoice_number);


        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error converting quote #'.$quote->id.' to invoice: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('quotes.show', $quote->id)->with('error', __('An error occurred while converting the quote to an invoice.'));
        }
    }
}
