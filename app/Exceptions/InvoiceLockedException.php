<?php

namespace App\Exceptions;

use Exception;

class InvoiceLockedException extends Exception
{
    protected $message = 'La factura está bloqueada y no puede ser modificada.';

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 403);
        }

        return redirect()->back()
            ->with('error', $this->getMessage());
    }
}