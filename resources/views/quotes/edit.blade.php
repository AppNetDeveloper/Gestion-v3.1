<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        {{-- Asegúrate de pasar $breadcrumbItems desde QuoteController@edit --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Edit Quote') . ' #' . $quote->quote_number" />
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
            <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-6">{{ __('Edit Quote Details') }}</h3>

            {{-- Formulario apunta a la ruta update con método PUT --}}
            <form action="{{ route('quotes.update', $quote->id) }}" method="POST" id="quoteForm">
                @csrf
                @method('PUT') {{-- Importante para formularios de actualización --}}

                {{-- Sección Datos Generales del Presupuesto --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    {{-- Cliente --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Client') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="client_id" name="client_id" class="inputField select2 w-full p-3 border {{ $errors->has('client_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="" disabled>{{ __('Select a client') }}</option>
                            @foreach($clients ?? [] as $client)
                                <option value="{{ $client->id }}"
                                        data-vat-rate="{{ $client->vat_rate ?? config('app.vat_rate', 21) }}"
                                        {{-- Seleccionar el cliente actual del presupuesto --}}
                                        {{ (old('client_id', $quote->client_id) == $client->id) ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Número de Presupuesto --}}
                    <div>
                        <label for="quote_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Quote Number') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="quote_number" name="quote_number" value="{{ old('quote_number', $quote->quote_number) }}"
                               class="inputField w-full p-3 border {{ $errors->has('quote_number') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                        @error('quote_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fecha Presupuesto --}}
                    <div>
                        <label for="quote_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Quote Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="quote_date" name="quote_date" value="{{ old('quote_date', $quote->quote_date->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('quote_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                        @error('quote_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Fecha Vencimiento --}}
                    <div>
                        <label for="expiry_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Expiry Date') }}
                        </label>
                        <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $quote->expiry_date?->format('Y-m-d')) }}"
                               class="inputField w-full p-3 border {{ $errors->has('expiry_date') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                        @error('expiry_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Estado --}}
                     <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Status') }} <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status" class="inputField w-full p-3 border {{ $errors->has('status') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition" required>
                            <option value="draft" {{ old('status', $quote->status) == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="sent" {{ old('status', $quote->status) == 'sent' ? 'selected' : '' }}>{{ __('Sent') }}</option>
                            <option value="accepted" {{ old('status', $quote->status) == 'accepted' ? 'selected' : '' }}>{{ __('Accepted') }}</option>
                            <option value="rejected" {{ old('status', $quote->status) == 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                            <option value="expired" {{ old('status', $quote->status) == 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                     {{-- Descuento Global (Opcional) --}}
                    <div>
                        <label for="discount_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('Global Discount') }} ({{ __('Optional') }})
                        </label>
                        <select id="discount_id" name="discount_id" class="inputField select2 w-full p-3 border {{ $errors->has('discount_id') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">
                            <option value="">{{ __('None') }}</option>
                            @foreach($discounts ?? [] as $discount)
                                <option value="{{ $discount->id }}"
                                        data-type="{{ $discount->type }}"
                                        data-value="{{ $discount->value }}"
                                        {{ old('discount_id', $quote->discount_id) == $discount->id ? 'selected' : '' }}>
                                    {{ $discount->name }} ({{ $discount->type == 'percentage' ? $discount->value.'%' : number_format($discount->value, 2).'€' }})
                                </option>
                            @endforeach
                        </select>
                         @error('discount_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Sección Líneas del Presupuesto (Items) --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200 mb-4">{{ __('Quote Items') }}</h3>

                {{-- Contenedor para las líneas de items --}}
                <div id="quoteItemsContainer" class="space-y-4">
                    {{-- Cargar items existentes --}}
                    @foreach (old('items', $quote->items->toArray()) as $index => $item)
                        <div class="quote-item-row grid grid-cols-12 gap-3 items-center border-b border-slate-200 dark:border-slate-700 pb-3">
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}"> {{-- ID para edición --}}
                            {{-- Servicio (Selector) --}}
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-xs mb-1 sr-only">{{ __('Service') }}</label>
                                <select name="items[{{ $index }}][service_id]" class="inputField item-service select2 w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900">
                                    <option value="">{{ __('Select or type description') }}</option>
                                    @foreach($services ?? [] as $service)
                                        <option value="{{ $service->id }}"
                                                data-price="{{ $service->default_price }}"
                                                data-unit="{{ $service->unit }}"
                                                data-description="{{ htmlspecialchars($service->description ?? $service->name) }}"
                                                {{ ($item['service_id'] ?? null) == $service->id ? 'selected' : '' }}>
                                            {{ $service->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                             {{-- Descripción --}}
                             <div class="col-span-12 md:col-span-4">
                                 <label class="block text-xs mb-1 sr-only">{{ __('Description') }}</label>
                                <input type="text" name="items[{{ $index }}][item_description]" class="inputField item-description w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900" value="{{ $item['item_description'] ?? '' }}" required>
                            </div>
                            {{-- Cantidad --}}
                            <div class="col-span-4 md:col-span-1">
                                 <label class="block text-xs mb-1 sr-only">{{ __('Qty') }}</label>
                                <input type="number" name="items[{{ $index }}][quantity]" class="inputField item-quantity w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-center" value="{{ $item['quantity'] ?? 1 }}" min="1" required>
                            </div>
                             {{-- Precio Unitario --}}
                             <div class="col-span-4 md:col-span-2">
                                 <label class="block text-xs mb-1 sr-only">{{ __('Unit Price') }}</label>
                                <input type="number" name="items[{{ $index }}][unit_price]" step="0.01" min="0" class="inputField item-price w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-right" value="{{ $item['unit_price'] ?? '0.00' }}" required>
                            </div>
                            {{-- Total Línea --}}
                            <div class="col-span-3 md:col-span-1 text-right">
                                 <label class="block text-xs mb-1 sr-only">{{ __('Total') }}</label>
                                <span class="item-total font-medium text-sm text-slate-700 dark:text-slate-200">
                                    {{ number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2, ',', '.') }} €
                                </span>
                            </div>
                            {{-- Botón Eliminar --}}
                            <div class="col-span-1 flex items-center justify-end">
                                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 p-1">
                                    <iconify-icon icon="heroicons:trash" class="text-lg"></iconify-icon>
                                </button>
                            </div>
                            {{-- Podrías añadir campos para descuento de línea aquí si es necesario --}}
                            <input type="hidden" name="items[{{ $index }}][sort_order]" class="item-sort-order" value="{{ $index }}">
                        </div>
                    @endforeach
                </div>

                {{-- Botón para añadir nueva línea --}}
                <div class="mt-4">
                    <button type="button" id="addQuoteItemBtn" class="inline-flex items-center px-3 py-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-md text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <iconify-icon icon="heroicons:plus-solid" class="text-lg mr-1"></iconify-icon>
                        {{ __('Add Item') }}
                    </button>
                </div>

                {{-- Sección Totales y Notas --}}
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Notas y Términos --}}
                    <div class="space-y-4">
                         <div>
                            <label for="notes_to_client" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Notes to Client') }}
                            </label>
                            <textarea id="notes_to_client" name="notes_to_client" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('notes_to_client') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">{{ old('notes_to_client', $quote->notes_to_client) }}</textarea>
                        </div>
                         <div>
                            <label for="terms_and_conditions" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Terms & Conditions') }}
                            </label>
                            <textarea id="terms_and_conditions" name="terms_and_conditions" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('terms_and_conditions') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">{{ old('terms_and_conditions', $quote->terms_and_conditions) }}</textarea>
                        </div>
                         <div>
                            <label for="internal_notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('Internal Notes') }}
                            </label>
                            <textarea id="internal_notes" name="internal_notes" rows="3"
                                      class="inputField w-full p-3 border {{ $errors->has('internal_notes') ? 'border-red-500' : 'border-slate-300 dark:border-slate-600' }} rounded-md dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-500 dark:focus:border-indigo-500 transition">{{ old('internal_notes', $quote->internal_notes) }}</textarea>
                        </div>
                    </div>

                    {{-- Resumen de Totales --}}
                    <div class="space-y-2 text-right">
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Subtotal') }}:</span>
                            <span id="quoteSubtotal" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('Discount') }}:</span>
                            <span id="quoteDiscount" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                         <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-300">{{ __('VAT') }} (<span id="vatRateDisplay">{{ $quote->client->vat_rate ?? config('app.vat_rate', 21) }}</span>%):</span>
                            <span id="quoteTaxes" class="font-medium text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                         <hr class="my-1 border-slate-200 dark:border-slate-700">
                         <div class="flex justify-between text-lg">
                            <span class="font-bold text-slate-900 dark:text-white">{{ __('Total') }}:</span>
                            <span id="quoteTotal" class="font-bold text-slate-900 dark:text-white">0.00 €</span>
                        </div>
                         {{-- Campos ocultos para enviar totales calculados --}}
                         <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                         <input type="hidden" name="discount_amount" id="inputDiscount" value="0">
                         <input type="hidden" name="tax_amount" id="inputTaxes" value="0">
                         <input type="hidden" name="total_amount" id="inputTotal" value="0">
                    </div>
                </div>

                {{-- Botones de Acción --}}
                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('quotes.show', $quote->id) }}" class="btn btn-secondary"> {{-- Enlace a 'show' en lugar de 'index' --}}
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Update Quote') }} {{-- Cambiado texto del botón --}}
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- Template para nuevas filas de items (oculto) - Igual que en create --}}
    <template id="quoteItemTemplate">
         <div class="quote-item-row grid grid-cols-12 gap-3 items-center border-b border-slate-200 dark:border-slate-700 pb-3">
            <input type="hidden" name="items[__INDEX__][id]" value=""> {{-- ID vacío para nuevas filas --}}
            <div class="col-span-12 md:col-span-3">
                <select name="items[__INDEX__][service_id]" class="inputField item-service select2 w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900">
                     <option value="">{{ __('Select or type description') }}</option>
                     @foreach($services ?? [] as $service)
                        <option value="{{ $service->id }}" data-price="{{ $service->default_price }}" data-unit="{{ $service->unit }}" data-description="{{ htmlspecialchars($service->description ?? $service->name) }}">
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
            </div>
             <div class="col-span-12 md:col-span-4">
                <input type="text" name="items[__INDEX__][item_description]" class="inputField item-description w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900" required>
            </div>
            <div class="col-span-4 md:col-span-1">
                <input type="number" name="items[__INDEX__][quantity]" class="inputField item-quantity w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-center" value="1" min="1" required>
            </div>
             <div class="col-span-4 md:col-span-2">
                <input type="number" name="items[__INDEX__][unit_price]" step="0.01" min="0" class="inputField item-price w-full p-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md dark:bg-slate-900 text-right" required>
            </div>
            <div class="col-span-3 md:col-span-1 text-right">
                <span class="item-total font-medium text-sm text-slate-700 dark:text-slate-200">0.00 €</span>
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 p-1">
                    <iconify-icon icon="heroicons:trash" class="text-lg"></iconify-icon>
                </button>
            </div>
            <input type="hidden" name="items[__INDEX__][sort_order]" class="item-sort-order" value="__INDEX__">
        </div>
    </template>


    {{-- Estilos adicionales --}}
    @push('styles')
        {{-- Cargar CSS de Select2 aquí si no está global --}}
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            /* Estilos para Select2 */
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

            /* Estilos generales */
            .inputField:focus { /* Tailwind's focus classes handle this */ }
            .swal2-popup { width: 90% !important; max-width: 700px !important; border-radius: 0.5rem !important; }
            .dark .swal2-popup { background: #1f2937 !important; color: #d1d5db !important; }
            .dark .swal2-title { color: #f3f4f6 !important; }
            .dark .swal2-html-container { color: #d1d5db !important; }
            .dark .swal2-actions button.swal2-confirm { background-color: #4f46e5 !important; }
            .dark .swal2-actions button.swal2-cancel { background-color: #4b5563 !important; }
            .custom-swal-input, .custom-swal-textarea { width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; padding: 0.75rem !important; display: block !important; border: 1px solid #d1d5db !important; border-radius: 0.375rem !important; background-color: #fff !important; color: #111827 !important; }
            .custom-swal-input:focus, .custom-swal-textarea:focus { outline: none !important; border-color: #4f46e5 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3) !important; }
            .dark .custom-swal-input, .dark .custom-swal-textarea { background-color: #374151 !important; border-color: #4b5563 !important; color: #f3f4f6 !important; }
            .dark .custom-swal-input:focus, .dark .custom-swal-textarea:focus { border-color: #6366f1 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.4) !important; }

            /* Estilos para el contenedor colapsable (ajustar IDs si es necesario) */
            #clientFormContainer { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-out, opacity 0.5s ease-out, padding-top 0.5s ease-out; opacity: 0; padding-top: 0 !important; }
            #clientFormContainer.expanded { max-height: 1500px; opacity: 1; padding-top: 1.5rem !important; }
            #clientFormToggleIcon.rotated { transform: rotate(180deg); }
        </style>
    @endpush

    @push('scripts')
        {{-- *** Cargar jQuery PRIMERO *** --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        {{-- Cargar Select2 DESPUÉS de jQuery --}}
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        {{-- Otros scripts --}}
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <script>
            // Pasar datos de descuentos a JavaScript
            const discountsData = @json($discounts ?? []);
            // Tasa de IVA por defecto de la configuración
            const defaultVatRate = {{ config('app.vat_rate', 21) }};
            // Obtener tasa de IVA inicial del cliente actual
            let currentVatRate = parseFloat($('#client_id').find('option:selected').data('vat-rate')) || defaultVatRate;

            // Esperar a que jQuery esté listo
             $(function() {
                 // Verificar si Select2 está cargado
                 const isSelect2Loaded = typeof $.fn.select2 !== 'undefined';
                 if (!isSelect2Loaded) {
                    console.warn('Select2 plugin is not loaded. Using standard dropdowns.');
                 } else {
                     console.log('Select2 plugin IS loaded.');
                     // Inicializar Select2 en los selectores que tengan la clase 'select2'
                     $('.select2').each(function() {
                         const $select = $(this);
                         const placeholderText = $select.find('option[disabled]').text() || $select.find('option[value=""]').text() || 'Select an option';
                         try {
                             $select.select2({
                                 placeholder: placeholderText,
                                 allowClear: true,
                                 width: '100%'
                             });
                             console.log('Select2 initialized for:', $select.attr('id') || $select.attr('name'));
                         } catch (error) {
                             console.error('Error initializing Select2 for:', $select.attr('id') || $select.attr('name'), error);
                         }
                     });

                     // Añadir listeners específicos de Select2
                     $('#client_id').on('select2:select', handleClientChange)
                                     .on('select2:unselect', handleClientUnselect);
                     $('#discount_id').on('change', calculateTotals);

                 } // Fin del else (Select2 está cargado)

                 // Si Select2 NO está cargado, añadir listeners 'change' normales
                 if (!isSelect2Loaded) {
                     $('#client_id').on('change', handleClientChange);
                     $('#discount_id').on('change', calculateTotals);
                 }


                const itemsContainer = $('#quoteItemsContainer');
                const addItemBtn = $('#addQuoteItemBtn');
                const itemTemplateHtml = $('#quoteItemTemplate').html();
                // Ajustar itemIndex para que empiece después de los items existentes
                let itemIndex = {{ $quote->items->count() > 0 ? $quote->items->count() : (old('items') ? count(old('items')) : 0) }};

                // Función para añadir una nueva fila de item
                function addQuoteItemRow() {
                    if (!itemTemplateHtml || !itemsContainer.length) { return; }
                    const newItemHtml = itemTemplateHtml.replace(/__INDEX__/g, itemIndex);
                    const $newRow = $(newItemHtml);
                    itemsContainer.append($newRow);

                    const $newSelect = $newRow.find('.item-service');

                    // Intentar inicializar Select2 si está cargado
                    if (isSelect2Loaded && $newSelect.length) {
                         try {
                            $newSelect.select2({ placeholder: "{{ __('Select or type description') }}", allowClear: true, width: '100%' })
                            .on('select2:select', handleServiceSelect);
                            console.log('Select2 initialized for new item row:', itemIndex);
                         } catch (error) {
                             console.error('Error initializing Select2 for new item row:', itemIndex, error);
                             // Fallback si falla la inicialización de Select2 en la nueva fila
                             $newSelect.on('change', handleServiceSelect);
                         }
                    } else {
                        // Fallback a listener 'change' normal
                        $newSelect.on('change', handleServiceSelect);
                        if(!isSelect2Loaded) console.warn('Select2 not loaded for new item row:', itemIndex, '. Using standard change listener.');
                    }

                    $newRow.find('.remove-item-btn').on('click', function() { $newRow.remove(); calculateTotals(); });
                    $newRow.find('.item-quantity, .item-price').on('input', function() { updateLineTotal($newRow); calculateTotals(); });

                    itemIndex++;
                    updateLineTotal($newRow);
                }

                // Handler para el cambio de servicio (funciona con select2:select y change)
                function handleServiceSelect(e) {
                    let selectedOption;
                    if (isSelect2Loaded && e.type === 'select2:select') {
                        selectedOption = e.params.data.element;
                    } else if (e.target && e.target.tagName === 'SELECT') {
                        selectedOption = $(this).find('option:selected')[0];
                    } else { return; }
                    if (!selectedOption || !$(selectedOption).val()) { return; }

                    const $row = $(this).closest('.quote-item-row');
                    const description = $(selectedOption).data('description') || '';
                    const price = $(selectedOption).data('price') || '0.00';
                    $row.find('.item-description').val(description);
                    $row.find('.item-price').val(price);
                    updateLineTotal($row);
                    calculateTotals();
                }

                 // Handler para el cambio de cliente (funciona con select2:select y change)
                 function handleClientChange(e) {
                    let selectedOption;
                    if (isSelect2Loaded && e.type === 'select2:select') {
                        selectedOption = e.params.data.element;
                    } else if (e.target && e.target.tagName === 'SELECT') {
                         selectedOption = $(this).find('option:selected')[0];
                    } else { return; }

                    const rateAttr = $(selectedOption).data('vat-rate');
                    currentVatRate = (rateAttr !== undefined && !isNaN(parseFloat(rateAttr))) ? parseFloat(rateAttr) : defaultVatRate;
                    $('#vatRateDisplay').text(currentVatRate.toFixed(2));
                    calculateTotals();
                 }

                 // Handler para deseleccionar cliente (solo Select2)
                 function handleClientUnselect(e) {
                    currentVatRate = defaultVatRate;
                    $('#vatRateDisplay').text(currentVatRate.toFixed(2));
                    calculateTotals();
                 }


                // Función para actualizar el total de una línea
                function updateLineTotal($row) {
                    const quantity = parseFloat($row.find('.item-quantity').val()) || 0;
                    const price = parseFloat($row.find('.item-price').val()) || 0;
                    const lineTotal = quantity * price;
                    $row.find('.item-total').text(lineTotal.toFixed(2) + ' €');
                }

                 // Función para calcular y mostrar los totales generales
                function calculateTotals() {
                    let subtotal = 0;
                    $('#quoteItemsContainer .quote-item-row').each(function() {
                        const $row = $(this);
                        const quantity = parseFloat($row.find('.item-quantity').val()) || 0;
                        const price = parseFloat($row.find('.item-price').val()) || 0;
                        subtotal += quantity * price;
                    });

                    let discountAmount = 0;
                    const $discountSelect = $('#discount_id');
                    const selectedDiscountId = $discountSelect.val();

                    if (selectedDiscountId) {
                        const selectedDiscount = discountsData.find(d => d.id == selectedDiscountId);
                        if (selectedDiscount) {
                            if (selectedDiscount.type === 'percentage') { discountAmount = subtotal * (parseFloat(selectedDiscount.value) / 100); }
                            else if (selectedDiscount.type === 'fixed_amount') { discountAmount = parseFloat(selectedDiscount.value); }
                        }
                    }
                    discountAmount = Math.min(subtotal, discountAmount);

                    const taxableBase = subtotal - discountAmount;
                    const validVatRate = (!isNaN(parseFloat(currentVatRate))) ? parseFloat(currentVatRate) : defaultVatRate;
                    const taxAmount = taxableBase * (validVatRate / 100);
                    const total = taxableBase + taxAmount;

                    $('#quoteSubtotal').text(subtotal.toFixed(2) + ' €');
                    $('#quoteDiscount').text(discountAmount.toFixed(2) + ' €');
                    $('#quoteTaxes').text(taxAmount.toFixed(2) + ' €');
                    $('#vatRateDisplay').text(validVatRate.toFixed(2));
                    $('#quoteTotal').text(total.toFixed(2) + ' €');

                    $('#inputSubtotal').val(subtotal.toFixed(2));
                    $('#inputDiscount').val(discountAmount.toFixed(2));
                    $('#inputTaxes').val(taxAmount.toFixed(2));
                    $('#inputTotal').val(total.toFixed(2));
                }


                // Listener para el botón "Add Item"
                if (addItemBtn.length) { addItemBtn.on('click', addQuoteItemRow); }

                 // Inicializar Select2 y listeners para items existentes
                 $('#quoteItemsContainer .quote-item-row').each(function(index) {
                     const $row = $(this);
                     const $select = $row.find('.item-service');

                     // Reasignar el índice correcto para el nombre del campo
                     $row.find('[name^="items["]').each(function() {
                         const currentName = $(this).attr('name');
                         const newName = currentName.replace(/items\[\d+\]/, `items[${index}]`);
                         $(this).attr('name', newName);
                     });
                      $row.find('.item-sort-order').val(index); // Corregir sort order

                     if (isSelect2Loaded && $select.length) {
                         try {
                             $select.select2({ placeholder: "{{ __('Select or type description') }}", allowClear: true, width: '100%' })
                             .on('select2:select', handleServiceSelect);
                         } catch(error) {
                             console.error('Error initializing Select2 for existing row:', index, error);
                             $select.on('change', handleServiceSelect);
                         }
                     } else {
                         $select.on('change', handleServiceSelect);
                         if(!isSelect2Loaded) console.warn('Select2 not loaded for existing row:', index, '. Using standard change listener.');
                     }

                     $row.find('.remove-item-btn').on('click', function() { $row.remove(); calculateTotals(); });
                     $row.find('.item-quantity, .item-price').on('input', function() { updateLineTotal($row); calculateTotals(); });

                     itemIndex = index + 1; // Asegurar que el próximo índice sea correcto
                 });


                // Calcular totales iniciales al cargar la página
                calculateTotals();

            }); // Fin document ready jQuery
        </script>
    @endpush
</x-app-layout>
