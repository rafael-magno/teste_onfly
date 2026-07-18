<?php

namespace App\Policies;

use App\Models\User;

class TravelOrderPolicy
{
    public function updateStatus(User $user): bool
    {
        return $user->isAdmin();
    }
}
