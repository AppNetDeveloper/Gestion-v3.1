<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Create New Invoice')" />
    </div>

    {{-- Alert start --}}
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
            <p class="font-semibold mb-2">{{ __('Please correct the following errors:') }}</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- Alert end --}}

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">{{ __('Invoice Details') }}</h3>

            <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                @csrf

                {{-- Sección Datos Generales de la Factura --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    {{-- Cliente --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Client') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="client_id" name="client_id" class="inputField select2-client w-full p-3 border {{ $errors->has('client_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="" disabled selected>{{ __('Select a client') }}</option>
                            @foreach($clients ?? [] as $client)
                                <option value="{{ $client->id }}" data-vat-rate="{{ $client->vat_rate ?? config('app.vat_rate', 21) }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Número de Factura --}}
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice #') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', 'FAC-' . date('Ymd') . '-' . rand(100,999)) }}"
                               class="inputField w-full p-3 border {{ $errors->has('invoice_number') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900" required>
                        @error('invoice_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha Factura --}}
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('invoice_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900" required>
                        @error('invoice_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Fecha Vencimiento --}}
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Due Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}"
                               class="inputField w-full p-3 border {{ $errors->has('due_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900" required>
                        @error('due_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Estado --}}
                     <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Status') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" class="inputField w-full p-3 border {{ $errors->has('status') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900" required>
                            <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>{{ __('Sent') }}</option>
                            <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                            <option value="partially_paid" {{ old('status') == 'partially_paid' ? 'selected' : '' }}>{{ __('Partially Paid') }}</option>
                            <option value="overdue" {{ old('status') == 'overdue' ? 'selected' : '' }}>{{ __('Overdue') }}</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Moneda --}}
                    <div>
                        <label for="currency" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Currency') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', 'EUR') }}"
                               class="inputField w-full p-3 border {{ $errors->has('currency') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900" required>
                        @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Presupuesto Asociado (Opcional) --}}
                    <div>
                        <label for="quote_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Quote') }} ({{ __('Optional') }})
                        </label>
                        <select id="quote_id" name="quote_id" class="inputField select2-quote w-full p-3 border {{ $errors->has('quote_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach($availableQuotes ?? [] as $quote)
                                <option value="{{ $quote->id }}" data-client-id="{{ $quote->client_id }}" {{ old('quote_id') == $quote->id ? 'selected' : '' }}>
                                    {{ $quote->quote_number }} - {{ $quote->client->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('quote_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Proyecto Asociado (Opcional) --}}
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Project') }} ({{ __('Optional') }})
                        </label>
                        <select id="project_id" name="project_id" class="inputField select2-project w-full p-3 border {{ $errors->has('project_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                             @foreach($availableProjects ?? [] as $project)
                                <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_title }} - {{ $project->client->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Descuento Global para la Factura --}}
                    <div>
                        <label for="discount_id_invoice" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Global Discount') }} ({{ __('Optional') }})
                        </label>
                        <select id="discount_id_invoice" name="discount_id_invoice" class="inputField select2-discount w-full p-3 border {{ $errors->has('discount_id_invoice') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach($discounts ?? [] as $discount)
                                <option value="{{ $discount->id }}"
                                        data-type="{{ $discount->type }}"
                                        data-value="{{ $discount->value }}"
                                        {{ old('discount_id_invoice') == $discount->id ? 'selected' : '' }}>
                                    {{ $discount->name }} ({{ $discount->type == 'percentage' ? $discount->value.'%' : number_format($discount->value, 2).'€' }})
                                </option>
                            @endforeach
                        </select>
                        @error('discount_id_invoice') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Sección Líneas de la Factura --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('Invoice Items') }}</h3>

                <div id="invoiceItemsContainer" class="space-y-4">
                    {{-- Las filas se añadirán aquí con JS --}}
                </div>

                <div class="mt-4">
                    <button type="button" id="addInvoiceItemBtn" class="inline-flex items-center px-3 py-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                        {{ __('Add Item') }}
                    </button>
                </div>

                {{-- Sección Totales y Notas --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                         <div>
                            <label for="payment_terms" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Payment Terms') }}
                            </label>
                            <textarea id="payment_terms" name="payment_terms" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('payment_terms') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">{{ old('payment_terms', __('Payment due within 30 days.')) }}</textarea>
                        </div>
                         <div>
                            <label for="notes_to_client" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Notes to Client') }}
                            </label>
                            <textarea id="notes_to_client" name="notes_to_client" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('notes_to_client') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">{{ old('notes_to_client') }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-2 text-right">
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}:</span>
                            <span id="invoiceSubtotal" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Discount Amount') }}:</span>
                            <span id="invoiceDiscountAmount" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                         <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Total Tax') }} (<span id="clientVatRateDisplay">{{ config('app.vat_rate', 21) }}</span>%):</span>
                            <span id="invoiceTaxes" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                         <hr class="my-1 border-slate-200 dark:border-slate-700">
                         <div class="flex justify-between text-lg">
                            <span class="font-bold text-slate-900 dark:text-white">{{ __('Total Amount') }}:</span>
                            <span id="invoiceTotal" class="font-bold text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                         <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                         <input type="hidden" name="discount_amount" id="inputDiscountAmount" value="0">
                         <input type="hidden" name="tax_amount" id="inputTaxAmount" value="0">
                         <input type="hidden" name="total_amount" id="inputTotalAmount" value="0">
                    </div>
                </div>

                {{-- Botones de Acción --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save Invoice') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Template para nuevas filas de items --}}
    <template id="invoiceItemTemplate">
         <div class="invoice-item-row grid grid-cols-12 gap-3 items-center border-b border-slate-200 dark:border-slate-700 pb-3">
            <input type="hidden" name="items[__INDEX__][id]" value="">
            <input type="hidden" name="items[__INDEX__][service_id]" class="item-service-id" value="">
            <div class="col-span-12 md:col-span-4">
                <input type="text" name="items[__INDEX__][item_description]" class="inputField item-description w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900" placeholder="{{ __('Item description') }}" required>
            </div>
            <div class="col-span-4 sm:col-span-2 md:col-span-1">
                <input type="number" name="items[__INDEX__][quantity]" class="inputField item-quantity w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-center" value="1" min="0.01" step="0.01" required>
            </div>
             <div class="col-span-4 sm:col-span-2 md:col-span-2">
                <input type="number" name="items[__INDEX__][unit_price]" step="0.01" min="0" class="inputField item-price w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-right" placeholder="0.00" required>
            </div>
            <div class="col-span-4 sm:col-span-2 md:col-span-2">
                <input type="number" name="items[__INDEX__][tax_rate]" step="0.01" min="0" max="100" class="inputField item-tax-rate w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-right" placeholder="{{ config('app.vat_rate', 21) }}">
            </div>
            <div class="col-span-3 md:col-span-2 text-right">
                <span class="item-total font-medium text-sm text-slate-700 dark:text-slate-200">0.00 €</span>
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 p-1">
                    <iconify-icon icon="heroicons:trash" class="text-lg"></iconify-icon>
                </button>
            </div>
        </div>
    </template>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            /* ... (Estilos Select2 como en quotes.create) ... */
            .select2-container--default .select2-selection--single { background-color: transparent !important; border: 1px solid #e2e8f0 !important; border-radius: 0.375rem !important; height: calc(1.5em + 0.75rem + 2px + 0.75rem) !important; padding-top: 0.75rem; padding-bottom: 0.75rem; }
            .dark .select2-container--default .select2-selection--single { border: 1px solid #475569 !important; background-color: #0f172a !important; }
            .select2-container--default .select2-selection--single .select2-selection__rendered { color: #0f172a !important; line-height: 1.5rem !important; padding-left: 0.75rem !important; padding-right: 2rem !important; }
            .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #cbd5e1 !important; }
            .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + 0.75rem + 2px + 0.75rem - 2px) !important; right: 0.5rem !important; }
            .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: #64748b transparent transparent transparent !important; }
            .dark .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: #94a3b8 transparent transparent transparent !important; }
            .select2-dropdown { background-color: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 0.375rem !important; }
            .dark .select2-dropdown { background-color: #1e293b !important; border: 1px solid #334155 !important; }
            .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #e2e8f0 !important; background-color: #fff !important; color: #0f172a !important; }
            .dark .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #475569 !important; background-color: #374151 !important; color: #cbd5e1 !important; }
            .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #6366f1 !important; color: white !important; }
            .select2-results__option { color: #374151 !important; }
            .dark .select2-results__option { color: #cbd5e1 !important; }
            .dark .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #4f46e5 !important; }
        </style>
    @endpush

    @push('scripts')
        {{-- NO cargar jQuery aquí si ya está globalmente en app.js --}}
        {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
        <script>
            const allAvailableQuotes = @json($availableQuotes ?? []);
            const allAvailableProjects = @json($availableProjects ?? []);
            const allDiscounts = @json($discounts ?? []);
            const defaultVatRate = {{ config('app.vat_rate', 21) }};
            let currentClientVatRate = defaultVatRate;

            $(function() {
                console.log('jQuery version:', $ ? $.fn.jquery : 'Not loaded');
                console.log('Select2 function type:', typeof $.fn.select2);

                if (typeof $.fn.select2 !== 'undefined') {
                    console.log('Select2 IS defined, attempting to initialize...');
                    $('.select2-client, .select2-quote, .select2-project, .select2-discount').each(function() {
                        const $select = $(this);
                        try {
                            $select.select2({
                                placeholder: $select.find('option[disabled]').text() || $select.find('option[value=""]').text() || 'Select an option',
                                allowClear: true,
                                width: '100%'
                            });
                            console.log('Select2 initialized for:', $select.attr('id') || $select.attr('name'));
                        } catch (e) {
                            console.error('Error initializing Select2 for:', $select.attr('id') || $select.attr('name'), e);
                        }
                    });
                } else {
                    console.warn('Select2 plugin is NOT loaded or $.fn.select2 is undefined.');
                }

                $('#client_id').on('change', function() {
                    const selectedClientOption = $(this).find('option:selected');
                    currentClientVatRate = parseFloat(selectedClientOption.data('vat-rate')) || defaultVatRate;
                    $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));
                    const clientId = $(this).val();

                    const $quoteSelect = $('#quote_id');
                    const currentQuoteVal = $quoteSelect.val();
                    $quoteSelect.empty().append(new Option(@json(__('None')), '', true, true));
                    if (clientId) {
                        allAvailableQuotes.forEach(function(quote) {
                            if (quote.client_id == clientId) {
                                $quoteSelect.append(new Option(`${quote.quote_number} - ${quote.client?.name || ''} (${parseFloat(quote.total_amount || 0).toFixed(2)}€)`, quote.id, false, quote.id == currentQuoteVal));
                            }
                        });
                    }
                    $quoteSelect.val(currentQuoteVal).trigger('change.select2');


                    const $projectSelect = $('#project_id');
                    const currentProjectVal = $projectSelect.val();
                    $projectSelect.empty().append(new Option(@json(__('None')), '', true, true));
                     if (clientId) {
                        allAvailableProjects.forEach(function(project) {
                            if (project.client_id == clientId) {
                                $projectSelect.append(new Option(`${project.project_title} - ${project.client?.name || ''}`, project.id, false, project.id == currentProjectVal));
                            }
                        });
                    }
                    $projectSelect.val(currentProjectVal).trigger('change.select2');


                    $('#invoiceItemsContainer .invoice-item-row').each(function() {
                        // Al cambiar cliente, actualizar la tasa de IVA por defecto de las líneas si no tienen una específica
                        const $taxRateInput = $(this).find('.item-tax-rate');
                        if (!$taxRateInput.data('custom-rate-set')) { // Marcar si el usuario ya puso una tasa
                            $taxRateInput.val(currentClientVatRate.toFixed(2));
                        }
                        updateLineTotal($(this));
                    });
                    calculateOverallTotals();
                }).trigger('change');

                $('#quote_id').on('select2:select', function (e) {
                    const quoteId = $(this).val();
                    if (quoteId) {
                        $.ajax({
                            url: `/quotes/${quoteId}/details-for-invoice`,
                            type: 'GET',
                            success: function(response) {
                                if (response.success) {
                                    if ($('#client_id').val() != response.client_id) {
                                        $('#client_id').val(response.client_id).trigger('change.select2');
                                    } else {
                                        currentClientVatRate = parseFloat(response.client_vat_rate) || defaultVatRate;
                                        $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));
                                    }
                                    $('#payment_terms').val(response.payment_terms || '');
                                    $('#notes_to_client').val(response.notes_to_client || '');

                                    if(response.quote_discount_id) {
                                        $('#discount_id_invoice').val(response.quote_discount_id).trigger('change.select2');
                                    } else {
                                        $('#discount_id_invoice').val("").trigger('change.select2');
                                    }

                                    itemsContainer.empty();
                                    itemIndex = 0;
                                    response.items.forEach(function(item) { addInvoiceItemRow(item); });
                                    calculateOverallTotals();
                                } else { Swal.fire('Error', response.error || 'Could not load quote details.', 'error'); }
                            },
                            error: function() { Swal.fire('Error', 'Error fetching quote details.', 'error'); }
                        });
                    } else {
                        itemsContainer.empty(); itemIndex = 0; addInvoiceItemRow(); calculateOverallTotals();
                    }
                });

                const itemsContainer = $('#invoiceItemsContainer');
                const addItemBtn = $('#addInvoiceItemBtn');
                const itemTemplateHtml = $('#invoiceItemTemplate').html();
                let itemIndex = 0;

                function addInvoiceItemRow(itemData = null) {
                    const currentIndex = itemIndex;
                    const newItemHtml = itemTemplateHtml.replace(/__INDEX__/g, currentIndex);
                    const $newRow = $(newItemHtml);

                    if (itemData) {
                        $newRow.find('.item-service-id').val(itemData.service_id || '');
                        $newRow.find('.item-description').val(itemData.item_description || '');
                        $newRow.find('.item-quantity').val(itemData.quantity || 1);
                        $newRow.find('.item-price').val(parseFloat(itemData.unit_price || 0).toFixed(2));
                        $newRow.find('.item-tax-rate').val(parseFloat(itemData.tax_rate !== undefined ? itemData.tax_rate : currentClientVatRate).toFixed(2));
                    } else {
                        $newRow.find('.item-tax-rate').val(currentClientVatRate.toFixed(2));
                    }

                    itemsContainer.append($newRow);
                    initializeRowEvents($newRow);
                    updateLineTotal($newRow);
                    itemIndex++;
                }

                function initializeRowEvents($row) {
                    $row.find('.item-quantity, .item-price, .item-tax-rate').on('input change', function() {
                        if ($(this).hasClass('item-tax-rate')) {
                            $(this).data('custom-rate-set', true); // Marcar que el usuario cambió la tasa
                        }
                        updateLineTotal($row);
                        calculateOverallTotals();
                    });
                    $row.find('.remove-item-btn').on('click', function() {
                        $(this).closest('.invoice-item-row').remove();
                        calculateOverallTotals();
                         if ($('#invoiceItemsContainer .invoice-item-row').length === 0) {
                            addInvoiceItemRow();
                        }
                    });
                }

                function updateLineTotal($row) {
                    const quantity = parseFloat($row.find('.item-quantity').val()) || 0;
                    const price = parseFloat($row.find('.item-price').val()) || 0;
                    const taxRate = parseFloat($row.find('.item-tax-rate').val()) || 0;
                    const lineSubtotal = quantity * price;
                    const lineTaxAmount = lineSubtotal * (taxRate / 100);
                    const lineTotal = lineSubtotal + lineTaxAmount;
                    $row.find('.item-total').text(lineTotal.toFixed(2) + ' €');
                }

                function calculateOverallTotals() {
                    let overallSubtotal = 0;
                    let overallTaxAmount = 0;

                    $('#invoiceItemsContainer .invoice-item-row').each(function() {
                        const $row = $(this);
                        const quantity = parseFloat($row.find('.item-quantity').val()) || 0;
                        const price = parseFloat($row.find('.item-price').val()) || 0;
                        const taxRate = parseFloat($row.find('.item-tax-rate').val()) || 0;
                        const lineSubtotal = quantity * price;
                        const lineTaxAmount = lineSubtotal * (taxRate / 100);
                        overallSubtotal += lineSubtotal;
                        overallTaxAmount += lineTaxAmount;
                    });

                    let discountAmount = 0;
                    const selectedDiscountOption = $('#discount_id_invoice').find('option:selected');
                    if (selectedDiscountOption.length && selectedDiscountOption.val()) {
                        const discountType = selectedDiscountOption.data('type');
                        const discountValue = parseFloat(selectedDiscountOption.data('value')) || 0;
                        if (discountType === 'percentage') {
                            discountAmount = overallSubtotal * (discountValue / 100);
                        } else {
                            discountAmount = discountValue;
                        }
                        discountAmount = Math.min(overallSubtotal, discountAmount);
                    }

                    const taxableBase = overallSubtotal - discountAmount;
                    const totalAmount = taxableBase + overallTaxAmount;

                    $('#invoiceSubtotal').text(overallSubtotal.toFixed(2) + ' €');
                    $('#invoiceDiscountAmount').text(discountAmount.toFixed(2) + ' €');
                    $('#invoiceTaxes').text(overallTaxAmount.toFixed(2) + ' €');
                    $('#invoiceTotal').text(totalAmount.toFixed(2) + ' €');
                    $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));

                    $('#inputSubtotal').val(overallSubtotal.toFixed(2));
                    $('#inputDiscountAmount').val(discountAmount.toFixed(2));
                    $('#inputTaxAmount').val(overallTaxAmount.toFixed(2));
                    $('#inputTotalAmount').val(totalAmount.toFixed(2));
                }

                $('#discount_id_invoice').on('change', calculateOverallTotals);
                addItemBtn.on('click', function() { addInvoiceItemRow(); });

                if ($('#invoiceItemsContainer .invoice-item-row').length === 0) {
                    addInvoiceItemRow();
                } else {
                     $('#invoiceItemsContainer .invoice-item-row').each(function() {
                        initializeRowEvents($(this));
                        updateLineTotal($(this));
                    });
                }
                calculateOverallTotals();
            });
        </script>
    @endpush
</x-app-layout>
