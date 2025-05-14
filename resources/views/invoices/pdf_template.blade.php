<!DOCTYPE html>
<html lang="es">
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
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-logo {
            max-height: 80px;
            margin-bottom: 15px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin-bottom: 0;
        }
        .invoice-details, .client-details, .company-details {
            margin-bottom: 20px;
        }
        .invoice-details p, .client-details p, .company-details p {
            margin: 0;
            line-height: 1.4;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
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
            float: right; /* dompdf a veces tiene problemas con flex/grid, float es más seguro */
        }
        .totals-table td {
            padding: 5px 8px;
        }
        .totals-table tr td:first-child {
            text-align: right;
            font-weight: bold;
            color: #555;
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
        <div class="header">
            <div class="header-left">
                @if(isset($companyData['logo_path']) && $companyData['logo_path'])
                    <img src="{{ $companyData['logo_path'] }}" alt="{{ $companyData['name'] ?? 'Company Logo' }}" class="company-logo">
                @endif
                <h3 class="section-title" style="margin-bottom: 5px;">{{ __('From:') }}</h3>
                <div class="company-details">
                    <p><strong>{{ $companyData['name'] ?? '' }}</strong></p>
                    <p class="whitespace-pre-wrap">{{ $companyData['address'] ?? '' }}</p>
                    <p>{{ $companyData['city_zip_country'] ?? '' }}</p>
                    @if(isset($companyData['phone']) && $companyData['phone']) <p>{{ __('Phone:') }} {{ $companyData['phone'] }}</p> @endif
                    @if(isset($companyData['email']) && $companyData['email']) <p>{{ __('Email:') }} {{ $companyData['email'] }}</p> @endif
                    @if(isset($companyData['vat']) && $companyData['vat']) <p>{{ __('VAT ID:') }} {{ $companyData['vat'] }}</p> @endif
                </div>
            </div>
            <div class="header-right">
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
                </div>
            </div>
        </div>
        <div class="clearfix"></div>

        <div class="client-details">
            <h3 class="section-title">{{ __('Billed To:') }}</h3>
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
                <tr class="grand-total">
                    <td>{{ __('Total Amount') }}:</td>
                    <td class="text-right">{{ number_format($invoice->total_amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
            </table>
        </div>
        <div class="clearfix"></div>


        @if($invoice->payment_terms || $invoice->notes_to_client)
        <div class="footer-notes">
            @if($invoice->payment_terms)
                <h4 class="section-title" style="font-size: 12px; margin-bottom: 5px;">{{ __('Payment Terms') }}</h4>
                <p class="whitespace-pre-wrap" style="font-size: 10px;">{{ $invoice->payment_terms }}</p>
            @endif
            @if($invoice->notes_to_client)
                <h4 class="section-title" style="font-size: 12px; margin-top: 15px; margin-bottom: 5px;">{{ __('Notes to Client') }}</h4>
                <p class="whitespace-pre-wrap" style="font-size: 10px;">{{ $invoice->notes_to_client }}</p>
            @endif
        </div>
        @endif

    </div>
</body>
</html>
