<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function view(User $user, Event $event): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->isAdmin() || ! $user->company_id) {
            return false;
        }

        return $user->company_id === $event->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && (bool) $user->company_id && $user->company->status === 'approved';
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->view($user, $event) && $user->company->status === 'approved';
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->view($user, $event) && $user->company->status === 'approved';
    }
}
