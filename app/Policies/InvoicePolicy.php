<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view invoices');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Invoice $invoice)
    {
        return $user->hasPermissionTo('view invoices');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('create invoices');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            return false;
        }
        
        return $user->hasPermissionTo('edit invoices');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            return false;
        }
        
        return $user->hasPermissionTo('delete invoices');
    }

    /**
     * Determine whether the user can sign the invoice.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function sign(User $user, Invoice $invoice)
    {
        // No permitir firmar facturas ya firmadas
        if (!empty($invoice->verifactu_hash)) {
            return false;
        }
        
        return $user->hasPermissionTo('invoices sign');
    }

    /**
     * Determine whether the user can lock or unlock the invoice.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function toggleLock(User $user, Invoice $invoice)
    {
        // Si la factura está firmada digitalmente, solo super-admin puede desbloquearla
        if (!empty($invoice->verifactu_hash) && !$user->hasRole('super-admin')) {
            return false;
        }
        
        return $user->hasPermissionTo('lock invoices');
    }
}
