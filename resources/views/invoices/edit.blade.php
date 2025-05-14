<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- $invoice, $breadcrumbItems se pasarán desde InvoiceController@edit --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Edit Invoice') . ': ' . $invoice->invoice_number" />
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
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">{{ __('Edit Invoice Details') }}</h3>

            <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" id="invoiceForm">
                @csrf
                @method('PUT') {{-- Método para actualizar --}}

                {{-- Sección Datos Generales de la Factura --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    {{-- Cliente --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Client') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="client_id" name="client_id"
                                class="inputField select2-main w-full p-3 {{ $errors->has('client_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900"
                                required>
                            <option value="" disabled>{{ __('Select a client') }}</option>
                            @foreach (($clients ?? []) as $client)
                                <option value="{{ $client->id }}"
                                        data-vat-rate="{{ $client->vat_rate ?? config('app.vat_rate',21) }}"
                                        {{ old('client_id', $invoice->client_id) == $client->id ? 'selected' : '' }}>
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
                        <input id="invoice_number" name="invoice_number" type="text"
                               value="{{ old('invoice_number', $invoice->invoice_number) }}"
                               class="inputField w-full p-3 {{ $errors->has('invoice_number') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900" required>
                        @error('invoice_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha Factura --}}
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="invoice_date" name="invoice_date" type="date"
                               value="{{ old('invoice_date', $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '') }}"
                               class="inputField w-full p-3 {{ $errors->has('invoice_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900" required>
                        @error('invoice_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Fecha Vencimiento --}}
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Due Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="due_date" name="due_date" type="date"
                               value="{{ old('due_date', $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '') }}"
                               class="inputField w-full p-3 {{ $errors->has('due_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900" required>
                        @error('due_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Estado --}}
                     <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Status') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status"
                                class="inputField w-full p-3 {{ $errors->has('status') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900"
                                required>
                            @foreach (['draft','sent','paid','partially_paid','overdue','cancelled'] as $statusOption)
                                <option value="{{ $statusOption }}" {{ old('status', $invoice->status) == $statusOption ? 'selected' : '' }}>
                                    {{ __(ucfirst(str_replace('_',' ',$statusOption))) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label for="currency" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Currency') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="currency" name="currency" type="text"
                               value="{{ old('currency', $invoice->currency) }}"
                               class="inputField w-full p-3 {{ $errors->has('currency') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900" required>
                        @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Quote opcional --}}
                    <div>
                        <label for="quote_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Quote') }} ({{ __('Optional') }})
                        </label>
                        <select id="quote_id" name="quote_id"
                                class="inputField select2-main w-full p-3 {{ $errors->has('quote_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach($availableQuotes ?? [] as $quote)
                                <option value="{{ $quote->id }}" data-client-id="{{ $quote->client_id }}" {{ old('quote_id', $invoice->quote_id) == $quote->id ? 'selected' : '' }}>
                                    {{ $quote->quote_number }} - {{ $quote->client->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('quote_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Project opcional --}}
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Project') }} ({{ __('Optional') }})
                        </label>
                        <select id="project_id" name="project_id"
                                class="inputField select2-main w-full p-3 {{ $errors->has('project_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                             @foreach($availableProjects ?? [] as $project)
                                <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" {{ old('project_id', $invoice->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_title }} - {{ $project->client->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Discount global --}}
                    <div>
                        <label for="discount_id_invoice" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Global Discount') }} ({{ __('Optional') }})
                        </label>
                        <select id="discount_id_invoice" name="discount_id_invoice"
                                class="inputField select2-main w-full p-3 {{ $errors->has('discount_id_invoice') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach (($discounts ?? []) as $discount)
                                <option value="{{ $discount->id }}"
                                        data-type="{{ $discount->type }}"
                                        data-value="{{ $discount->value }}"
                                        {{ old('discount_id_invoice', $invoice->discount_id) == $discount->id ? 'selected' : '' }}>
                                    {{ $discount->name }}
                                    ({{ $discount->type == 'percentage' ? $discount->value.'%' : number_format($discount->value,2).'€' }})
                                </option>
                            @endforeach
                        </select>
                        @error('discount_id_invoice') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ================= LÍNEAS ================= --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('Invoice Items') }}</h3>

                <div id="invoiceItemsContainer" class="space-y-4">
                    {{-- Las filas existentes o nuevas se añadirán aquí con JS --}}
                </div>

                <div class="mt-4">
                    <button type="button" id="addInvoiceItemBtn"
                            class="inline-flex items-center px-3 py-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600">
                        <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                        {{ __('Add Item') }}
                    </button>
                </div>

                {{-- ================= TOTALES/NOTAS ================= --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label for="payment_terms" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Payment Terms') }}
                            </label>
                            <textarea id="payment_terms" name="payment_terms" rows="3"
                                      class="inputField w-full p-3 {{ $errors->has('payment_terms') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">{{ old('payment_terms', $invoice->payment_terms) }}</textarea>
                        </div>
                        <div>
                            <label for="notes_to_client" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Notes to Client') }}
                            </label>
                            <textarea id="notes_to_client" name="notes_to_client" rows="3"
                                      class="inputField w-full p-3 {{ $errors->has('notes_to_client') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">{{ old('notes_to_client', $invoice->notes_to_client) }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-2 text-right">
                        <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}:</span><span id="invoiceSubtotal" class="font-medium text-slate-900 dark:text-white">0.00 €</span></div>
                        <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">{{ __('Discount Amount') }}:</span><span id="invoiceDiscountAmount" class="font-medium text-slate-900 dark:text-white">0.00 €</span></div>
                        <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">{{ __('Total Tax') }} (<span id="clientVatRateDisplay">{{ $invoice->client->vat_rate ?? config('app.vat_rate',21) }}</span>%):</span><span id="invoiceTaxes" class="font-medium text-slate-900 dark:text-white">0.00 €</span></div>
                        <hr class="my-1 border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between text-lg"><span class="font-bold text-slate-900 dark:text-white">{{ __('Total Amount') }}:</span><span id="invoiceTotal" class="font-bold text-slate-900 dark:text-white">0.00 €</span></div>

                        <input type="hidden" name="client_vat_rate" id="inputClientVatRate" value="{{ $invoice->client->vat_rate ?? config('app.vat_rate',21) }}">
                        <input type="hidden" name="subtotal"        id="inputSubtotal"      value="{{ old('subtotal', $invoice->subtotal) }}">
                        <input type="hidden" name="discount_amount" id="inputDiscountAmount" value="{{ old('discount_amount', $invoice->discount_amount) }}">
                        <input type="hidden" name="tax_amount"      id="inputTaxAmount"      value="{{ old('tax_amount', $invoice->tax_amount) }}">
                        <input type="hidden" name="total_amount"    id="inputTotalAmount"    value="{{ old('total_amount', $invoice->total_amount) }}">
                    </div>
                </div>

                {{-- Botones --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update Invoice') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =============== TEMPLATE FILA =============== --}}
    <template id="invoiceItemTemplate">
        <div class="invoice-item-row grid grid-cols-12 gap-3 items-center border-b border-slate-200 dark:border-slate-700 pb-3">
            <input type="hidden" name="items[__INDEX__][id]" class="item-id" value=""> {{-- Para el ID del item existente --}}
            <div class="col-span-12 md:col-span-5">
                <select name="items[__INDEX__][service_id]"
                        class="inputField item-service select2-item-service w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900">
                    <option value="">{{ __('Select a service or type description') }}</option>
                    @foreach (($services ?? []) as $service)
                        <option value="{{ $service->id }}"
                                data-price="{{ $service->default_price }}"
                                data-description="{{ e($service->description ?? $service->name) }}">
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-12 md:col-span-3">
                <input type="text" name="items[__INDEX__][item_description]"
                       class="inputField item-description w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900"
                       placeholder="{{ __('Item description') }}" required>
            </div>
            <div class="col-span-4 sm:col-span-2 md:col-span-1">
                <input type="number" name="items[__INDEX__][quantity]" value="1" min="0.01" step="0.01"
                       class="inputField item-quantity w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-center" required>
            </div>
            <div class="col-span-4 sm:col-span-2 md:col-span-2">
                <input type="number" name="items[__INDEX__][unit_price]" step="0.01" min="0"
                       class="inputField item-price w-full p-2 text-sm border border-slate-300 dark;border-slate-600 rounded-md dark:bg-slate-900 text-right"
                       placeholder="0.00" required>
            </div>
            <div class="col-span-3 md:col-span-1 text-right">
                <span class="item-total font-medium text-sm text-slate-700 dark:text-slate-200">0.00 €</span>
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 p-1">
                    <iconify-icon icon="heroicons:trash" class="text-lg"></iconify-icon>
                </button>
            </div>
        </div>
    </template>

    {{-- =============== ASSETS =============== --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
        <style>
            /* Estilos Select2 */
        </style>
    @endpush

    @once
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
        $(function () {
            const allAvailableQuotes   = @json($availableQuotes   ?? []);
            const allAvailableProjects = @json($availableProjects ?? []);
            const allDiscounts         = @json($discounts         ?? []);
            const allServices          = @json($services          ?? []); // Necesario para los items
            const defaultVatRate       = {{ config('app.vat_rate',21) }};
            let   currentClientVatRate = parseFloat($('#client_id').find('option:selected').data('vat-rate')) || defaultVatRate;
            if (isNaN(currentClientVatRate)) currentClientVatRate = defaultVatRate;

            function initSelect2($element) {
                if (typeof $.fn.select2 === 'undefined') { return; }
                const placeholderText = $element.find('option[value=""]').first().text() ||
                                      $element.find('option[disabled]').first().text() ||
                                      'Select an option';
                $element.select2({ placeholder: placeholderText, allowClear: !$element.prop('multiple'), width: '100%' });
            }

            $('.select2-main').each(function(){ initSelect2($(this)); });

            $('#client_id').on('change', function () {
                const selectedClientOption = $(this).find('option:selected');
                currentClientVatRate = parseFloat(selectedClientOption.data('vat-rate')) || defaultVatRate;
                $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));
                $('#inputClientVatRate').val(currentClientVatRate);
                const clientId = $(this).val();

                const $quoteSelect = $('#quote_id');
                const oldQuoteVal = "{{ old('quote_id', $invoice->quote_id ?? '') }}";
                let currentQuoteSelectedValue = $quoteSelect.val();
                $quoteSelect.empty().append(new Option(@json(__('None')), '', !oldQuoteVal && !currentQuoteSelectedValue, !oldQuoteVal && !currentQuoteSelectedValue));
                if (clientId) {
                    allAvailableQuotes.forEach(function(quote) {
                        if (String(quote.client_id) === String(clientId)) {
                            $quoteSelect.append(new Option(`${quote.quote_number} - ${quote.client?.name || ''} (${parseFloat(quote.total_amount || 0).toFixed(2)}€)`, quote.id));
                        }
                    });
                }
                if (oldQuoteVal && $quoteSelect.find('option[value="' + oldQuoteVal + '"]').length > 0) { $quoteSelect.val(oldQuoteVal); }
                else if (currentQuoteSelectedValue && $quoteSelect.find('option[value="' + currentQuoteSelectedValue + '"]').length > 0 && $quoteSelect.find('option[value="' + currentQuoteSelectedValue + '"]').data('client-id') == clientId){}
                else { $quoteSelect.val(''); }
                $quoteSelect.trigger('change.select2');

                const $projectSelect = $('#project_id');
                const oldProjectVal = "{{ old('project_id', $invoice->project_id ?? '') }}";
                let currentProjectSelectedValue = $projectSelect.val();
                $projectSelect.empty().append(new Option(@json(__('None')), '', !oldProjectVal && !currentProjectSelectedValue, !oldProjectVal && !currentProjectSelectedValue));
                 if (clientId) {
                    allAvailableProjects.forEach(function(project) {
                        if (String(project.client_id) === String(clientId)) {
                            $projectSelect.append(new Option(`${project.project_title} - ${project.client?.name || ''}`, project.id));
                        }
                    });
                }
                if (oldProjectVal && $projectSelect.find('option[value="' + oldProjectVal + '"]').length > 0) { $projectSelect.val(oldProjectVal); }
                else if (currentProjectSelectedValue && $projectSelect.find('option[value="' + currentProjectSelectedValue + '"]').length > 0 && $projectSelect.find('option[value="' + currentProjectSelectedValue + '"]').data('client-id') == clientId){}
                else { $projectSelect.val(''); }
                $projectSelect.trigger('change.select2');

                calculateOverallTotals();
            }).trigger('change');

            $('#quote_id').on('change', function () { // Cambiado de select2:select a change
                const quoteId = $(this).val();
                const itemsContainer = $('#invoiceItemsContainer');
                if (quoteId) {
                    $.get(`/quotes/${quoteId}/details-for-invoice`, function (response) {
                        if (response.success) {
                            if ($('#client_id').val() != response.client_id) {
                                $('#client_id').val(response.client_id).trigger('change'); // Disparar change normal
                            } else {
                                currentClientVatRate = parseFloat(response.client_vat_rate) || defaultVatRate;
                                $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));
                                $('#inputClientVatRate').val(currentClientVatRate);
                            }
                            $('#payment_terms').val(response.payment_terms || '');
                            $('#notes_to_client').val(response.notes_to_client || '');

                            if (response.quote_discount_id) {
                                $('#discount_id_invoice').val(response.quote_discount_id).trigger('change.select2');
                            } else {
                                $('#discount_id_invoice').val("").trigger('change.select2');
                            }

                            itemsContainer.empty();
                            itemIndex = 0;
                            response.items.forEach(function (item) { addInvoiceItemRow(item); });
                            calculateOverallTotals();
                        } else { Swal.fire('Error', response.error || 'Could not load quote details.', 'error'); }
                    }).fail(function () { Swal.fire('Error', 'Error fetching quote details.', 'error'); });
                } else {
                    // No limpiar items si se deselecciona, para permitir entrada manual
                    // itemsContainer.empty(); itemIndex = 0; addInvoiceItemRow();
                    calculateOverallTotals();
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
                const $serviceSelect = $newRow.find('.item-service');

                // Las opciones de servicio ya están en el template gracias al @foreach
                if (itemData) {
                    if (itemData.service_id) {
                        $serviceSelect.val(itemData.service_id);
                        const selectedServiceData = $serviceSelect.find('option:selected');
                        $newRow.find('.item-description').val(itemData.item_description || selectedServiceData.data('description') || selectedServiceData.text());
                        $newRow.find('.item-price').val(parseFloat(itemData.unit_price || selectedServiceData.data('price') || 0).toFixed(2));
                    } else {
                        $newRow.find('.item-description').val(itemData.item_description || '');
                        $newRow.find('.item-price').val(parseFloat(itemData.unit_price || 0).toFixed(2));
                    }
                    $newRow.find('.item-quantity').val(itemData.quantity || 1);
                }

                itemsContainer.append($newRow);
                initSelect2($serviceSelect); // Inicializar Select2 para el nuevo selector
                initializeRowEvents($newRow);
                updateLineTotal($newRow);
                itemIndex++;
            }

            function initializeRowEvents($row) {
                $row.find('.item-service').on('change', function (e) { // Cambiado a 'change'
                    const selectedServiceOption = $(this).find('option:selected');
                    if (selectedServiceOption.length && selectedServiceOption.val()) {
                        const price = selectedServiceOption.data('price') || '0.00';
                        const description = selectedServiceOption.data('description') || selectedServiceOption.text();
                        $row.find('.item-description').val(description);
                        $row.find('.item-price').val(parseFloat(price).toFixed(2));
                    } else {
                        $row.find('.item-description').val('');
                        $row.find('.item-price').val('0.00');
                    }
                    updateLineTotal($row);
                    calculateOverallTotals();
                });

                $row.find('.item-quantity, .item-price').on('input change', function () {
                    updateLineTotal($row);
                    calculateOverallTotals();
                });
                $row.find('.remove-item-btn').on('click', function () {
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
                const lineSubtotal = quantity * price;
                $row.find('.item-total').text(lineSubtotal.toFixed(2) + ' €');
            }

            function calculateOverallTotals() {
                let overallSubtotal = 0;
                $('#invoiceItemsContainer .invoice-item-row').each(function () {
                    const $row = $(this);
                    const quantity = parseFloat($row.find('.item-quantity').val()) || 0;
                    const price = parseFloat($row.find('.item-price').val()) || 0;
                    overallSubtotal += quantity * price;
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
                const overallTaxAmount = taxableBase * (currentClientVatRate / 100);
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
            addItemBtn.on('click', function () { addInvoiceItemRow(); });

            const oldItems = @json(old('items'));
            if (oldItems && oldItems.length > 0) {
                oldItems.forEach(function (itemData) { addInvoiceItemRow(itemData); });
            } else if ($('#invoiceItemsContainer .invoice-item-row').length === 0) {
                addInvoiceItemRow();
            }
            calculateOverallTotals();
        });
        </script>
    @endpush
    @endonce
</x-app-layout>