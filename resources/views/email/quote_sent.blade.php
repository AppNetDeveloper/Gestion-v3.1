{{-- Usamos la sintaxis de componentes anónimos de Blade para emails Markdown --}}
<x-mail::message>
# {{ __('Quote') }} #{{ $quote->quote_number }}

{{ __('Hello') }} {{ $quote->client->name }},

{{ __('Please find attached the quote :quote_number requested from :app_name.', ['quote_number' => $quote->quote_number, 'app_name' => config('app.name')]) }}

**{{ __('Quote Summary:') }}**
* **{{ __('Quote Date') }}:** {{ $quote->quote_date->format('d/m/Y') }}
* **{{ __('Total Amount') }}:** {{ number_format($quote->total_amount, 2, ',', '.') }} €

{{-- Puedes añadir más detalles o un resumen de los items si quieres --}}

@if($quote->notes_to_client)
**{{ __('Notes') }}:**
{{ $quote->notes_to_client }}
@endif

{{-- Botón para ver el presupuesto online (opcional) --}}
<x-mail::button :url="route('quotes.show', $quote->id)">
{{ __('View Quote Online') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
