<x-app-layout>
    <div class="mb-6">
        {{-- Breadcrumb --}}
        <x-breadcrumb :breadcrumb-items="$breadcrumbItems ?? []" :page-title="__('Invoice Details') . ': ' . $invoice->invoice_number" />
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
        <div class="card-header p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex flex-col sm:flex-row justify-between items-start">
                <div>
                    <h3 class="text-xl font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('Invoice') }} #{{ $invoice->invoice_number }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        {{ __('Date Issued:') }} {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : 'N/A' }}
                        <span class="mx-2">|</span>
                        {{ __('Due Date:') }} {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A' }}
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 space-x-1 sm:space-x-2 text-right flex flex-wrap sm:flex-nowrap justify-end items-center">
                    @can('invoices export_pdf')
                        <a href="{{ route('invoices.pdf', $invoice->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary inline-flex items-center">
                            <iconify-icon icon="heroicons:arrow-down-tray" class="mr-1"></iconify-icon> <span>{{ __('PDF') }}</span>
                        </a>
                        
                        @if($invoice->verifactu_hash)
                        <a href="{{ route('invoices.verify', $invoice->id) }}" target="_blank" class="btn btn-sm btn-outline-success inline-flex items-center">
                            <iconify-icon icon="heroicons:shield-check" class="mr-1"></iconify-icon> <span>{{ __('Verify') }}</span>
                        </a>
                        @endif
                    @endcan
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary inline-flex items-center">
                        <iconify-icon icon="heroicons:printer" class="mr-1"></iconify-icon> <span>{{ __('Print') }}</span>
                    </button>

                    {{-- Botón Enviar Email --}}
                    @can('invoices send_email')
                        @if(!in_array($invoice->status, ['paid', 'cancelled'])) {{-- No enviar si ya está pagada o cancelada --}}
                            <form action="{{ route('invoices.sendEmail', $invoice->id) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ __('Are you sure you want to send this invoice to the client?') }}');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary inline-flex items-center">
                                    <iconify-icon icon="heroicons:envelope" class="mr-1"></iconify-icon> <span>{{ __('Send Email') }}</span>
                                </button>
                            </form>
                        @endif
                    @endcan

                    @can('invoices update')
                        @if(!in_array($invoice->status, ['paid', 'cancelled']))
                            <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-sm btn-outline-primary inline-flex items-center">
                                <iconify-icon icon="heroicons:pencil-square" class="mr-1"></iconify-icon> <span>{{ __('Edit') }}</span>
                            </a>
                        @endif
                    @endcan
                    
                    {{-- Botón de firma digital VeriFact --}}
                    @can('sign', $invoice)
                        @if(empty($invoice->verifactu_hash))
                            <a href="{{ route('invoices.sign', $invoice) }}" class="btn btn-sm btn-outline-success inline-flex items-center">
                                <iconify-icon icon="heroicons:shield-check" class="mr-1"></iconify-icon> <span>{{ __('Sign with VeriFact') }}</span>
                            </a>
                        @else
                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100">
                                <iconify-icon icon="heroicons:shield-check" class="mr-1"></iconify-icon> {{ __('Digitally Signed') }}
                            </span>
                        @endif
                    @endcan
                    
                    {{-- Botón de verificación para facturas firmadas --}}
                    @can('invoices verify')
                        @if(!empty($invoice->verifactu_hash))
                            <a href="{{ route('invoices.verify.hash', $invoice->verifactu_hash) }}" class="btn btn-sm btn-outline-info inline-flex items-center ml-2">
                                <iconify-icon icon="heroicons:document-magnifying-glass" class="mr-1"></iconify-icon> <span>{{ __('Verify Signature') }}</span>
                            </a>
                        @endif
                    @endcan
                    {{-- Otros botones como "Marcar como Pagada" se añadirían aquí --}}
                </div>
            </div>
        </div>

        <div class="card-body p-6">
            {{-- Información del Cliente y Empresa --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Billed To:') }}</h4>
                    @if($invoice->client)
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $invoice->client->name }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $invoice->client->address }}<br>
                            {{ $invoice->client->city }}, {{ $invoice->client->postal_code }}<br>
                            {{ $invoice->client->country }}<br>
                            @if($invoice->client->vat_number) VAT: {{ $invoice->client->vat_number }}<br> @endif
                            {{ $invoice->client->email }} <br>
                            {{ $invoice->client->phone }}
                        </p>
                    @else
                        <p>{{ __('N/A') }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('From:') }}</h4>
                    <p class="font-semibold text-slate-700 dark:text-slate-200">{{ config('app.company_name', 'Your Company Name') }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ config('app.company_address', '123 Main St, Anytown, USA') }}<br>
                        {{ config('app.company_phone', '+1 234 567 890') }}<br>
                        {{ config('app.company_email', 'contact@yourcompany.com') }}<br>
                        @if(config('app.company_vat')) VAT: {{ config('app.company_vat') }} @endif
                    </p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                @switch($invoice->status)
                                    @case('draft') bg-orange-100 text-orange-700 dark:bg-orange-700 dark:text-orange-100 @break
                                    @case('sent') bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-blue-100 @break
                                    @case('paid') bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100 @break
                                    @case('partially_paid') bg-yellow-100 text-yellow-700 dark:bg-yellow-700 dark:text-yellow-100 @break
                                    @case('overdue') bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100 @break
                                    @case('cancelled') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-100 @break
                                    @default bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-100
                                @endswitch">
                                {{ __(ucfirst(str_replace('_', ' ', $invoice->status))) }}
                            </span>
                        </div>
                        
                        @if($invoice->verifactu_qr_code_data)
                            <h5 class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">{{ __('VeriFactu QR') }}</h5>
                            <div class="mt-1 flex justify-center">
                                @php
                                    $qrCode = trim($invoice->verifactu_qr_code_data);
                                    $isSvg = str_starts_with($qrCode, '<?xml') || str_contains($qrCode, '<svg');
                                    $isBase64 = str_starts_with($qrCode, 'data:image/');
                                    $isUrl = filter_var($qrCode, FILTER_VALIDATE_URL) !== false;
                                @endphp
                                
                                @if($isSvg)
                                    <div class="w-32 h-32 flex items-center justify-center">
                                        {!! $qrCode !!}
                                    </div>
                                @elseif($isBase64 || $isUrl)
                                    <img src="{{ $qrCode }}" alt="VeriFactu QR Code" class="w-32 h-32 object-contain" />
                                @else
                                    <div class="text-yellow-500 text-sm p-2 bg-yellow-50 rounded">
                                        <p class="font-medium">{{ __('QR no disponible') }}</p>
                                        <p class="text-xs">{{ __('El código QR no está en un formato compatible.') }}</p>
                                        <a href="{{ route('invoices.generate-qr', $invoice->id) }}" class="text-blue-600 hover:underline text-xs">
                                            {{ __('Regenerar código QR') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-blue-500 text-sm p-2 bg-blue-50 rounded">
                                <p>{{ __('No hay código QR generado.') }}</p>
                                <a href="{{ route('invoices.generate-qr', $invoice->id) }}" class="text-blue-600 hover:underline text-xs">
                                    {{ __('Generar código QR ahora') }}
                                </a>
                            </div>
                        @endif
                        @if($invoice->verifactu_id)
                            <p class="text-xs text-center mt-2 text-slate-500 dark:text-slate-400">
                                {{ $invoice->verifactu_id }}
                            </p>
                        @endif
                    </div>
                    </div>
                </div>
            </div>

            {{-- Líneas de la Factura --}}
            <div class="mt-6 overflow-x-auto">
                <table class="w-full table-auto">
                    <colgroup>
                        <col style="width: 50%;">
                        <col style="width: 10%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                    </colgroup>
                    <thead class="bg-slate-100 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Item Description') }}</th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Qty') }}</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Unit Price') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">{{ __('Subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse ($invoice->items as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3">
                                    <div class="text-sm text-slate-800 dark:text-slate-200 break-words">{{ $item->item_description }}</div>
                                    @if($item->service) 
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $item->service->name }}</div>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-center text-sm text-slate-600 dark:text-slate-300">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-slate-600 dark:text-slate-300">
                                    {{ number_format($item->unit_price, 2, ',', '.') }} {{ $invoice->currency }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-slate-800 dark:text-slate-200">
                                    {{ number_format($item->item_subtotal, 2, ',', '.') }} {{ $invoice->currency }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No items for this invoice.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totales de la Factura --}}
            <div class="flex flex-col md:flex-row justify-between mt-8 gap-8">
                <div class="md:w-2/3 pl-0 md:pl-4">
                    @if($invoice->payment_terms)
                        <h5 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-1">{{ __('Payment Terms') }}</h5>
                        <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-wrap">{{ $invoice->payment_terms }}</p>
                    @endif
                    @if($invoice->notes_to_client)
                        <h5 class="text-slate-600 dark:text-slate-300 font-medium text-sm mt-4 mb-1">{{ __('Notes to Client') }}</h5>
                        <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-wrap">{{ $invoice->notes_to_client }}</p>
                    @endif
                </div>
                <div class="md:w-1/3">
                    <div class="bg-slate-50 dark:bg-slate-700/30 p-4 rounded-lg">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}:</span>
                                <span class="text-slate-700 dark:text-slate-200">{{ number_format($invoice->subtotal, 2, ',', '.') }} {{ $invoice->currency }}</span>
                            </div>
                            @if($invoice->discount_amount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('Discount') }}:</span>
                                <span class="text-slate-700 dark:text-slate-200">-{{ number_format($invoice->discount_amount, 2, ',', '.') }} {{ $invoice->currency }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('Total Tax') }} ({{ $invoice->client->vat_rate ?? config('app.vat_rate', 21) }}%):</span>
                                <span class="text-slate-700 dark:text-slate-200">{{ number_format($invoice->tax_amount, 2, ',', '.') }} {{ $invoice->currency }}</span>
                            </div>
                            @if($invoice->irpf_amount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('IRPF') }} ({{ number_format($invoice->irpf, 2, ',', '.') }}%):</span>
                                <span class="text-slate-700 dark:text-slate-200">-{{ number_format($invoice->irpf_amount, 2, ',', '.') }} {{ $invoice->currency }}</span>
                            </div>
                            @endif
                            <hr class="my-2 border-slate-200 dark:border-slate-600">
                            <div class="flex justify-between font-bold text-base">
                                <span class="text-slate-900 dark:text-white">{{ __('Total Amount') }}:</span>
                                <span class="text-slate-900 dark:text-white">{{ number_format($invoice->total_amount, 2, ',', '.') }} {{ $invoice->currency }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            @if($invoice->internal_notes && Auth::user()->can('invoices show'))
                <hr class="my-6 border-slate-200 dark:border-slate-700">
                <div>
                    <h4 class="text-slate-600 dark:text-slate-300 font-medium text-sm mb-2">{{ __('Internal Notes') }}</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-wrap bg-slate-50 dark:bg-slate-700 p-3 rounded-md">{{ $invoice->internal_notes }}</p>
                </div>
            @endif

        </div>
    </div>

    @push('scripts')
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
        {{-- Scripts para acciones AJAX (Marcar Pagada) se añadirían aquí si es necesario --}}
    @endpush
</x-app-layout>
