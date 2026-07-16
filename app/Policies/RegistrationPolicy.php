<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $user->company_id && $registration->event->company_id === $user->company_id;
    }

    public function update(User $user, Registration $registration): bool
    {
        return $this->view($user, $registration);
    }
}
