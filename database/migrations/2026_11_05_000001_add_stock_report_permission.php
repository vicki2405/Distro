<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat permission stock.report jika belum ada
        $perm = Permission::firstOrCreate([
            'name' => 'stock.report',
            'guard_name' => 'web',
        ]);

        // 2. Berikan ke role owner
        $ownerRole = Role::where('name', 'owner')->first();
        if ($ownerRole) {
            $ownerRole->givePermissionTo($perm);
        }

        // 3. Berikan ke role staff secara default
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo($perm);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('name', 'stock.report')->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
