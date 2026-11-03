<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->can('roles.manage') || $user->isOwner()), 403, 'Akses ditolak. Fitur ini hanya untuk Owner/Admin.');

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::orderBy('name')->get();

        $permissionsGrouped = $allPermissions->groupBy(function ($perm) {
            $prefix = explode('.', $perm->name)[0] ?? '';

            return match ($prefix) {
                'products' => 'Master Produk',
                'categories' => 'Kategori Produk',
                'stock' => 'Operasional Gudang & Stok',
                'users', 'roles' => 'Administrasi & Hak Akses',
                default => 'Lainnya',
            };
        });

        return Inertia::render('Warehouse/Roles/Index', [
            'roles' => $roles,
            'permissionsGrouped' => $permissionsGrouped,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->can('roles.manage') || $user->isOwner()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.required' => 'Nama peran (role) wajib diisi.',
            'name.unique' => 'Nama peran ini sudah digunakan.',
        ]);

        $role = Role::create([
            'name' => strtolower(trim($validated['name'])),
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('warehouse.roles.index')
            ->with('success', "Peran '{$role->name}' berhasil dibuat dengan hak akses yang ditentukan.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->can('roles.manage') || $user->isOwner()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.required' => 'Nama peran (role) wajib diisi.',
            'name.unique' => 'Nama peran ini sudah digunakan.',
        ]);

        if ($role->name !== 'owner') {
            $role->update([
                'name' => strtolower(trim($validated['name'])),
            ]);
        }

        // Owner role selalu memiliki semua permission
        if ($role->name === 'owner') {
            $role->syncPermissions(Permission::all());
        } else {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        return redirect()->route('warehouse.roles.index')
            ->with('success', "Hak akses peran '{$role->name}' berhasil diperbarui.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->can('roles.manage') || $user->isOwner()), 403);

        if ($role->name === 'owner' || $role->name === 'staff') {
            return back()->with('error', "Peran bawaan '{$role->name}' adalah peran sistem inti dan tidak boleh dihapus.");
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Peran '{$role->name}' tidak dapat dihapus karena masih digunakan oleh {$role->users()->count()} pengguna. Pindahkan pengguna ke peran lain terlebih dahulu.");
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('warehouse.roles.index')
            ->with('success', "Peran '{$roleName}' berhasil dihapus dari sistem.");
    }
}
