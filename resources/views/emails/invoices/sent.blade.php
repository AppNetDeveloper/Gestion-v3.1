<x-mail::message>
# {{ __('Invoice Attached') }} - #{{ $invoice->invoice_number }}

{{ __('Hello') }} {{ $invoice->client->name ?? __('Client') }},

{{ __('Please find attached your invoice :invoiceNumber from :companyName.', ['invoiceNumber' => $invoice->invoice_number, 'companyName' => $companyName]) }}

{{ __('Invoice Details:') }}
- **{{ __('Invoice Number:') }}** {{ $invoice->invoice_number }}
- **{{ __('Invoice Date:') }}** {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : 'N/A' }}
- **{{ __('Due Date:') }}** {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A' }}
- **{{ __('Total Amount:') }}** {{ number_format($invoice->total_amount, 2, ',', '.') }} {{ $invoice->currency }}

@if(isset($invoiceUrl))
<x-mail::button :url="$invoiceUrl">
{{ __('View Invoice Online') }}
</x-mail::button>
@endif

{{ __('Thank you for your business!') }}<br>
{{ __('Regards') }},<br>
{{ $companyName }}
</x-mail::message>
