<?php

namespace App\Policies;

use App\Models\DigitalCertificate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DigitalCertificatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->can('digital_certificates index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DigitalCertificate $digitalCertificate): bool
    {
        return $user->hasRole('superadmin') || $user->can('digital_certificates show');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->can('digital_certificates create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DigitalCertificate $digitalCertificate): bool
    {
        return $user->hasRole('superadmin') || $user->can('digital_certificates update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DigitalCertificate $digitalCertificate): bool
    {
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DigitalCertificate $digitalCertificate): bool
    {
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DigitalCertificate $digitalCertificate): bool
    {
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can download the certificate file.
     */
    public function download(User $user, DigitalCertificate $digitalCertificate): bool
    {
        return $user->hasRole('superadmin') || $user->can('digital_certificates download');
    }
}
