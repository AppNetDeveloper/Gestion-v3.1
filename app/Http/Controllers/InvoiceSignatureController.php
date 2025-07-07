<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\DigitalCertificate;
use App\Services\VeriFactuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceSignatureController extends Controller
{
    protected $veriFactuService;

    public function __construct(VeriFactuService $veriFactuService)
    {
        $this->veriFactuService = $veriFactuService;
        $this->middleware('auth');
    }

    /**
     * Muestra el formulario para firmar una factura
     *
     * @param Invoice $invoice
     * @return \Illuminate\View\View
     */
    public function showSignForm(Invoice $invoice)
    {
        $this->authorize('sign', $invoice);

        // Si la factura ya está firmada, redirigir con mensaje
        if (!empty($invoice->verifactu_hash)) {
            return redirect()->route('invoices.show', $invoice)
                ->with('warning', __('This invoice is already digitally signed.'));
        }

        // Obtener certificados digitales activos y válidos
        $certificates = DigitalCertificate::active()->valid()->get();

        if ($certificates->isEmpty()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', __('No active digital certificates available. Please add a valid certificate first.'));
        }

        return view('invoices.sign', compact('invoice', 'certificates'));
    }

    /**
     * Procesa la firma digital de una factura
     *
     * @param Request $request
     * @param Invoice $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signInvoice(Request $request, Invoice $invoice)
    {
        $this->authorize('sign', $invoice);

        // Validar el formulario
        $validated = $request->validate([
            'certificate_id' => 'required|exists:digital_certificates,id',
        ]);

        // Si la factura ya está firmada, redirigir con mensaje
        if (!empty($invoice->verifactu_hash)) {
            return redirect()->route('invoices.show', $invoice)
                ->with('warning', __('This invoice is already digitally signed.'));
        }

        try {
            // Obtener el certificado seleccionado
            $certificate = DigitalCertificate::findOrFail($validated['certificate_id']);
            
            // Verificar que el certificado sea válido
            if (!$certificate->is_active || $certificate->isExpired()) {
                return redirect()->back()
                    ->with('error', __('The selected certificate is not active or has expired.'))
                    ->withInput();
            }

            // Firmar la factura
            $success = $this->veriFactuService->signInvoice($invoice, $certificate);

            if ($success) {
                // Registrar la acción
                \Log::info('Invoice signed digitally', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'user_id' => auth()->id(),
                    'certificate_id' => $certificate->id
                ]);

                return redirect()->route('invoices.show', $invoice)
                    ->with('success', __('Invoice has been successfully signed with VeriFact.'));
            } else {
                return redirect()->back()
                    ->with('error', __('Failed to sign the invoice. Please try again or contact support.'))
                    ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Error signing invoice: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', __('An error occurred while signing the invoice: ') . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Verifica la autenticidad de una factura firmada
     *
     * @param string $hash
     * @return \Illuminate\View\View
     */
    public function verifyInvoice($hash)
    {
        $invoice = Invoice::where('verifactu_hash', $hash)->first();

        if (!$invoice) {
            return view('invoices.verify', [
                'verified' => false,
                'message' => __('Invoice not found or hash is invalid.')
            ]);
        }

        $verified = $this->veriFactuService->verifyInvoice($invoice);

        return view('invoices.verify', [
            'verified' => $verified,
            'invoice' => $invoice,
            'message' => $verified 
                ? __('The invoice is authentic and has not been modified.') 
                : __('Warning: The invoice data may have been modified after signing.')
        ]);
    }
}
