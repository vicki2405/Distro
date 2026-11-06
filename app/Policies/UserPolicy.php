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
        // Akun Owner hanya boleh diubah oleh sesama Owner, tidak boleh oleh pemegang hak akses users.manage non-owner
        if ($model->isOwner() && ! $user->isOwner()) {
            return false;
        }

        return $user->can('users.manage') || $user->hasRole('owner') || $user->isOwner();
    }

    public function delete(User $user, User $model): bool
    {
        // Non-owner tidak boleh menghapus akun Owner
        if ($model->isOwner() && ! $user->isOwner()) {
            return false;
        }

        return $user->can('users.manage') || $user->hasRole('owner') || $user->isOwner();
    }
}
