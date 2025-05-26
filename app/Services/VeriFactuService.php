<?php

namespace App\Services;

use App\Models\Invoice;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;

class VeriFactuService
{
    /**
     * Genera la huella digital para una factura según normativa española
     * 
     * @param Invoice $invoice
     * @return array
     */
    public function generateDigitalFingerprint(Invoice $invoice): array
    {
        try {
            // 1. Generar la cadena de resumen (hash) de la factura
            $invoiceData = $this->getInvoiceDataForHashing($invoice);
            $hash = hash('sha256', $invoiceData);
            
            // 2. Generar un identificador único para la factura
            $invoiceId = $this->generateInvoiceId($invoice);
            
            // 3. Crear la firma digital (simplificado para el ejemplo)
            // En producción, aquí se usaría un certificado digital real
            $signature = $this->generateSignature($hash, $invoiceId);
            
            return [
                'verifactu_id' => $invoiceId,
                'verifactu_hash' => $hash,
                'verifactu_signature' => $signature,
                'verifactu_qr_code_data' => $this->generateQrCodeData($invoice, $hash, $signature),
                'verifactu_timestamp' => now()
            ];
            
        } catch (Exception $e) {
            Log::error('Error generating digital fingerprint: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Prepara los datos de la factura para el cálculo del hash
     */
    private function getInvoiceDataForHashing(Invoice $invoice): string
    {
        // Datos básicos de la factura
        $data = [
            'number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date,
            'total' => number_format($invoice->total_amount, 2, '.', ''),
            'tax_amount' => number_format($invoice->tax_amount, 2, '.', ''),
            'subtotal' => number_format($invoice->subtotal, 2, '.', ''),
            'vat' => $invoice->client->vat_number ?? '',
            'company_vat' => config('app.company_vat', ''),
            'currency' => $invoice->currency,
            'items_count' => $invoice->items->count(),
        ];
        
        // Ordenar los datos para asegurar consistencia
        ksort($data);
        
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Genera un ID único para la factura según normativa
     */
    private function generateInvoiceId(Invoice $invoice): string
    {
        $date = now()->format('Ymd-His');
        $random = strtoupper(substr(md5(uniqid()), 0, 12));
        return "FAC-{$date}-{$random}";
    }
    
    /**
     * Genera la firma digital (simulada)
     * En producción, esto se haría con un certificado digital real
     */
    private function generateSignature(string $data, string $invoiceId): string
    {
        // En un entorno real, aquí se usaría el certificado digital para firmar
        // Este es un ejemplo simplificado que devuelve un hash como firma
        return hash('sha256', $data . $invoiceId . config('app.key'));
    }
    
    /**
     * Genera los datos para el código QR
     */
    private function generateQrCodeData(Invoice $invoice, string $hash, string $signature): string
    {
        $data = [
            'id' => $invoice->verifactu_id,
            'date' => now()->toIso8601String(),
            'total' => number_format($invoice->total_amount, 2, '.', ''),
            'vat' => $invoice->client->vat_number ?? '',
            'hash' => $hash,
            'signature' => $signature
        ];
        
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Verifica si la huella digital de una factura es válida
     * 
     * @param Invoice $invoice
     * @return array
     */
    public function verifyDigitalFingerprint(Invoice $invoice): array
    {
        if (!$invoice->verifactu_hash || !$invoice->verifactu_signature) {
            return [
                'is_valid' => false,
                'message' => 'La factura no tiene huella digital registrada.'
            ];
        }
        
        try {
            // Regenerar los datos de la factura para verificación
            $currentData = $this->getInvoiceDataForHashing($invoice);
            $currentHash = hash('sha256', $currentData);
            
            // Verificar si el hash actual coincide con el almacenado
            if ($currentHash !== $invoice->verifactu_hash) {
                return [
                    'is_valid' => false,
                    'message' => '¡ADVERTENCIA: La factura ha sido modificada después de su firma digital.',
                    'original_hash' => $invoice->verifactu_hash,
                    'current_hash' => $currentHash
                ];
            }
            
            // Verificar la firma (en un entorno real, aquí se verificaría con un certificado)
            $expectedSignature = $this->generateSignature($invoice->verifactu_hash, $invoice->verifactu_id);
            
            if ($expectedSignature !== $invoice->verifactu_signature) {
                return [
                    'is_valid' => false,
                    'message' => '¡ADVERTENCIA: La firma digital no es válida. La factura podría haber sido alterada.'
                ];
            }
            
            return [
                'is_valid' => true,
                'message' => 'La factura es auténtica y no ha sido modificada desde su firma.',
                'verification_date' => now()->toDateTimeString(),
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ];
            
        } catch (\Exception $e) {
            Log::error('Error al verificar la huella digital: ' . $e->getMessage());
            return [
                'is_valid' => false,
                'message' => 'Error al verificar la factura: ' . $e->getMessage()
            ];
        }
    }
}
