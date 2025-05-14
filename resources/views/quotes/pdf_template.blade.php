<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Quote') }} #{{ $quote->quote_number }}</title>
    <style>
        /* Estilos básicos para el PDF - Evita CSS complejo o externo */
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Fuente compatible con UTF-8 */
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-lg { font-size: 14px; }
        .mt-8 { margin-top: 20px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-4 { margin-bottom: 12px; }
        .mb-6 { margin-bottom: 18px; }
        .w-half { width: 50%; }
        .float-left { float: left; }
        .float-right { float: right; }
        .clearfix::after { content: ""; clear: both; display: table; }
        address { font-style: normal; line-height: 1.3; }
        h1, h3, h4 { margin: 0 0 10px 0; }
        h1 { font-size: 18px; }
        h3 { font-size: 14px; }
        h4 { font-size: 11px; color: #555; }
        hr { border: 0; border-top: 1px solid #eee; margin: 15px 0; }
        .totals-table td { border: none; padding: 3px 0; }
        .whitespace-pre-wrap { white-space: pre-wrap; } /* Para respetar saltos de línea en notas/términos */
        /* Puedes añadir aquí el logo de tu empresa si quieres */
        .header-logo {
            /* text-align: right; */
            /* margin-bottom: 20px; */
        }
        .header-logo img {
            /* max-width: 150px; */
            /* max-height: 70px; */
        }
    </style>
</head>
<body>

    {{-- Cabecera (Opcional: Logo, Datos Empresa) --}}
    <div class="header-logo">
        {{-- <img src="{{ public_path('path/to/your/logo.png') }}" alt="Logo"> --}}
        {{-- Puedes añadir aquí los datos de tu empresa --}}
    </div>

    <h1>{{ __('Quote') }} #{{ $quote->quote_number }}</h1>

    <div class="clearfix mb-6">
        {{-- Información del Cliente --}}
        <div class="w-half float-left">
            <h4>{{ __('Quote To:') }}</h4>
            <address>
                <strong class="font-bold">{{ $quote->client->name }}</strong><br>
                @if($quote->client->address) {{ $quote->client->address }}<br> @endif
                @if($quote->client->city || $quote->client->postal_code) {{ $quote->client->city }} {{ $quote->client->postal_code }}<br> @endif
                @if($quote->client->country) {{ $quote->client->country }}<br> @endif
                @if($quote->client->vat_number) {{ __('VAT Number (NIF/CIF)') }}: {{ $quote->client->vat_number }}<br> @endif
                @if($quote->client->phone) {{ __('Phone') }}: {{ $quote->client->phone }}<br> @endif
                @if($quote->client->email) {{ __('Email') }}: {{ $quote->client->email }} @endif
            </address>
        </div>

        {{-- Detalles del Presupuesto --}}
        <div class="w-half float-right text-right">
             <h4>{{ __('Quote Details:') }}</h4>
             <strong>{{ __('Quote Date') }}:</strong> {{ $quote->quote_date->format('d/m/Y') }}<br>
             @if($quote->expiry_date)
                 <strong>{{ __('Expiry Date') }}:</strong> {{ $quote->expiry_date->format('d/m/Y') }}<br>
             @endif
             <strong>{{ __('Status') }}:</strong> {{ __(ucfirst($quote->status)) }}
        </div>
    </div>

    {{-- Tabla de Items --}}
    <h3 class="mb-4">{{ __('Quote Items') }}</h3>
    <table>
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th class="text-center">{{ __('Qty') }}</th>
                <th class="text-right">{{ __('Unit Price') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr>
                    <td>{{ $item->item_description }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td class="text-right">{{ number_format($item->line_total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    <div class="clearfix">
        <div class="w-half float-right">
            <table class="totals-table">
                <tbody>
                    <tr>
                        <td class="text-right">{{ __('Subtotal') }}:</td>
                        <td class="text-right font-bold">{{ number_format($quote->subtotal, 2, ',', '.') }} €</td>
                    </tr>
                    @if($quote->discount_amount > 0)
                    <tr>
                        <td class="text-right">{{ __('Discount') }}:</td>
                        <td class="text-right font-bold">-{{ number_format($quote->discount_amount, 2, ',', '.') }} €</td>
                    </tr>
                    @endif
                     <tr>
                        @php $appliedVatRate = $quote->client->vat_rate ?? config('app.vat_rate', 21); @endphp
                        <td class="text-right">{{ __('VAT') }} ({{ number_format($appliedVatRate, 2, ',', '.') }}%):</td>
                        <td class="text-right font-bold">{{ number_format($quote->tax_amount, 2, ',', '.') }} €</td>
                    </tr>
                    <tr><td colspan="2"><hr></td></tr>
                    <tr class="text-lg">
                        <td class="text-right font-bold">{{ __('Total') }}:</td>
                        <td class="text-right font-bold">{{ number_format($quote->total_amount, 2, ',', '.') }} €</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notas y Términos --}}
    <div class="mt-8">
        @if($quote->notes_to_client)
            <div class="mb-4">
                <h4 class="font-bold mb-1">{{ __('Notes') }}:</h4>
                <p class="whitespace-pre-wrap">{{ $quote->notes_to_client }}</p>
            </div>
        @endif
         @if($quote->terms_and_conditions)
            <div>
                <h4 class="font-bold mb-1">{{ __('Terms & Conditions') }}:</h4>
                <p class="whitespace-pre-wrap">{{ $quote->terms_and_conditions }}</p>
            </div>
        @endif
    </div>

</body>
</html>
