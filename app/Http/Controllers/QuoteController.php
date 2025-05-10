<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Client;
use App\Models\Service;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuoteSentMail; // Asegúrate que esta clase Mailable existe
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Verificar permiso general para ver la lista de presupuestos
        if (!Auth::user()->can('quotes index') && !Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'], // Ajusta URL
            ['name' => __('Quotes'), 'url' => route('quotes.index')],
        ];
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
            $user = Auth::user();
            $query = Quote::with('client')->latest();

            if ($user->hasRole('customer')) {
                $clientProfile = Client::where('user_id', $user->id)->first();
                if ($clientProfile) {
                    $query->where('client_id', $clientProfile->id);
                } else {
                    $query->whereRaw('1 = 0'); // Condición que siempre es falsa
                }
            } elseif (!$user->can('quotes index')) { // Si no es cliente y no tiene permiso general
                 $query->whereRaw('1 = 0'); // No mostrar nada
            }

            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('client_name', function($row){
                    return $row->client ? $row->client->name : __('Unknown');
                })
                ->editColumn('total_amount', function($row) {
                    return number_format($row->total_amount ?? 0, 2, ',', '.') . ' €';
                })
                 ->editColumn('status', function($row) {
                    $status = ucfirst($row->status ?? 'draft');
                    $color = 'text-slate-500 dark:text-slate-400';
                    switch ($row->status) {
                        case 'sent': $color = 'text-blue-500'; break;
                        case 'accepted': $color = 'text-green-500'; break;
                        case 'invoiced': $color = 'text-purple-500'; break;
                        case 'rejected':
                        case 'expired': $color = 'text-red-500'; break;
                        case 'draft': $color = 'text-yellow-500'; break;
                    }
                    return "<span class='{$color} font-medium'>{$status}</span>";
                })
                ->editColumn('quote_date', function ($row) {
                    return $row->quote_date ? $row->quote_date->format('d/m/Y') : '';
                })
                ->addColumn('action', function($row) use ($user) { // Pasar $user al closure
                    $actions = '<div class="flex items-center justify-center space-x-1">';

                    // Botón Ver
                    $canView = $user->can('quotes show');
                    if ($user->hasRole('customer') && $user->can('quotes view_own') && $row->client && $row->client->user_id == $user->id) {
                        $canView = true;
                    }
                    if ($canView) {
                        $actions .= '<a href="'.route('quotes.show', $row->id).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="'.__('View Quote').'">
                                        <iconify-icon icon="heroicons:eye" style="font-size: 1.25rem;"></iconify-icon>
                                    </a>';
                    }

                    // Botón Editar
                    $canUpdate = $user->can('quotes update');
                    if ($user->hasRole('customer') && $user->can('quotes view_own') && $row->client && $row->client->user_id == $user->id && in_array($row->status, ['draft', 'sent'])) {
                        $canUpdate = true; // Cliente puede editar sus borradores/enviados
                    }
                    if ($canUpdate && !in_array($row->status, ['accepted', 'invoiced', 'rejected', 'expired'])) {
                         $actions .= '<a href="'.route('quotes.edit', $row->id).'" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="'.__('Edit Quote').'">
                                    <iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon>
                                </a>';
                    }

                    // Botón PDF
                    $canExportPdf = $user->can('quotes export_pdf');
                     if ($user->hasRole('customer') && $user->can('quotes view_own') && $row->client && $row->client->user_id == $user->id) {
                        $canExportPdf = true;
                    }
                    if ($canExportPdf) {
                        $actions .= '<a href="'.route('quotes.pdf', $row->id).'" target="_blank" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1" title="'.__('Export PDF').'">
                                        <iconify-icon icon="heroicons:arrow-down-tray" style="font-size: 1.25rem;"></iconify-icon>
                                    </a>';
                    }

                    // Botón Eliminar (Generalmente no para clientes)
                    if ($user->can('quotes delete') && !$user->hasRole('customer')) {
                         $actions .= '<button class="deleteQuote text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1"
                                    data-id="'.$row->id.'" title="'.__('Delete Quote').'">
                                    <iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon>
                                 </button>';
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (!Auth::user()->can('quotes create')) {
            abort(403, __('This action is unauthorized.'));
        }

        $clients = Client::orderBy('name')->get(['id', 'name', 'vat_rate']);
        $services = Service::orderBy('name')->get(['id', 'name', 'default_price', 'unit', 'description']);
        $discounts = Discount::where('is_active', true)->orderBy('name')->get();

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
        if (!Auth::user()->can('quotes create')) {
            abort(403, __('This action is unauthorized.'));
        }

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
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'items.required' => __('You must add at least one item to the quote.'),
            'items.min' => __('You must add at least one item to the quote.'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('quotes.create')
                        ->withErrors($validator)
                        ->withInput()
                        ->with('error', __('Failed to create quote. Please check the errors.'));
        }

        DB::beginTransaction();
        try {
            $quoteData = $request->only([
                'client_id', 'quote_number', 'quote_date', 'expiry_date', 'status',
                'terms_and_conditions', 'notes_to_client', 'internal_notes', 'discount_id'
            ]);
            // Los totales se calculan después de procesar los items
            $quoteData['subtotal'] = $request->input('subtotal', 0);
            $quoteData['discount_amount'] = $request->input('discount_amount', 0);
            $quoteData['tax_amount'] = $request->input('tax_amount', 0);
            $quoteData['total_amount'] = $request->input('total_amount', 0);

            $quote = Quote::create($quoteData);

            foreach ($request->input('items', []) as $itemData) {
                $quote->items()->create([
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'item_subtotal' => ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0),
                    'line_discount_amount' => $itemData['line_discount_amount'] ?? 0, // Asumir que se envía desde JS
                    'line_total' => $itemData['line_total'] ?? (($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0)), // Asumir que se envía desde JS
                    'sort_order' => $itemData['sort_order'] ?? 0,
                ]);
            }
            // No es necesario recalcular y guardar los totales aquí si ya se envían desde el formulario
            // y se validan/confían. Si no, se recalcularían como en la versión anterior.

            DB::commit();
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote created successfully!'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating quote: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('quotes.create')
                        ->withInput()
                        ->with('error', __('An error occurred while creating the quote.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Quote $quote)
    {
        $user = Auth::user();
        $canView = $user->can('quotes show');
        if ($user->hasRole('customer')) {
            $clientProfile = Client::where('user_id', $user->id)->first();
            if ($clientProfile && $quote->client_id === $clientProfile->id && $user->can('quotes view_own')) {
                $canView = true;
            } else if (!$clientProfile || $quote->client_id !== $clientProfile->id) {
                 $canView = false; // Cliente intentando ver presupuesto ajeno
            }
        }
        if (!$canView) {
            abort(403, __('This action is unauthorized.'));
        }

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
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Quote $quote)
    {
        $user = Auth::user();
        $canUpdate = $user->can('quotes update');

        if ($user->hasRole('customer')) {
            $clientProfile = Client::where('user_id', $user->id)->first();
            if (!$clientProfile || $quote->client_id !== $clientProfile->id || !in_array($quote->status, ['draft', 'sent'])) {
                // Si es cliente pero no es su presupuesto o no está en estado editable por cliente
                return redirect()->route('quotes.show', $quote->id)->with('error', __('You cannot edit this quote.'));
            }
            // Si es su presupuesto y está en draft/sent, el permiso 'quotes update' general no aplica,
            // pero permitimos la edición basado en la lógica de cliente.
        } elseif (!$canUpdate) { // Para otros roles, verificar permiso general
             abort(403, __('This action is unauthorized.'));
        }

        // Comprobación general de estado para todos los que pueden editar
         if (in_array($quote->status, ['accepted', 'invoiced', 'rejected', 'expired'])) {
             return redirect()->route('quotes.show', $quote->id)->with('error', __('This quote cannot be edited because it is already :status.', ['status' => $quote->status]));
         }

        $quote->load('items');
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
        // Lógica de permisos similar a edit()
        $user = Auth::user();
        $canUpdateGeneral = $user->can('quotes update');
        $canUpdateOwn = false;

        if ($user->hasRole('customer')) {
            $clientProfile = Client::where('user_id', $user->id)->first();
            if ($clientProfile && $quote->client_id === $clientProfile->id && in_array($quote->status, ['draft', 'sent'])) {
                $canUpdateOwn = true;
            }
        }

        if (!$canUpdateGeneral && !$canUpdateOwn) {
            abort(403, __('This action is unauthorized.'));
        }
        if (in_array($quote->status, ['accepted', 'invoiced', 'rejected', 'expired']) && !$canUpdateOwn) { // Si no es cliente editando su draft/sent
             return redirect()->route('quotes.show', $quote->id)->with('error', __('This quote cannot be edited because it is already :status.', ['status' => $quote->status]));
        }


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
            'items.*.id' => 'nullable|integer',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
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
             $quoteData = $request->only([
                'client_id', 'quote_number', 'quote_date', 'expiry_date', 'status',
                'terms_and_conditions', 'notes_to_client', 'internal_notes', 'discount_id'
            ]);
            $quoteData['subtotal'] = $request->input('subtotal', 0);
            $quoteData['discount_amount'] = $request->input('discount_amount', 0);
            $quoteData['tax_amount'] = $request->input('tax_amount', 0);
            $quoteData['total_amount'] = $request->input('total_amount', 0);

            $quote->update($quoteData);

            $existingItemIds = $quote->items()->pluck('id')->toArray();
            $newItemIds = [];

            foreach ($request->input('items', []) as $itemData) {
                $itemPayload = [
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'item_subtotal' => ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0),
                    'line_discount_amount' => $itemData['line_discount_amount'] ?? 0,
                    'line_total' => $itemData['line_total'] ?? (($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0)),
                    'sort_order' => $itemData['sort_order'] ?? 0,
                ];

                if (isset($itemData['id']) && $itemData['id'] && in_array($itemData['id'], $existingItemIds)) {
                    $item = $quote->items()->find($itemData['id']);
                    if ($item) {
                        $item->update($itemPayload);
                        $newItemIds[] = (int)$item->id;
                    }
                } else {
                    $newItem = $quote->items()->create($itemPayload);
                    $newItemIds[] = (int)$newItem->id;
                }
            }

            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            if (!empty($itemsToDelete)) {
                $quote->items()->whereIn('id', $itemsToDelete)->delete();
            }
            // No es necesario recalcular y guardar los totales aquí si ya se envían desde el formulario.
            // Si no se envían, se deberían recalcular aquí como en el método store.

            DB::commit();
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote updated successfully!'));

        } catch (\Exception $e) {
             DB::rollBack();
            Log::error('Error updating quote: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
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
        // Lógica de permisos
        if (!Auth::user()->can('quotes delete')) {
             // Si es un cliente, normalmente no debería poder borrar.
            if (Auth::user()->hasRole('customer')) {
                 abort(403, __('This action is unauthorized.'));
            }
            abort(403, __('This action is unauthorized.'));
        }
        // Un cliente no debería poder borrar presupuestos, incluso los suyos, a menos que se permita explícitamente.

        try {
            $quote->delete();
            return response()->json(['success' => __('Quote deleted successfully!')]);
        } catch (\Exception $e) {
            Log::error('Error deleting quote: '.$e->getMessage());
            return response()->json(['error' => __('An error occurred while deleting the quote.')], 500);
        }
    }

    /**
     * Export the specified quote as PDF.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Quote $quote)
    {
        // Lógica de permisos similar a show()
        $user = Auth::user();
        $canView = $user->can('quotes export_pdf'); // Permiso específico para exportar
        if ($user->hasRole('customer')) {
            $clientProfile = Client::where('user_id', $user->id)->first();
            if (!($clientProfile && $quote->client_id === $clientProfile->id && $user->can('quotes view_own'))) { // view_own para ver si puede acceder
                $canView = false;
            }
        }
        if (!$canView) {
            abort(403, __('This action is unauthorized.'));
        }

        $quote->load('client', 'items.service');
        $data = ['quote' => $quote];
        try {
            $pdf = Pdf::loadView('quotes.pdf_template', $data);
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
        // Lógica de permisos similar a show()
        $user = Auth::user();
        $canSend = $user->can('quotes send_email');
         if ($user->hasRole('customer')) { // Un cliente no debería poder enviar presupuestos por email de esta forma
            abort(403, __('This action is unauthorized.'));
        }
        if (!$canSend) {
             abort(403, __('This action is unauthorized.'));
        }


        if (!$quote->client || !$quote->client->email) {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('Client does not have an email address.'));
        }
        $quote->load('client', 'items.service');
        try {
            $pdfData = null;
            try {
                 $pdf = Pdf::loadView('quotes.pdf_template', ['quote' => $quote]);
                 $pdfData = $pdf->output();
            } catch (\Exception $pdfError) {
                 Log::error('Error generating PDF for email attachment (Quote #'.$quote->id.'): '.$pdfError->getMessage());
            }
            Mail::to($quote->client->email)->send(new QuoteSentMail($quote, $pdfData));
            if ($quote->status === 'draft') {
                 $quote->update(['status' => 'sent']);
            }
            Log::info("Quote #{$quote->id} email sent successfully to {$quote->client->email}");
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote sent successfully to client!'));
        } catch (\Exception $e) {
            Log::error('Error sending quote email #'.$quote->id.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
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
        // Lógica de permisos
        if (!Auth::user()->can('quotes convert_to_invoice')) {
             abort(403, __('This action is unauthorized.'));
        }
         // Un cliente no debería poder convertir a factura directamente
         if (Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }


        if ($quote->status !== 'accepted') {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('Only accepted quotes can be converted to invoices.'));
        }
        if (Invoice::where('quote_id', $quote->id)->exists()) {
             return redirect()->route('quotes.show', $quote->id)->with('error', __('An invoice has already been generated for this quote.'));
        }

        $quote->load('client', 'items.service', 'items.discount');

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'project_id' => $quote->project_id,
                'invoice_number' => 'INV-' . date('Ymd') . '-' . rand(1000,9999),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'currency' => $quote->currency ?? 'EUR',
                'payment_terms' => $quote->terms_and_conditions,
                'notes_to_client' => $quote->notes_to_client,
            ]);

            foreach ($quote->items as $quoteItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $quoteItem->service_id,
                    'item_description' => $quoteItem->item_description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'item_subtotal' => $quoteItem->item_subtotal,
                    'line_discount_amount' => $quoteItem->line_discount_amount ?? 0,
                    'tax_rate' => $quote->client->vat_rate ?? config('app.vat_rate', 21),
                    'tax_amount_per_item' => 0, // Recalcular
                    'line_tax_total' => 0, // Recalcular
                    'line_total' => $quoteItem->line_total,
                    'sort_order' => $quoteItem->sort_order,
                ]);
            }

            $quote->status = 'invoiced';
            $quote->save();

            DB::commit();
            Log::info("Quote #{$quote->id} converted to Invoice #{$invoice->id}");
            // Idealmente redirigir a la vista de la factura: route('invoices.show', $invoice->id)
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote successfully converted to invoice! Invoice #') . $invoice->invoice_number);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error converting quote #'.$quote->id.' to invoice: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('quotes.show', $quote->id)->with('error', __('An error occurred while converting the quote to an invoice.'));
        }
    }
}
