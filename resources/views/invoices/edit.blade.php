<x-app-layout>
    {{-- =========================================================
        BREADCRUMB
    ========================================================= --}}
    <div class="mb-6">
        <x-breadcrumb
            :breadcrumb-items="$breadcrumbItems ?? []"
            :page-title="__('Edit Invoice') . ': ' . $invoice->invoice_number"/>
    </div>

    {{-- =========================================================
        ALERTAS
    ========================================================= --}}
    @if (session('error'))
        <x-alert :message="session('error')" type="danger" />
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

    {{-- =========================================================
        FORMULARIO
    ========================================================= --}}
    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">
                {{ __('Edit Invoice Details') }}
            </h3>

            <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" id="invoiceForm">
                @csrf
                @method('PUT')

                {{-- ================= DATOS GENERALES ================= --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">

                    {{-- Cliente --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Client') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="client_id" name="client_id"
                                class="inputField select2-main w-full p-3 border {{ $errors->has('client_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900"
                                required>
                            <option value="" disabled>{{ __('Select a client') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                        data-vat-rate="{{ $client->vat_rate ?? config('app.vat_rate',21) }}"
                                        @selected(old('client_id', $invoice->client_id) == $client->id)>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Nº Factura --}}
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice #') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="invoice_number" name="invoice_number" type="text"
                               value="{{ old('invoice_number', $invoice->invoice_number) }}"
                               class="inputField w-full p-3 border {{ $errors->has('invoice_number') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900"
                               required>
                        @error('invoice_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Fecha factura --}}
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="invoice_date" name="invoice_date" type="date"
                               value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('invoice_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900"
                               required>
                        @error('invoice_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Fecha vencimiento --}}
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Due Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="due_date" name="due_date" type="date"
                               value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('due_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900"
                               required>
                        @error('due_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Estado --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Status') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status"
                                class="inputField w-full p-3 border {{ $errors->has('status') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900"
                                required>
                            @foreach (['draft','sent','paid','partially_paid','overdue','cancelled'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $invoice->status) == $st)>
                                    {{ __(ucfirst(str_replace('_',' ',$st))) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Moneda --}}
                    <div>
                        <label for="currency" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Currency') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="currency" name="currency" type="text"
                               value="{{ old('currency', $invoice->currency) }}"
                               class="inputField w-full p-3 border {{ $errors->has('currency') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900"
                               required>
                        @error('currency')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Presupuesto --}}
                    <div>
                        <label for="quote_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Quote') }} ({{ __('Optional') }})
                        </label>
                        <select id="quote_id" name="quote_id"
                                class="inputField select2-main w-full p-3 border {{ $errors->has('quote_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($availableQuotes as $quote)
                                <option value="{{ $quote->id }}" data-client-id="{{ $quote->client_id }}"
                                        @selected(old('quote_id', $invoice->quote_id) == $quote->id)>
                                    {{ $quote->quote_number }} - {{ $quote->client->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('quote_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Proyecto --}}
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Associated Project') }} ({{ __('Optional') }})
                        </label>
                        <select id="project_id" name="project_id"
                                class="inputField select2-main w-full p-3 border {{ $errors->has('project_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($availableProjects as $project)
                                <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}"
                                        @selected(old('project_id', $invoice->project_id) == $project->id)>
                                    {{ $project->project_title }} - {{ $project->client->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Descuento global --}}
                    <div>
                        <label for="discount_id_invoice" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Global Discount') }} ({{ __('Optional') }})
                        </label>
                        <select id="discount_id_invoice" name="discount_id_invoice"
                                class="inputField select2-main w-full p-3 border {{ $errors->has('discount_id_invoice') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($discounts as $discount)
                                <option value="{{ $discount->id }}"
                                        data-type="{{ $discount->type }}"
                                        data-value="{{ $discount->value }}"
                                        @selected(old('discount_id_invoice', $invoice->discount_id) == $discount->id)>
                                    {{ $discount->name }}
                                    ({{ $discount->type === 'percentage' ? $discount->value.'%' : number_format($discount->value,2).'€' }})
                                </option>
                            @endforeach
                        </select>
                        @error('discount_id_invoice')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                </div>{{-- /grid datos generales --}}

                {{-- ================= LÍNEAS ================= --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">
                    {{ __('Invoice Items') }}
                </h3>

                <div id="invoiceItemsContainer" class="space-y-4"></div>

                <button type="button" id="addInvoiceItemBtn"
                        class="mt-2 inline-flex items-center px-3 py-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600">
                    <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                    {{ __('Add Item') }}
                </button>

                {{-- ================= TOTALES / NOTAS ================= --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Notas --}}
                    <div class="space-y-4">
                        <div>
                            <label for="payment_terms" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Payment Terms') }}
                            </label>
                            <textarea id="payment_terms" name="payment_terms" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('payment_terms') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">{{ old('payment_terms', $invoice->payment_terms) }}</textarea>
                        </div>

                        <div>
                            <label for="notes_to_client" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Notes to Client') }}
                            </label>
                            <textarea id="notes_to_client" name="notes_to_client" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('notes_to_client') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900">{{ old('notes_to_client', $invoice->notes_to_client) }}</textarea>
                        </div>
                    </div>

                    {{-- Totales --}}
                    <div class="space-y-2 text-right">
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}:</span>
                            <span id="invoiceSubtotal" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Discount Amount') }}:</span>
                            <span id="invoiceDiscountAmount" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">
                                {{ __('Total Tax') }} (<span id="clientVatRateDisplay">{{ $invoice->client->vat_rate ?? config('app.vat_rate',21) }}</span>%):
                            </span>
                            <span id="invoiceTaxes" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                        <hr class="my-1 border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-slate-900 dark:text-white">{{ __('Total Amount') }}:</span>
                            <span id="invoiceTotal" class="font-bold text-slate-900 dark:text-white">0.00 €</span>
                        </div>

                        {{-- Hidden totals --}}
                        <input type="hidden" name="client_vat_rate" id="inputClientVatRate"
                               value="{{ $invoice->client->vat_rate ?? config('app.vat_rate',21) }}">
                        <input type="hidden" name="subtotal"        id="inputSubtotal"
                               value="{{ old('subtotal', $invoice->subtotal) }}">
                        <input type="hidden" name="discount_amount" id="inputDiscountAmount"
                               value="{{ old('discount_amount', $invoice->discount_amount) }}">
                        <input type="hidden" name="tax_amount"      id="inputTaxAmount"
                               value="{{ old('tax_amount', $invoice->tax_amount) }}">
                        <input type="hidden" name="total_amount"    id="inputTotalAmount"
                               value="{{ old('total_amount', $invoice->total_amount) }}">
                    </div>
                </div>

                {{-- Botones --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Update Invoice') }}
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- =========================================================
        TEMPLATE FILA
    ========================================================= --}}
    <template id="invoiceItemTemplate">
        <div class="invoice-item-row grid grid-cols-12 gap-3 items-center border-b border-slate-200 dark:border-slate-700 pb-3">
            <input type="hidden" name="items[__INDEX__][id]" class="item-id" value="">
            <div class="col-span-12 md:col-span-5">
                <select name="items[__INDEX__][service_id]"
                        class="inputField item-service select2-item-service w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900">
                    <option value="">{{ __('Select a service or type description') }}</option>
                    @foreach ($services as $service)
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
                       class="inputField item-quantity w-full p-2 text-sm text-center border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900"
                       required>
            </div>
            <div class="col-span-4 sm:col-span-2 md:col-span-2">
                <input type="number" name="items[__INDEX__][unit_price]" step="0.01" min="0"
                       class="inputField item-price w-full p-2 text-sm text-right border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900"
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

    {{-- =========================================================
        ASSETS
    ========================================================= --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
    @endpush

    @once
        @push('scripts')
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
            <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

            <script>
                $(function () {
                    /* ---------- Datos del backend ---------- */
                    const quotes        = @json($availableQuotes);
                    const projects      = @json($availableProjects);
                    const services      = @json($services);
                    const discountsList = @json($discounts);
                    const existingItems = @json($invoiceItems ?? []);

                    /* ---------- Vat desde back ---------- */
                    const defaultVatRate = {{ config('app.vat_rate',21) }};

                    function readVat($option){
                        const v = parseFloat($option.data('vat-rate'));
                        return isNaN(v) ? defaultVatRate : v;   // 👈 NO reemplaza el 0
                    }

                    let currentVatRate = readVat($('#client_id option:selected'));

                    $('#clientVatRateDisplay').text(currentVatRate.toFixed(2));
                    $('#inputClientVatRate').val(currentVatRate);
                    console.log('[INIT] option:selected data-vat-rate =',
                                $('#client_id option:selected').data('vat-rate'),
                                '→ currentVatRate =', currentVatRate);
                    /* ------------ Select2 helpers ------------ */
                    function initSelect2($el){
                        if ($.fn.select2){
                            $el.select2({width:'100%', placeholder:$el.find('option[value=""]').text()});
                        }
                    }
                    $('.select2-main').each(function(){ initSelect2($(this)); });

                    /* ------------ Cliente cambia ------------ */
                    $('#client_id').on('change select2:select', function () {

                    // lee el atributo
                    let vat = parseFloat($(this).find(':selected').data('vat-rate'));

                        // solo usa el valor por defecto si ES NaN (undefined, null, etc.),
                        // pero NO si el valor real es 0
                        if (isNaN(vat)) vat = defaultVatRate;
                        currentVatRate = vat;

                        console.log('currentVatRate:', currentVatRate);   // 0, 7, 15, 21…

                        $('#clientVatRateDisplay').text(currentVatRate.toFixed(2));
                        $('#inputClientVatRate').val(currentVatRate);

                        const cid = $(this).val();
                        reloadSelect('#quote_id', quotes,   cid, 'quote_number');
                        reloadSelect('#project_id', projects, cid, 'project_title');

                        recalcTotals();
                    });


                    function reloadSelect(sel, list, cid, label){
                        const $s = $(sel);
                        const keep = $s.val();
                        $s.empty().append(new Option(@json(__('None')), ''));
                        list.forEach(el=>{
                            if (String(el.client_id) === String(cid)){
                                $s.append(new Option(`${el[label]} - ${(el.client?.name || '')}`, el.id));
                            }
                        });
                        if ($s.find(`option[value="${keep}"]`).length) $s.val(keep);
                        $s.trigger('change.select2');
                    }

                    /* ------------ Items ------------ */
                    const tpl = $('#invoiceItemTemplate').html();
                    let idx   = 0;
                    function addRow(data={}){
                        const html = tpl.replace(/__INDEX__/g, idx);
                        const $r   = $(html);
                        if (data.id)               $r.find('.item-id').val(data.id);
                        if (data.service_id)       $r.find('.item-service').val(data.service_id);
                        $r.find('.item-description').val(data.item_description || '');
                        $r.find('.item-quantity').val(data.quantity || 1);
                        $r.find('.item-price').val(parseFloat(data.unit_price || 0).toFixed(2));

                        $('#invoiceItemsContainer').append($r);
                        initSelect2($r.find('.item-service'));
                        bindRow($r);
                        updateLine($r);
                        idx++;
                    }
                    function bindRow($r){
                        $r.find('.item-service').on('change', function(){
                            const $o = $(this).find(':selected');
                            $r.find('.item-description').val($o.data('description')||'');
                            $r.find('.item-price').val(parseFloat($o.data('price')||0).toFixed(2));
                            updateLine($r); recalcTotals();
                        });
                        $r.find('.item-quantity, .item-price').on('input change', ()=>{ updateLine($r); recalcTotals(); });
                        $r.find('.remove-item-btn').on('click', ()=>{
                            $r.remove(); if (!$('.invoice-item-row').length) addRow(); recalcTotals();
                        });
                    }
                    function updateLine($r){
                        const q=parseFloat($r.find('.item-quantity').val())||0;
                        const p=parseFloat($r.find('.item-price').val())||0;
                        $r.find('.item-total').text((q*p).toFixed(2)+' €');
                    }

                    /* ------------ Totales ------------ */
                    $('#discount_id_invoice').on('change', recalcTotals);
                    $('#addInvoiceItemBtn').on('click', ()=>addRow());

                    function recalcTotals(){
                        let subtotal=0;
                        $('.invoice-item-row').each(function(){
                            const q=parseFloat($(this).find('.item-quantity').val())||0;
                            const p=parseFloat($(this).find('.item-price').val())||0;
                            subtotal += q*p;
                        });

                        let discountAmt=0;
                        const $dSel=$('#discount_id_invoice').find(':selected');
                        if ($dSel.val()){
                            const t=$dSel.data('type'), v=parseFloat($dSel.data('value'))||0;
                            discountAmt = t==='percentage' ? subtotal*(v/100) : v;
                            discountAmt = Math.min(discountAmt, subtotal);
                        }

                        const base=subtotal-discountAmt;
                        const taxes=base*(currentVatRate/100);
                        const total=base+taxes;

                        $('#invoiceSubtotal').text(subtotal.toFixed(2)+' €');
                        $('#invoiceDiscountAmount').text(discountAmt.toFixed(2)+' €');
                        $('#invoiceTaxes').text(taxes.toFixed(2)+' €');
                        $('#invoiceTotal').text(total.toFixed(2)+' €');

                        $('#inputSubtotal').val(subtotal.toFixed(2));
                        $('#inputDiscountAmount').val(discountAmt.toFixed(2));
                        $('#inputTaxAmount').val(taxes.toFixed(2));
                        $('#inputTotalAmount').val(total.toFixed(2));
                    }
                    recalcTotals();
                    /* ------------ Cargar líneas existentes ------------ */
                    if (existingItems.length){
                        existingItems.forEach(addRow);
                    } else {
                        addRow();
                    }
                    recalcTotals();
                });
            </script>
        @endpush
    @endonce
</x-app-layout>
