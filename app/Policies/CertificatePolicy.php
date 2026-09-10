<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine whether the user can view any certificates.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStudent() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the certificate.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        // Students can strictly view only their own certificates; Admins can view any certificate
        return (int) $user->id === (int) $certificate->user_id || $user->isAdmin();
    }
}