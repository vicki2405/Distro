<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|string|int $targetUser */
        $targetUser = $this->route('user');
        $userModel = is_object($targetUser) ? $targetUser : User::find($targetUser);

        return $this->user()?->can('update', $userModel)
            || $this->user()?->isOwner()
            || false;
    }

    public function rules(): array
    {
        /** @var User|string|int $targetUser */
        $targetUser = $this->route('user');
        $userId = is_object($targetUser) ? $targetUser->id : $targetUser;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh pengguna lain.',
            'role.required' => 'Peran (role) pengguna wajib dipilih.',
            'role.exists' => 'Peran yang dipilih tidak terdaftar di sistem.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }
}
