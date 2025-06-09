@extends('layouts.app')

@section('title', __('Sign Invoice with VeriFact'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            {{ __('Sign Invoice') }} #{{ $invoice->invoice_number }}
        </h1>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">
            {{ __('Back to Invoice') }}
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Invoice Details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Invoice Number') }}:</p>
                    <p class="font-medium">{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Date') }}:</p>
                    <p class="font-medium">{{ $invoice->invoice_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Client') }}:</p>
                    <p class="font-medium">{{ $invoice->client->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Amount') }}:</p>
                    <p class="font-medium">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('Digital Signature') }}</h2>
            
            <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="iconify text-blue-500" data-icon="heroicons:information-circle"></span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            {{ __('Signing this invoice will generate a unique digital signature using VeriFact technology. Once signed, the invoice cannot be modified.') }}
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('invoices.sign.process', $invoice) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="certificate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Select Digital Certificate') }}
                    </label>
                    <select id="certificate_id" name="certificate_id" class="form-select block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Select a certificate...') }}</option>
                        @foreach($certificates as $certificate)
                            <option value="{{ $certificate->id }}">
                                {{ $certificate->name }} 
                                @if($certificate->expires_at)
                                    ({{ __('Expires') }}: {{ $certificate->expires_at->format('d/m/Y') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('certificate_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span class="iconify text-yellow-500" data-icon="heroicons:exclamation-triangle"></span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                {{ __('Warning: This action cannot be undone. Once the invoice is signed, it will be locked and cannot be modified.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="iconify mr-1" data-icon="heroicons:document-check"></span>
                        {{ __('Sign Invoice') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
