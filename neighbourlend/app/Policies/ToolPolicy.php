<?php

namespace App\Policies;

use App\Domain\Models\Tool;
use App\Domain\Models\User;

class ToolPolicy
{
    public function update(User $user, Tool $tool): bool
    {
        return $user->id === $tool->owner_id;
    }

    public function delete(User $user, Tool $tool): bool
    {
        return $user->id === $tool->owner_id;
    }

    public function reserve(User $user, Tool $tool): bool
    {
        // Business rule: You cannot borrow your own tool
        return $user->id !== $tool->owner_id;
    }
}
