<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Invoice') }} #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        /* NUEVA SECCIÓN SUPERIOR: QR a la izquierda, Info Principal Factura a la derecha */
        .invoice-top-section {
            display: table;
            width: 100%;
            margin-bottom: 20px; /* Espacio antes de la sección De/Facturado A */
        }
        .invoice-qr-side {
            display: table-cell;
            width: 130px; /* Ancho para el QR y un poco de margen */
            vertical-align: top;
        }
        .invoice-main-info-side {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }

        .qr-code {
            width: 110px; /* Tamaño fijo para el contenedor */
            height: 110px; /* Mismo valor que el ancho para hacerlo cuadrado */
            border: 1px solid #e2e8f0;
            background: white;
            padding: 5px;
            text-align: center;
            margin-bottom: 10px;
            overflow: hidden; /* Para asegurar que nada se salga */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-code svg {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
        }
        .qr-code img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .qr-code .qr-id {
            font-size: 8px;
            word-break: break-all;
            margin-top: 3px;
            line-height: 1;
            color: #666;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin-top: 0; /* Ajustar si el QR es más alto */
            margin-bottom: 5px;
        }
        .invoice-details p {
            margin: 0 0 3px 0;
            line-height: 1.4;
            font-size: 11px;
        }

        /* NUEVA SECCIÓN DE PARTES: De (Emisor) a la izquierda, Facturado A (Receptor) a la derecha */
        .party-details-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .party-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .party-column.from-details {
            padding-right: 15px; /* Espacio entre columnas */
        }
        .party-column.to-details {
            padding-left: 15px; /* Espacio entre columnas */
        }
        .company-logo {
            max-height: 60px; /* Ajusta según necesidad */
            margin-bottom: 10px;
        }
        .company-details p, .client-address-details p { /* Unifica el estilo de los párrafos de dirección */
            margin: 0 0 3px 0;
            line-height: 1.4;
            font-size: 11px; /* Un poco más pequeño para que quepa bien */
        }
        .section-title { /* Título para "De:" y "Facturado A:" */
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-top: 0; /* Asegurar que no haya margen superior extra */
        }

        /* Estilos de la tabla de ítems y totales (sin cambios significativos) */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table.items-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        table.items-table td.text-right, table.items-table th.text-right {
            text-align: right;
        }
        table.items-table td.text-center, table.items-table th.text-center {
            text-align: center;
        }
        .totals-section {
            width: 100%;
            margin-top: 30px;
        }
        .totals-table {
            width: 40%;
            float: right;
        }
        .totals-table td {
            padding: 5px 8px;
        }
        .totals-table tr td:first-child {
            text-align: left; /* Etiquetas de totales a la izquierda */
            font-weight: bold;
            color: #555;
            padding-right: 10px;
        }
        .totals-table tr td:last-child {
            text-align: right; /* Valores de totales a la derecha */
        }
        .totals-table tr.grand-total td {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .footer-notes {
            margin-top: 40px;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .whitespace-pre-wrap {
            white-space: pre-wrap;
        }
        .page-break {
            page-break-after: always;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="invoice-top-section">
            <div class="invoice-qr-side">
                @if($invoice->verifactu_qr_code_data)
                <div class="qr-code">
                    @php
                        $qrCode = trim($invoice->verifactu_qr_code_data);
                        $isSvg = str_starts_with($qrCode, '<?xml') || str_contains($qrCode, '<svg');
                        $isBase64 = str_starts_with($qrCode, 'data:image/');
                        
                        // Si es SVG, convertirlo a base64
                        if ($isSvg) {
                            $base64 = 'data:image/svg+xml;base64,' . base64_encode($qrCode);
                        } elseif ($isBase64) {
                            $base64 = $qrCode;
                        }
                    @endphp
                    
                    @if(isset($base64))
                        <img src="{{ $base64 }}" alt="Código QR Factura" style="max-width: 100%; height: auto;">
                    @endif
                    
                    @if($invoice->verifactu_id)
                    <div class="qr-id">
                        {{ substr($invoice->verifactu_id, 0, 12) }}...
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="invoice-main-info-side">
                <h1 class="invoice-title">{{ __('INVOICE') }}</h1>
                <div class="invoice-details">
                    <p><strong>{{ __('Invoice #') }}:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>{{ __('Date Issued:') }}</strong> {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : 'N/A' }}</p>
                    <p><strong>{{ __('Due Date:') }}</strong> {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A' }}</p>
                    @if($invoice->quote)
                        <p><strong>{{ __('Quote #') }}:</strong> {{ $invoice->quote->quote_number }}</p>
                    @endif
                    @if($invoice->project)
                        <p><strong>{{ __('Project:') }}</strong> {{ $invoice->project->project_title }}</p>
                    @endif
                    <p><strong>{{ __('Status:') }}</strong> <span style="text-transform: capitalize;">{{ __(str_replace('_', ' ', $invoice->status)) }}</span></p>
                    @if($invoice->verifactu_id)
                    <p style="margin-top: 5px; font-size: 9px; color: #666;">
                        <strong>{{ __('VeriFactu ID:') }}</strong> {{ $invoice->verifactu_id }}
                    </p>
                    @endif
                </div>
            </div>
        </div>
        <div class="clearfix"></div>

        <div class="party-details-section">
            <div class="party-column from-details">
                @if(isset($companyData['logo_path']) && $companyData['logo_path'])
                    <img src="{{ $companyData['logo_path'] }}" alt="{{ $companyData['name'] ?? 'Company Logo' }}" class="company-logo">
                @endif
                <h3 class="section-title">{{ __('From:') }}</h3>
                <div class="company-details">
                    <p><strong>{{ $companyData['name'] ?? '' }}</strong></p>
                    <p class="whitespace-pre-wrap">{{ $companyData['address'] ?? '' }}</p>
                    <p>{{ $companyData['city_zip_country'] ?? '' }}</p>
                    @if(isset($companyData['phone']) && $companyData['phone']) <p>{{ __('Phone:') }} {{ $companyData['phone'] }}</p> @endif
                    @if(isset($companyData['email']) && $companyData['email']) <p>{{ __('Email:') }} {{ $companyData['email'] }}</p> @endif
                    @if(isset($companyData['vat']) && $companyData['vat']) <p>{{ __('VAT ID:') }} {{ $companyData['vat'] }}</p> @endif
                </div>
            </div>
            <div class="party-column to-details">
                <h3 class="section-title">{{ __('Billed To:') }}</h3>
                <div class="client-address-details"> {{-- Renombrado para evitar posible conflicto con un .client-details global si existiera --}}
                    @if($invoice->client)
                        <p><strong>{{ $invoice->client->name }}</strong></p>
                        <p class="whitespace-pre-wrap">{{ $invoice->client->address }}</p>
                        <p>{{ $invoice->client->city }}{{ $invoice->client->postal_code ? ', ' . $invoice->client->postal_code : '' }}</p>
                        <p>{{ $invoice->client->country }}</p>
                        @if($invoice->client->vat_number) <p>{{ __('VAT ID:') }} {{ $invoice->client->vat_number }}</p> @endif
                        @if($invoice->client->email) <p>{{ __('Email:') }} {{ $invoice->client->email }}</p> @endif
                        @if($invoice->client->phone) <p>{{ __('Phone:') }} {{ $invoice->client->phone }}</p> @endif
                    @else
                        <p>{{ __('N/A') }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="clearfix"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>{{ __('Item Description') }}</th>
                    <th class="text-center">{{ __('Qty') }}</th>
                    <th class="text-right">{{ __('Unit Price') }}</th>
                    <th class="text-right">{{ __('Subtotal') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->items as $item)
                    <tr>
                        <td class="whitespace-pre-wrap">
                            {{ $item->item_description }}
                            @if($item->service) <br><small>({{ $item->service->name }})</small> @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} {{ $invoice->currency }}</td>
                        <td class="text-right">{{ number_format($item->item_subtotal, 2, ',', '.') }} {{ $invoice->currency }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">{{ __('No items for this invoice.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="clearfix"></div>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>{{ __('Subtotal') }}:</td>
                    <td class="text-right">{{ number_format($invoice->subtotal, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td>{{ __('Discount') }}:</td>
                    <td class="text-right">-{{ number_format($invoice->discount_amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
                @endif
                <tr>
                    <td>{{ __('Total Tax') }} ({{ $invoice->client->vat_rate ?? config('app.vat_rate', 21) }}%):</td>
                    <td class="text-right">{{ number_format($invoice->tax_amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
                @if($invoice->irpf > 0)
                <tr>
                    <td>{{ __('IRPF') }} ({{ number_format($invoice->irpf, 2, ',', '.') }}%):</td>
                    <td class="text-right" style="color: #dc2626;">-{{ number_format($invoice->irpf_amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td>{{ __('Total Amount') }}:</td>
                    <td class="text-right">{{ number_format($invoice->total_amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>

        <div class="footer-notes">
            @if($invoice->payment_terms || $invoice->notes_to_client)
                @if($invoice->payment_terms)
                    <h4 class="section-title" style="font-size: 12px; margin-bottom: 5px;">{{ __('Payment Terms') }}</h4>
                    <p class="whitespace-pre-wrap" style="font-size: 10px;">{{ $invoice->payment_terms }}</p>
                @endif
                @if($invoice->notes_to_client)
                    <h4 class="section-title" style="font-size: 12px; margin-top: 15px; margin-bottom: 5px;">{{ __('Notes to Client') }}</h4>
                    <p class="whitespace-pre-wrap" style="font-size: 10px;">{{ $invoice->notes_to_client }}</p>
                @endif
            @endif
            
            <!-- Bank Account Information -->
            <div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;">
                <h4 class="section-title" style="font-size: 12px; margin-bottom: 5px; color: #2d3748;">{{ __('Bank Transfer Details') }}</h4>
                <table style="width: 100%; font-size: 10px; line-height: 1.4;">
                    <tr>
                        <td style="width: 35%; font-weight: bold;">{{ __('Bank') }}:</td>
                        <td>{{ $bankInfo['name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; font-weight: bold;">{{ __('Account Holder') }}:</td>
                        <td>{{ $bankInfo['account_holder'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; font-weight: bold;">{{ __('Account Number') }} ({{ __('IBAN') }}):</td>
                        <td>{{ $bankInfo['account_number'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; font-weight: bold;">{{ __('SWIFT/BIC') }}:</td>
                        <td>{{ $bankInfo['swift_bic'] ?? '' }}</td>
                    </tr>
                </table>
                @if($invoice->invoice_number)
                <p style="margin-top: 8px; font-size: 9px; color: #666;">
                    {{ __('Please use the invoice number as reference:') }} <strong>{{ $invoice->invoice_number }}</strong>
                </p>
                @endif
            </div>
        </div>

    </div>
</body>
</html>
