<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\VeriFactuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceVerificationController extends Controller
{
    protected $veriFactuService;

    public function __construct(VeriFactuService $veriFactuService)
    {
        $this->veriFactuService = $veriFactuService;
    }

    /**
     * Muestra el formulario de verificación
     */
    public function showVerificationForm($id = null)
    {
        \Log::info('showVerificationForm llamado con ID: ' . $id);
        $invoice = null;
        $verificationResult = null;
        
        if ($id) {
            $invoice = Invoice::with('client')->find($id);
            \Log::info('Factura encontrada:', $invoice ? $invoice->toArray() : ['message' => 'No se encontró la factura']);
            
            if ($invoice) {
                if (!$invoice->verifactu_hash) {
                    \Log::info('La factura no tiene huella digital. Generando...');
                    $this->veriFactuService->generateDigitalFingerprint($invoice);
                    $invoice->refresh();
                }
                $verificationResult = $this->veriFactuService->verifyDigitalFingerprint($invoice);
                \Log::info('Resultado de verificación:', $verificationResult);
            }
        }
        
        return view('invoices.verify', [
            'invoice' => $invoice,
            'verificationResult' => $verificationResult
        ]);
    }

    /**
     * Procesa la verificación de la factura
     */
    public function verify(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string|max:255',
        ]);

        $invoice = Invoice::with('client')
            ->where('invoice_number', $request->invoice_number)
            ->first();

        if (!$invoice) {
            return redirect()->back()
                ->with('error', 'No se encontró ninguna factura con ese número.')
                ->withInput();
        }

        $verificationResult = $this->veriFactuService->verifyDigitalFingerprint($invoice);

        return view('invoices.verify', [
            'invoice' => $invoice,
            'verificationResult' => $verificationResult,
            'searched' => true
        ]);
    }
}
