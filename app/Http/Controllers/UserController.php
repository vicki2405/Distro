<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleFilter = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            })->orWhere('role', $roleFilter);
        }

        $users = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $availableRoles = Role::orderBy('name')->get();

        $stats = [
            'total' => User::count(),
            'owners' => User::role('owner')->count(),
            'staff' => User::role('staff')->count(),
        ];

        return Inertia::render('Warehouse/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'stats' => $stats,
            'availableRoles' => $availableRoles,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        event(new Registered($user));

        return redirect()->route('warehouse.users.index')
            ->with('success', "Akun '{$validated['name']}' berhasil dibuat dan terhubung ke peran '{$validated['role']}'.");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        if ($user->id === $request->user()->id && $validated['role'] !== 'owner') {
            return back()->with('error', 'Anda tidak dapat menurunkan peran (role) akun Anda sendiri.');
        }

        $payload = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('warehouse.users.index')
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->stockMovements()->exists() || $user->stockOpnames()->exists()) {
            return back()->with('error', "Pengguna '{$user->name}' tidak dapat dihapus karena tercatat dalam riwayat mutasi/opname stok gudang. Anda dapat mengganti perannya atau memperbarui kata sandinya.");
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('warehouse.users.index')
            ->with('success', "Pengguna '{$userName}' berhasil dihapus dari sistem.");
    }
}
