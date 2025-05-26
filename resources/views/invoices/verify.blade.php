@extends('layouts.verification')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="text-center mb-5">
                <h1 class="h3 mb-3">Verificación de Factura Electrónica</h1>
                <p class="text-muted">Verifique la autenticidad e integridad de sus facturas electrónicas</p>
            </div>
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Verificación de Factura</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('invoices.verify.submit') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="invoice_number" class="form-control form-control-lg" 
                                   placeholder="Ingrese el número de factura" required
                                   value="{{ $invoice->invoice_number ?? old('invoice_number') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Verificar
                            </button>
                        </div>
                    </form>

                    @if(isset($verificationResult))
                        <div class="alert alert-{{ $verificationResult['is_valid'] ? 'success' : 'danger' }} mt-4">
                            <h5 class="alert-heading">
                                <i class="fas {{ $verificationResult['is_valid'] ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
                                {{ $verificationResult['is_valid'] ? 'Factura Verificada' : '¡Atención!' }}
                            </h5>
                            <p>{{ $verificationResult['message'] }}</p>
                            
                            @if(isset($verificationResult['original_hash']) && isset($verificationResult['current_hash']))
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Hash Original:</h6>
                                        <code class="d-block text-truncate" title="{{ $verificationResult['original_hash'] }}">
                                            {{ $verificationResult['original_hash'] }}
                                        </code>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Hash Actual:</h6>
                                        <code class="d-block text-truncate" title="{{ $verificationResult['current_hash'] }}">
                                            {{ $verificationResult['current_hash'] }}
                                        </code>
                                    </div>
                                </div>
                            @endif
                            
                            @if(isset($verificationResult['verification_date']))
                                <hr>
                                <p class="mb-0">
                                    <strong>Verificado el:</strong> {{ $verificationResult['verification_date'] }}
                                </p>
                            @endif
                        </div>

                        @if(isset($invoice))
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Información de la Factura</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Número:</strong> {{ $invoice->invoice_number }}</p>
                                        <p><strong>Fecha:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}</p>
                                        <p><strong>Cliente:</strong> {{ $invoice->client->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Total:</strong> {{ number_format($invoice->total_amount, 2, ',', '.') }} {{ $invoice->currency }}</p>
                                        <p><strong>Estado:</strong> {{ ucfirst($invoice->status) }}</p>
                                        @if($invoice->verifactu_id)
                                            <p><strong>ID VeriFactu:</strong> {{ $invoice->verifactu_id }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($invoice->verifactu_qr_code_data)
                                <div class="text-center mt-3">
                                    <img src="{{ $invoice->verifactu_qr_code_data }}" alt="Código QR" class="img-fluid" style="max-width: 150px;">
                                    <p class="text-muted small mt-2">Escanee este código para verificar la autenticidad</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger mt-4">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
                <div class="card-footer text-muted text-center bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i> {{ config('app.name') }} - Sistema de Verificación
                        </small>
                        <small>
                            <a href="{{ url('/') }}" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i> Volver al inicio
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($invoice) && $invoice->verifactu_qr_code_data)
@push('scripts')
<script>
    // Generar código QR para compartir la verificación
    document.addEventListener('DOMContentLoaded', function() {
        const shareBtn = document.getElementById('share-verification');
        if (shareBtn && navigator.share) {
            shareBtn.style.display = 'inline-block';
            shareBtn.addEventListener('click', async () => {
                try {
                    await navigator.share({
                        title: 'Verificación de Factura {{ $invoice->invoice_number }}',
                        text: 'Verifica la autenticidad de esta factura',
                        url: '{{ route('invoices.verify', $invoice->id) }}',
                    });
                } catch (err) {
                    console.error('Error al compartir:', err);
                }
            });
        }
    });
</script>
@endpush
@endif
@endsection
