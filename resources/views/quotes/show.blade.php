<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Quote Details') . ' #' . $quote->quote_number" />
    </div>

    {{-- Alert start --}}
    @if (session('success'))
        <x-alert :message="session('success')" :type="'success'" />
    @endif
    @if (session('error'))
        <x-alert :message="session('error')" :type="'danger'" />
    @endif
    {{-- Alert end --}}

    <div class="card bg-white dark:bg-slate-800 shadow-xl rounded-lg">
        <div class="card-body p-6">

            {{-- Botones de Acción --}}
            <div class="flex flex-wrap justify-end space-x-3 mb-6"> {{-- flex-wrap para mejor responsive --}}
                <a href="{{ route('quotes.edit', $quote->id) }}" class="btn btn-outline-secondary btn-sm inline-flex items-center">
                    <iconify-icon icon="heroicons:pencil-square" class="text-lg mr-1"></iconify-icon>
                    {{ __('Edit') }}
                </a>
                {{-- Botón Imprimir --}}
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm inline-flex items-center">
                    <iconify-icon icon="heroicons:printer" class="text-lg mr-1"></iconify-icon>
                    {{ __('Print') }}
                </button>
                 {{-- Botón Exportar PDF --}}
                <a href="{{ route('quotes.pdf', $quote->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm inline-flex items-center">
                    <iconify-icon icon="heroicons:arrow-down-tray" class="text-lg mr-1"></iconify-icon>
                    {{ __('Export PDF') }}
                </a>
                {{-- Aquí podrías añadir más botones --}}
            </div>

            {{-- Detalles del Presupuesto --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Información del Cliente --}}
                <div>
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-1">{{ __('Quote To:') }}</h4>
                    <address class="not-italic text-sm text-slate-500 dark:text-slate-400">
                        <strong class="text-slate-700 dark:text-slate-200">{{ $quote->client->name }}</strong><br>
                        @if($quote->client->address)
                            {{ $quote->client->address }}<br>
                        @endif
                        @if($quote->client->city || $quote->client->postal_code)
                            {{ $quote->client->city }} {{ $quote->client->postal_code }}<br>
                        @endif
                         @if($quote->client->country)
                            {{ $quote->client->country }}<br>
                        @endif
                        @if($quote->client->vat_number)
                            {{ __('VAT Number (NIF/CIF)') }}: {{ $quote->client->vat_number }}<br>
                        @endif
                        @if($quote->client->phone)
                            {{ __('Phone') }}: {{ $quote->client->phone }}<br>
                        @endif
                         @if($quote->client->email)
                            {{ __('Email') }}: {{ $quote->client->email }}
                        @endif
                    </address>
                </div>

                {{-- Detalles del Presupuesto (Número, Fechas, Estado) --}}
                <div class="text-right">
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-1">{{ __('Quote Details:') }}</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <strong>{{ __('Quote Number') }}:</strong> {{ $quote->quote_number }}<br>
                        <strong>{{ __('Quote Date') }}:</strong> {{ $quote->quote_date->format('d/m/Y') }}<br>
                        @if($quote->expiry_date)
                            <strong>{{ __('Expiry Date') }}:</strong> {{ $quote->expiry_date->format('d/m/Y') }}<br>
                        @endif
                        <strong>{{ __('Status') }}:</strong>
                        @php
                            $status = ucfirst($quote->status);
                            $color = 'text-slate-500 dark:text-slate-400'; // default
                            switch ($quote->status) {
                                case 'sent': $color = 'text-blue-500'; break;
                                case 'accepted': $color = 'text-green-500'; break;
                                case 'rejected':
                                case 'expired': $color = 'text-red-500'; break;
                                case 'draft': $color = 'text-yellow-500'; break;
                            }
                        @endphp
                        <span class="{{ $color }} font-medium">{{ __($status) }}</span>
                    </p>
                </div>
            </div>

            {{-- Tabla de Items --}}
            <div class="mt-8 overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead class="bg-slate-100 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Item') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Qty') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Unit Price') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider border-b-2 border-slate-200 dark:border-slate-600">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($quote->items as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                    {{ $item->item_description }}
                                    {{-- Podrías mostrar el nombre del servicio si existe: $item->service?->name --}}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 text-right">{{ number_format($item->line_total, 2, ',', '.') }} €</td> {{-- Asumiendo que line_total ya tiene el descuento aplicado --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totales y Notas --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                 {{-- Notas y Términos --}}
                 <div class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    @if($quote->notes_to_client)
                        <div>
                            <h5 class="font-medium mb-1">{{ __('Notes') }}:</h5>
                            <p class="whitespace-pre-wrap">{{ $quote->notes_to_client }}</p>
                        </div>
                    @endif
                     @if($quote->terms_and_conditions)
                        <div>
                            <h5 class="font-medium mb-1">{{ __('Terms & Conditions') }}:</h5>
                            <p class="whitespace-pre-wrap">{{ $quote->terms_and_conditions }}</p>
                        </div>
                    @endif
                 </div>

                 {{-- Resumen Financiero --}}
                 <div class="text-right space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}:</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ number_format($quote->subtotal, 2, ',', '.') }} €</span>
                    </div>
                    @if($quote->discount_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Discount') }}:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-medium">-{{ number_format($quote->discount_amount, 2, ',', '.') }} €</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        @php
                            // Determinar la tasa de IVA usada (del cliente o por defecto)
                            $appliedVatRate = $quote->client->vat_rate ?? config('app.vat_rate', 21);
                        @endphp
                        <span class="text-slate-500 dark:text-slate-400">{{ __('VAT') }} ({{ number_format($appliedVatRate, 2, ',', '.') }}%):</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ number_format($quote->tax_amount, 2, ',', '.') }} €</span>
                    </div>
                    <hr class="my-1 border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between text-lg">
                        <span class="font-bold text-slate-900 dark:text-white">{{ __('Total') }}:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ number_format($quote->total_amount, 2, ',', '.') }} €</span>
                    </div>
                 </div>
            </div>

        </div>
    </div>

    {{-- Estilos y Scripts (si son necesarios específicamente para esta vista) --}}
    @push('styles')
        {{-- Añadir estilos si es necesario --}}
    @endpush

    @push('scripts')
        {{-- Añadir scripts si es necesario --}}
         <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
         {{-- El script para window.print() ya está inline en el botón --}}
    @endpush
</x-app-layout>
