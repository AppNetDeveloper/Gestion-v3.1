<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Client;
use App\Models\Service;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project; // Asegúrate que esté importado
use App\Models\User; // Para notificaciones opcionales
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuoteSentMail; // Asegúrate que esta clase Mailable existe
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Notification; // Para notificaciones opcionales
// use App\Notifications\QuoteAcceptedNotification; // Ejemplo de notificación
// use App\Notifications\QuoteRejectedNotification; // Ejemplo de notificación

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('quotes index') && !Auth::user()->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

        $breadcrumbItems = [
            ['name' => __('Dashboard'), 'url' => '/dashboard'],
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
            $isCustomer = $user->hasRole('customer'); // Definir $isCustomer aquí

            $query = Quote::with('client')->latest();

            if ($isCustomer) { // Usar la variable definida
                // Asumimos que Client tiene una columna user_id
                $clientProfile = Client::where('user_id', $user->id)->first();
                if ($clientProfile) {
                    $query->where('client_id', $clientProfile->id);
                } else {
                    $query->whereRaw('1 = 0'); // No mostrar nada si no hay perfil de cliente
                }
            } elseif (!$user->can('quotes index')) {
                 $query->whereRaw('1 = 0'); // No mostrar nada si no tiene permiso general
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
                ->addColumn('action', function($row) use ($user, $isCustomer) { // *** $isCustomer AÑADIDO AL use() ***
                    $actions = '<div class="flex items-center justify-center space-x-1">';
                    $isOwner = $isCustomer && $row->client && $row->client->user_id == $user->id;

                    // Botón Ver
                    $canView = $user->can('quotes show');
                    if ($isOwner && $user->can('quotes view_own')) {
                        $canView = true;
                    }
                    if ($canView) {
                        $actions .= '<a href="'.route('quotes.show', $row->id).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="'.__('View Quote').'"><iconify-icon icon="heroicons:eye" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }

                    // Botón Editar
                    $canUpdate = $user->can('quotes update');
                    if ($isOwner && in_array($row->status, ['draft', 'sent'])) {
                        $canUpdate = true;
                    }
                    if ($canUpdate && !in_array($row->status, ['accepted', 'invoiced', 'rejected', 'expired'])) {
                         if (!$isCustomer || ($isOwner && in_array($row->status, ['draft', 'sent']))) {
                             $actions .= '<a href="'.route('quotes.edit', $row->id).'" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="'.__('Edit Quote').'"><iconify-icon icon="heroicons:pencil-square" style="font-size: 1.25rem;"></iconify-icon></a>';
                         }
                    }

                    // Botón PDF
                    $canExportPdf = $user->can('quotes export_pdf');
                     if ($isOwner && $user->can('quotes view_own')) {
                        $canExportPdf = true;
                    }
                    if ($canExportPdf) {
                        $actions .= '<a href="'.route('quotes.pdf', $row->id).'" target="_blank" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1" title="'.__('Export PDF').'"><iconify-icon icon="heroicons:arrow-down-tray" style="font-size: 1.25rem;"></iconify-icon></a>';
                    }

                    // Botón Eliminar (Solo para admin/empleados, no para clientes)
                    if ($user->can('quotes delete') && !$isCustomer) { // Usar $isCustomer
                         $actions .= '<button class="deleteQuote text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1" data-id="'.$row->id.'" title="'.__('Delete Quote').'"><iconify-icon icon="heroicons:trash" style="font-size: 1.25rem;"></iconify-icon></button>';
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
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
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
                'terms_and_conditions', 'notes_to_client', 'internal_notes', 'discount_id',
                'subtotal', 'discount_amount', 'tax_amount', 'total_amount'
            ]);

            $quote = Quote::create($quoteData);

            foreach ($request->input('items', []) as $itemData) {
                $itemSubtotal = ($itemData['quantity'] ?? 1) * ($itemData['unit_price'] ?? 0);
                $quote->items()->create([
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_description' => $itemData['item_description'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'item_subtotal' => $itemSubtotal,
                    'line_discount_amount' => $itemData['line_discount_amount'] ?? 0,
                    'line_total' => $itemData['line_total'] ?? $itemSubtotal,
                    'sort_order' => $itemData['sort_order'] ?? 0,
                ]);
            }

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
        $isOwner = $user->hasRole('customer') && $quote->client && $quote->client->user_id == $user->id;

        if ($isOwner && $user->can('quotes view_own')) {
            $canView = true;
        }
        if (!$canView && !$isOwner) {
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
        $isCustomer = $user->hasRole('customer'); // Definir $isCustomer
        $isOwner = $isCustomer && $quote->client && $quote->client->user_id == $user->id;

        $canEdit = false;
        if (!$isCustomer && $user->can('quotes update')) {
            $canEdit = true;
        } elseif ($isOwner && in_array($quote->status, ['draft', 'sent'])) {
            $canEdit = true;
        }

        if (!$canEdit) {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('You cannot edit this quote.'));
        }
        // Mover esta comprobación para que solo aplique si no es el dueño editando un draft/sent
        if (in_array($quote->status, ['accepted', 'invoiced', 'rejected', 'expired']) && !($isOwner && in_array($quote->status, ['draft', 'sent']))) {
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
        $user = Auth::user();
        $isCustomer = $user->hasRole('customer'); // Definir $isCustomer
        $isOwner = $isCustomer && $quote->client && $quote->client->user_id == $user->id;

        $canUpdate = false;
        if (!$isCustomer && $user->can('quotes update')) {
            $canUpdate = true;
        } elseif ($isOwner && in_array($quote->status, ['draft', 'sent'])) {
            $canUpdate = true;
        }

        if (!$canUpdate) {
            abort(403, __('This action is unauthorized.'));
        }
        if (in_array($quote->status, ['accepted', 'invoiced', 'rejected', 'expired']) && !($isOwner && in_array($quote->status, ['draft', 'sent'])) ) {
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
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
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
                'terms_and_conditions', 'notes_to_client', 'internal_notes', 'discount_id',
                'subtotal', 'discount_amount', 'tax_amount', 'total_amount'
            ]);
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
        $user = Auth::user();
        if (!$user->can('quotes delete') || $user->hasRole('customer')) {
            abort(403, __('This action is unauthorized.'));
        }

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
        $user = Auth::user();
        $canExport = $user->can('quotes export_pdf');
        $isOwner = $user->hasRole('customer') && $quote->client && $quote->client->user_id == $user->id;

        if ($isOwner && $user->can('quotes view_own')) { // Clientes pueden exportar sus propios presupuestos si tienen permiso de verlos
            $canExport = true;
        }
        if (!$canExport) {
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
        $user = Auth::user();
        if (!$user->can('quotes send_email') || $user->hasRole('customer')) {
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
        $user = Auth::user();
        if (!$user->can('quotes convert_to_invoice') || $user->hasRole('customer')) {
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
                    'line_total' => $quoteItem->line_total,
                    'sort_order' => $quoteItem->sort_order,
                ]);
            }

            $quote->status = 'invoiced';
            $quote->save();

            DB::commit();
            Log::info("Quote #{$quote->id} converted to Invoice #{$invoice->id}");
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote successfully converted to invoice! Invoice #') . $invoice->invoice_number);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error converting quote #'.$quote->id.' to invoice: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('quotes.show', $quote->id)->with('error', __('An error occurred while converting the quote to an invoice.'));
        }
    }

    /**
     * Mark the specified quote as accepted by the client.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Quote $quote)
    {
        $user = Auth::user();
        if (!$user->hasRole('customer') || !$quote->client || $quote->client->user_id !== $user->id) {
            abort(403, __('This action is unauthorized.'));
        }
        if ($quote->status !== 'sent') {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('This quote cannot be accepted as it is not in "sent" status.'));
        }

        DB::beginTransaction();
        try {
            $quote->status = 'accepted';
            $quote->save();

            if (Project::where('quote_id', $quote->id)->doesntExist()) {
                $project = Project::create([
                    'client_id' => $quote->client_id,
                    'quote_id' => $quote->id,
                    'project_title' => __('Project for Quote #') . $quote->quote_number,
                    'description' => $quote->notes_to_client ?? __('Project based on quote :number', ['number' => $quote->quote_number]),
                    'status' => 'pending',
                    'start_date' => now(),
                ]);
                Log::info("Project #{$project->id} created automatically for accepted Quote #{$quote->id}");
            }

            DB::commit();
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote has been accepted. A project may have been created.'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting quote #'.$quote->id.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return redirect()->route('quotes.show', $quote->id)->with('error', __('An error occurred while accepting the quote.'));
        }
    }

    /**
     * Mark the specified quote as rejected by the client.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Quote $quote)
    {
        $user = Auth::user();
        if (!$user->hasRole('customer') || !$quote->client || $quote->client->user_id !== $user->id) {
            abort(403, __('This action is unauthorized.'));
        }
        if ($quote->status !== 'sent') {
            return redirect()->route('quotes.show', $quote->id)->with('error', __('This quote cannot be rejected as it is not in "sent" status.'));
        }

        try {
            $quote->status = 'rejected';
            $quote->save();
            return redirect()->route('quotes.show', $quote->id)->with('success', __('Quote has been rejected.'));
        } catch (\Exception $e) {
            Log::error('Error rejecting quote #'.$quote->id.': '.$e->getMessage());
            return redirect()->route('quotes.show', $quote->id)->with('error', __('An error occurred while rejecting the quote.'));
        }
    }
        /**
     * Get detailed information for a specific quote, formatted for use in invoice creation.
     *
     * @param  \App\Models\Quote  $quote
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetailsForInvoice(Quote $quote)
    {
        // Verificar permisos: ¿Quién puede obtener estos detalles?
        // Por ahora, asumimos que si se puede crear una factura, se pueden obtener estos detalles.
        // Podrías añadir una comprobación de permisos más específica si es necesario.
        if (!Auth::user()->can('invoices create') && !Auth::user()->can('quotes show')) {
             return response()->json(['error' => __('This action is unauthorized.')], 403);
        }

        // Cargar las relaciones necesarias para asegurar que todos los datos estén disponibles
        $quote->load(['client', 'items' => function ($query) {
            $query->with('service:id,name,default_price,unit'); // Cargar servicio si existe
        }, 'discount']); // Cargar el descuento global del presupuesto, si existe

        // Formatear los items del presupuesto para que sean fácilmente consumibles por el JS de la factura
        $formattedItems = $quote->items->map(function ($item) use ($quote) {
            // La tasa de IVA para la línea de la factura se tomará del cliente del presupuesto
            $clientVatRate = $quote->client?->vat_rate ?? config('app.vat_rate', 21);
            $itemSubtotal = $item->quantity * $item->unit_price;
            // Aquí no aplicamos descuentos de línea del presupuesto a la factura directamente,
            // la factura podría tener su propia lógica de descuentos por línea si es necesario.
            // El impuesto se calculará en el frontend de la factura basado en la tasa.
            return [
                'id' => null, // Será un nuevo InvoiceItem
                'quote_item_id' => $item->id, // Para trazabilidad
                'service_id' => $item->service_id,
                'item_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'item_subtotal' => $itemSubtotal, // Subtotal de la línea antes de impuestos
                'tax_rate' => $clientVatRate, // Tasa de IVA por defecto para esta línea
                // 'line_discount_amount' => $item->line_discount_amount ?? 0, // Si los items de presupuesto tienen descuentos
                // 'line_total' => $item->line_total, // Se recalculará en la factura
            ];
        });

        return response()->json([
            'success' => true,
            'quote_id' => $quote->id,
            'client_id' => $quote->client_id,
            'client_vat_rate' => $quote->client?->vat_rate ?? config('app.vat_rate', 21),
            'currency' => $quote->currency ?? 'EUR',
            'payment_terms' => $quote->terms_and_conditions, // O un campo específico de términos de pago del presupuesto
            'notes_to_client' => $quote->notes_to_client,
            'items' => $formattedItems,
            // Pasar los totales del presupuesto como referencia, pero la factura los recalculará
            'quote_subtotal' => $quote->subtotal,
            'quote_discount_id' => $quote->discount_id, // Para preseleccionar si la factura hereda el descuento
            'quote_discount_amount' => $quote->discount_amount,
            'quote_tax_amount' => $quote->tax_amount, // Impuesto total del presupuesto
            'quote_total_amount' => $quote->total_amount,
        ]);
    }

    public function detailsForInvoice(Quote $quote, Request $request)
    {
        // Asegúrate de validar permisos según tu política
        $quote->load(['items.service','client']);

        $items = $quote->items->map(fn ($i) => [
            'service_id'       => $i->service_id,
            'item_description' => $i->item_description,
            'quantity'         => $i->quantity,
            'unit_price'       => $i->unit_price,
        ]);

        return response()->json([
            'success'           => true,
            'client_id'         => $quote->client_id,
            'client_vat_rate'   => $quote->client->vat_rate ?? config('app.vat_rate',21),
            'quote_discount_id' => $quote->discount_id,      // o null
            'payment_terms'     => $quote->payment_terms,
            'notes_to_client'   => $quote->notes_to_client,
            'items'             => $items,
        ]);
    }
}
