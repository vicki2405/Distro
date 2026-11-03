<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage') || $user->hasRole('owner') || $user->isOwner();
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.manage') || $user->hasRole('owner') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage') || $user->hasRole('owner') || $user->isOwner();
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage') || $user->hasRole('owner') || $user->isOwner();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('users.manage') || $user->hasRole('owner') || $user->isOwner();
    }
}
