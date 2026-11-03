<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'roles.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'stock.in', 'guard_name' => 'web']);
        Permission::create(['name' => 'stock.out', 'guard_name' => 'web']);

        $ownerRole = Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $ownerRole->givePermissionTo(Permission::all());

        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);
        $staffRole->givePermissionTo(['stock.in']);

        $this->owner = User::factory()->create([
            'role' => 'owner',
        ]);
        $this->owner->assignRole($ownerRole);

        $this->staff = User::factory()->create([
            'role' => 'staff',
        ]);
        $this->staff->assignRole($staffRole);
    }

    public function test_owner_can_view_role_list(): void
    {
        $response = $this->actingAs($this->owner)->get(route('warehouse.roles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Warehouse/Roles/Index')
            ->has('roles')
            ->has('permissionsGrouped')
        );
    }

    public function test_staff_cannot_view_role_list(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.roles.index'));

        $response->assertStatus(403);
    }

    public function test_owner_can_create_new_role_with_permissions(): void
    {
        $response = $this->actingAs($this->owner)->post(route('warehouse.roles.store'), [
            'name' => 'Kasir Toko',
            'permissions' => ['stock.in', 'stock.out'],
        ]);

        $response->assertRedirect(route('warehouse.roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'kasir toko',
        ]);

        $createdRole = Role::where('name', 'kasir toko')->first();
        $this->assertTrue($createdRole->hasPermissionTo('stock.in'));
        $this->assertTrue($createdRole->hasPermissionTo('stock.out'));
    }

    public function test_owner_can_update_role_permissions(): void
    {
        $customRole = Role::create(['name' => 'operator input', 'guard_name' => 'web']);
        $customRole->syncPermissions(['stock.in']);

        $response = $this->actingAs($this->owner)->put(route('warehouse.roles.update', $customRole->id), [
            'name' => 'Operator Baru',
            'permissions' => ['stock.out'],
        ]);

        $response->assertRedirect(route('warehouse.roles.index'));

        $customRole->refresh();
        $this->assertSame('operator baru', $customRole->name);
        $this->assertFalse($customRole->hasPermissionTo('stock.in'));
        $this->assertTrue($customRole->hasPermissionTo('stock.out'));
    }

    public function test_owner_cannot_delete_built_in_role(): void
    {
        $ownerRole = Role::where('name', 'owner')->first();

        $response = $this->actingAs($this->owner)->delete(route('warehouse.roles.destroy', $ownerRole->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['name' => 'owner']);
    }

    public function test_owner_cannot_delete_role_in_use(): void
    {
        $inUseRole = Role::create(['name' => 'picker', 'guard_name' => 'web']);
        $this->staff->assignRole($inUseRole);

        $response = $this->actingAs($this->owner)->delete(route('warehouse.roles.destroy', $inUseRole->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['name' => 'picker']);
    }

    public function test_owner_can_delete_unused_role(): void
    {
        $unusedRole = Role::create(['name' => 'temp role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->owner)->delete(route('warehouse.roles.destroy', $unusedRole->id));

        $response->assertRedirect(route('warehouse.roles.index'));
        $this->assertDatabaseMissing('roles', ['name' => 'temp role']);
    }
}
