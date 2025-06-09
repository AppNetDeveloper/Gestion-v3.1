<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\DigitalCertificate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class VeriFactuService
{
    /**
     * Firma digitalmente una factura utilizando un certificado digital
     *
     * @param Invoice $invoice La factura a firmar
     * @param DigitalCertificate $certificate El certificado digital a utilizar
     * @return bool True si la firma fue exitosa, False en caso contrario
     */
    public function signInvoice(Invoice $invoice, DigitalCertificate $certificate)
    {
        try {
            // Verificar que el certificado sea válido
            if (!$certificate->is_active || $certificate->isExpired()) {
                throw new Exception('El certificado digital no está activo o ha expirado.');
            }

            // Generar el hash de la factura
            $hash = $this->generateInvoiceHash($invoice);
            
            // Generar la firma digital usando el certificado
            $signature = $this->generateDigitalSignature($hash, $certificate);
            
            // Actualizar la factura con los datos de VeriFact
            $invoice->update([
                'verifactu_hash' => $hash,
                'verifactu_signature' => $signature,
                'verifactu_timestamp' => now(),
                'is_locked' => true, // Bloquear la factura una vez firmada
            ]);
            
            // Generar código QR si es necesario
            $qrCodeData = $this->generateQRCodeData($invoice);
            $invoice->update(['verifactu_qr_code_data' => $qrCodeData]);
            
            Log::info("Factura #{$invoice->invoice_number} firmada digitalmente con éxito.");
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al firmar digitalmente la factura #{$invoice->invoice_number}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Genera un hash único para la factura basado en sus datos
     *
     * @param Invoice $invoice
     * @return string
     */
    protected function generateInvoiceHash(Invoice $invoice)
    {
        // Recopilar datos relevantes de la factura para el hash
        $data = [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
            'client_id' => $invoice->client_id,
            'total_amount' => number_format($invoice->total_amount, 2, '.', ''),
            'items' => $invoice->items->map(function($item) {
                return [
                    'description' => $item->item_description,
                    'quantity' => $item->quantity,
                    'unit_price' => number_format($item->unit_price, 2, '.', ''),
                    'amount' => number_format($item->line_total, 2, '.', ''),
                ];
            })->toArray(),
            // Eliminamos el timestamp dinámico para que el hash sea consistente
            // 'timestamp' => now()->timestamp,
        ];
        
        // Generar un hash SHA-256 de los datos
        return hash('sha256', json_encode($data));
    }
    
    /**
     * Genera una firma digital utilizando el certificado
     *
     * @param string $hash El hash a firmar
     * @param DigitalCertificate $certificate El certificado digital
     * @return string La firma digital en formato base64
     */
    protected function generateDigitalSignature($hash, DigitalCertificate $certificate)
    {
        try {
            // Ruta al archivo del certificado
            $certPath = $certificate->full_path;
            
            // Contraseña del certificado
            $password = $certificate->password;
            
            // Crear archivos temporales para el proceso de firma
            $hashFile = tempnam(sys_get_temp_dir(), 'hash_');
            $signatureFile = tempnam(sys_get_temp_dir(), 'sig_');
            $certExtractedFile = tempnam(sys_get_temp_dir(), 'cert_');
            $keyExtractedFile = tempnam(sys_get_temp_dir(), 'key_');
            
            // Guardar el hash en un archivo temporal
            file_put_contents($hashFile, $hash);
            
            // Extraer el certificado y la clave privada del archivo PKCS#12
            $extractCertCmd = "openssl pkcs12 -in {$certPath} -passin pass:{$password} -clcerts -nokeys -out {$certExtractedFile}";
            $extractKeyCmd = "openssl pkcs12 -in {$certPath} -passin pass:{$password} -nocerts -out {$keyExtractedFile} -passout pass:{$password}";
            
            // Ejecutar comandos de extracción
            exec($extractCertCmd, $certOutput, $certReturnCode);
            exec($extractKeyCmd, $keyOutput, $keyReturnCode);
            
            if ($certReturnCode !== 0 || $keyReturnCode !== 0) {
                throw new Exception('Error al extraer el certificado o la clave privada.');
            }
            
            // Firmar el hash con la clave privada extraída
            $signCmd = "openssl dgst -sha256 -sign {$keyExtractedFile} -passin pass:{$password} -out {$signatureFile} {$hashFile}";
            exec($signCmd, $signOutput, $signReturnCode);
            
            if ($signReturnCode !== 0) {
                throw new Exception('Error al firmar el hash con la clave privada.');
            }
            
            // Leer la firma generada
            $signature = file_get_contents($signatureFile);
            
            // Limpiar archivos temporales
            unlink($hashFile);
            unlink($signatureFile);
            unlink($certExtractedFile);
            unlink($keyExtractedFile);
            
            // Convertir la firma a base64
            $signature = base64_encode($signature);
            
            return $signature;
        } catch (Exception $e) {
            Log::error('Error en la generación de firma digital: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Genera los datos para el código QR de verificación
     *
     * @param Invoice $invoice
     * @return string
     */
    protected function generateQRCodeData(Invoice $invoice)
    {
        // Crear una URL o datos para el código QR que permita verificar la factura
        $data = [
            'invoice_number' => $invoice->invoice_number,
            'hash' => $invoice->verifactu_hash,
            'timestamp' => $invoice->verifactu_timestamp->timestamp,
            'verify_url' => route('invoices.verify', ['hash' => $invoice->verifactu_hash]),
        ];
        
        return json_encode($data);
    }
    
    /**
     * Verifica la autenticidad de una factura firmada
     *
     * @param Invoice $invoice
     * @return bool
     */
    public function verifyInvoice(Invoice $invoice)
    {
        // Si la factura no tiene hash o firma, no está verificada
        if (empty($invoice->verifactu_hash) || empty($invoice->verifactu_signature)) {
            return false;
        }
        
        // Regenerar el hash y comparar con el almacenado
        $currentHash = $this->generateInvoiceHash($invoice);
        
        // Si el hash actual no coincide con el almacenado, la factura ha sido modificada
        return $currentHash === $invoice->verifactu_hash;
    }
    
    /**
     * Verifica la huella digital de una factura y devuelve información detallada
     *
     * @param Invoice $invoice
     * @return array
     */
    public function verifyDigitalFingerprint(Invoice $invoice)
    {
        $result = [
            'verified' => false,
            'hash_match' => false,
            'has_signature' => false,
            'has_hash' => false,
            'timestamp' => null,
            'message' => ''
        ];
        
        // Verificar si la factura tiene hash y firma
        $result['has_hash'] = !empty($invoice->verifactu_hash);
        $result['has_signature'] = !empty($invoice->verifactu_signature);
        $result['timestamp'] = $invoice->verifactu_timestamp ?? null;
        
        if (!$result['has_hash'] || !$result['has_signature']) {
            $result['message'] = 'La factura no tiene huella digital o firma.';
            return $result;
        }
        
        // Regenerar el hash y comparar con el almacenado
        $currentHash = $this->generateInvoiceHash($invoice);
        $result['hash_match'] = ($currentHash === $invoice->verifactu_hash);
        
        // La verificación es exitosa si el hash coincide
        $result['verified'] = $result['hash_match'];
        
        if ($result['verified']) {
            $result['message'] = 'La factura ha sido verificada correctamente.';
        } else {
            $result['message'] = 'La factura ha sido modificada después de ser firmada.';
        }
        
        return $result;
    }
}
