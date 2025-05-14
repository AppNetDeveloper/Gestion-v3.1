{{-- resources/views/invoices/create.blade.php --}}
<x-app-layout>
    <div class="mb-6">
        <x-breadcrumb :breadcrumb-items="($breadcrumbItems ?? [])" :page-title="__('Create New Invoice')" />
    </div>

    {{-- Alerts --}}
    @if (session('error'))  <x-alert :message="session('error')" :type="'danger'" />  @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
            <p class="font-semibold mb-2">{{ __('Please correct the following errors:') }}</p>
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">{{ __('Invoice Details') }}</h3>

            <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                @csrf

                {{-- ================= CABECERA ================= --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    {{-- Cliente --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Client') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="client_id" name="client_id"
                                class="inputField select2-main w-full p-3 {{ $errors->has('client_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900"
                                required>
                            <option value="" disabled {{ old('client_id') ? '' : 'selected' }}>{{ __('Select a client') }}</option>
                            @foreach (($clients ?? []) as $client)
                                <option value="{{ $client->id }}"
                                        data-vat-rate="{{ $client->vat_rate ?? config('app.vat_rate',21) }}"
                                        {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Nº Factura --}}
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice #') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="invoice_number" name="invoice_number" type="text"
                               value="{{ old('invoice_number', 'FAC-'.date('Ymd').'-'.rand(100,999)) }}"
                               class="inputField w-full p-3 {{ $errors->has('invoice_number') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900" required>
                        @error('invoice_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fechas --}}
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Invoice Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="invoice_date" name="invoice_date" type="date"
                               value="{{ old('invoice_date', date('Y-m-d')) }}"
                               class="inputField w-full p-3 {{ $errors->has('invoice_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900" required>
                        @error('invoice_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Due Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input id="due_date" name="due_date" type="date"
                               value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}"
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
                                <option value="{{ $statusOption }}" {{ old('status','draft') == $statusOption ? 'selected' : '' }}>
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
                               value="{{ old('currency','EUR') }}"
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
                                        {{ old('discount_id_invoice') == $discount->id ? 'selected' : '' }}>
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

                <div id="invoiceItemsContainer" class="space-y-4"></div>

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
                                      class="inputField w-full p-3 {{ $errors->has('payment_terms') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">{{ old('payment_terms', __('Payment due within 30 days.')) }}</textarea>
                        </div>
                        <div>
                            <label for="notes_to_client" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Notes to Client') }}
                            </label>
                            <textarea id="notes_to_client" name="notes_to_client" rows="3"
                                      class="inputField w-full p-3 {{ $errors->has('notes_to_client') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} border rounded-md dark:bg-slate-900">{{ old('notes_to_client') }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-2 text-right">
                        <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}:</span><span id="invoiceSubtotal" class="font-medium text-slate-900 dark:text-white">0.00 €</span></div>
                        <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">{{ __('Discount Amount') }}:</span><span id="invoiceDiscountAmount" class="font-medium text-slate-900 dark:text-white">0.00 €</span></div>
                        <div class="flex justify-between"><span class="text-slate-600 dark:text-slate-300">{{ __('Total Tax') }} (<span id="clientVatRateDisplay">{{ config('app.vat_rate',21) }}</span>%):</span><span id="invoiceTaxes" class="font-medium text-slate-900 dark:text-white">0.00 €</span></div>
                        <hr class="my-1 border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between text-lg"><span class="font-bold text-slate-900 dark:text-white">{{ __('Total Amount') }}:</span><span id="invoiceTotal" class="font-bold text-slate-900 dark:text-white">0.00 €</span></div>

                        <input type="hidden" name="client_vat_rate" id="inputClientVatRate" value="{{ config('app.vat_rate',21) }}">
                        <input type="hidden" name="subtotal"        id="inputSubtotal"      value="0">
                        <input type="hidden" name="discount_amount" id="inputDiscountAmount" value="0">
                        <input type="hidden" name="tax_amount"      id="inputTaxAmount"      value="0">
                        <input type="hidden" name="total_amount"    id="inputTotalAmount"    value="0">
                    </div>
                </div>

                {{-- Botones --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save Invoice') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =============== TEMPLATE FILA =============== --}}
    <template id="invoiceItemTemplate">
        <div class="invoice-item-row grid grid-cols-12 gap-3 items-center border-b border-slate-200 dark:border-slate-700 pb-3">
            <input type="hidden" name="items[__INDEX__][id]" value="">
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
    @endpush

    @once
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
        $(function () {
            /* ---------- CONSTANTES ---------- */
            const allAvailableQuotes   = @json($availableQuotes   ?? []);
            const allAvailableProjects = @json($availableProjects ?? []);
            const defaultVatRate       = {{ config('app.vat_rate',21) }};
            let   currentClientVatRate = defaultVatRate;

            /* ---------- INIT SELECT2 ---------- */
            const initSelect2 = $el => $.fn.select2 && $el.select2({ width:'100%' });
            $('.select2-main').each(function(){ initSelect2($(this)); });

            /* ---------- CLIENT CHANGE ---------- */
            $('#client_id').on('change', function () {
                const clientId = $(this).val();
                const $opt     = $(this).find('option:selected');

                // IVA del cliente (acepta 0, 4, 10…)
                const rate = parseFloat($opt.data('vat-rate'));
                currentClientVatRate = Number.isNaN(rate) ? defaultVatRate : rate;
                $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));
                $('#inputClientVatRate').val(currentClientVatRate);

                // Quotes
                const $qSel = $('#quote_id').empty()
                    .append(`<option value="">${@json(__('None'))}</option>`);
                if (clientId) {
                    allAvailableQuotes.forEach(q => {
                        if (String(q.client_id) === String(clientId)) {
                            $qSel.append(
                                `<option value="${q.id}">${q.quote_number} - ${(q.client?.name ?? '')} (${parseFloat(q.total_amount || 0).toFixed(2)}€)</option>`
                            );
                        }
                    });
                }
                $qSel.trigger('change.select2');

                // Projects
                const $pSel = $('#project_id').empty()
                    .append(`<option value="">${@json(__('None'))}</option>`);
                if (clientId) {
                    allAvailableProjects.forEach(p => {
                        if (String(p.client_id) === String(clientId)) {
                            $pSel.append(
                                `<option value="${p.id}">${p.project_title} - ${(p.client?.name ?? '')}</option>`
                            );
                        }
                    });
                }
                $pSel.trigger('change.select2');

                calculateOverallTotals();
            }).trigger('change');

            /* ---------- FILAS ---------- */
            const $itemsContainer = $('#invoiceItemsContainer');
            const itemTpl         = $('#invoiceItemTemplate').html();
            let   itemIndex       = 0;

            function addInvoiceItemRow(data=null){
                const html = itemTpl.replace(/__INDEX__/g,itemIndex);
                const $row = $(html);
                $itemsContainer.append($row);

                const $serviceSel = $row.find('.item-service');
                initSelect2($serviceSel);

                if(data){
                    if(data.service_id){ $serviceSel.val(data.service_id).trigger('change'); }
                    $row.find('.item-description').val(data.item_description||'');
                    $row.find('.item-quantity').val(data.quantity||1);
                    $row.find('.item-price').val(parseFloat(data.unit_price||0).toFixed(2));
                }

                initializeRowEvents($row);
                updateLineTotal($row);
                itemIndex++;
            }

            function initializeRowEvents($row){
                $row.find('.item-service').on('change',function(){
                    const $opt  = $(this).find('option:selected');
                    const price = parseFloat($opt.data('price'))||0;
                    const desc  = $opt.data('description')||$opt.text();
                    if($opt.val()){
                        $row.find('.item-description').val(desc);
                        $row.find('.item-price').val(price.toFixed(2));
                    }else{
                        $row.find('.item-description').val('');
                        $row.find('.item-price').val('0.00');
                    }
                    updateLineTotal($row); calculateOverallTotals();
                });
                $row.find('.item-quantity, .item-price').on('input change',function(){
                    updateLineTotal($row); calculateOverallTotals();
                });
                $row.find('.remove-item-btn').on('click',function(){
                    $(this).closest('.invoice-item-row').remove();
                    calculateOverallTotals();
                    if($('#invoiceItemsContainer .invoice-item-row').length===0){ addInvoiceItemRow(); }
                });
            }

            const updateLineTotal = $row => {
                const qty = parseFloat($row.find('.item-quantity').val())||0;
                const prc = parseFloat($row.find('.item-price').val())   ||0;
                $row.find('.item-total').text((qty*prc).toFixed(2)+' €');
            };

            function calculateOverallTotals(){
                let subtotal = 0;
                $('#invoiceItemsContainer .invoice-item-row').each(function(){
                    subtotal += (parseFloat($(this).find('.item-quantity').val())||0) *
                                (parseFloat($(this).find('.item-price').val())||0);
                });

                let discount = 0;
                const $dOpt = $('#discount_id_invoice option:selected');
                if($dOpt.val()){
                    const t=$dOpt.data('type'), v=parseFloat($dOpt.data('value'))||0;
                    discount = t==='percentage' ? subtotal*(v/100) : v;
                    discount = Math.min(discount, subtotal);
                }

                const base      = subtotal-discount;
                const taxAmount = base*(currentClientVatRate/100);
                const total     = base+taxAmount;

                $('#invoiceSubtotal').text(subtotal.toFixed(2)+' €');
                $('#invoiceDiscountAmount').text(discount.toFixed(2)+' €');
                $('#invoiceTaxes').text(taxAmount.toFixed(2)+' €');
                $('#invoiceTotal').text(total.toFixed(2)+' €');

                $('#inputSubtotal').val(subtotal.toFixed(2));
                $('#inputDiscountAmount').val(discount.toFixed(2));
                $('#inputTaxAmount').val(taxAmount.toFixed(2));
                $('#inputTotalAmount').val(total.toFixed(2));
            }

            $('#discount_id_invoice').on('change', calculateOverallTotals);
            $('#addInvoiceItemBtn').on('click', () => addInvoiceItemRow());

            /* ---------- CARGAR ITEMS DESDE QUOTE ---------- */
            $('#quote_id').on('change',function(){
                const quoteId=$(this).val();
                if(!quoteId){
                    $itemsContainer.empty(); itemIndex=0; addInvoiceItemRow(); calculateOverallTotals(); return;
                }
                $.get(`/quotes/${quoteId}/details-for-invoice`,function(res){
                    if(!res.success){ alert('Error loading quote'); return; }

                    $('#client_id').val(res.client_id).trigger('change.select2');

                    if(res.quote_discount_id){
                        $('#discount_id_invoice').val(res.quote_discount_id).trigger('change.select2');
                    }else{
                        $('#discount_id_invoice').val('').trigger('change.select2');
                    }

                    currentClientVatRate=parseFloat(res.client_vat_rate)||defaultVatRate;
                    $('#clientVatRateDisplay').text(currentClientVatRate.toFixed(2));
                    $('#inputClientVatRate').val(currentClientVatRate);

                    $('#payment_terms').val(res.payment_terms||'');
                    $('#notes_to_client').val(res.notes_to_client||'');

                    $itemsContainer.empty(); itemIndex=0;
                    res.items.forEach(it=>addInvoiceItemRow(it));
                    calculateOverallTotals();
                }).fail(()=>alert('Error fetching quote details'));
            });

            /* ---------- INIT ---------- */
            addInvoiceItemRow();
            calculateOverallTotals();
        });
        </script>
    @endpush
    @endonce
</x-app-layout>
