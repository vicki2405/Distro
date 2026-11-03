<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);

        $this->owner = User::factory()->create([
            'name' => 'Owner Utama',
            'email' => 'owner@distro.com',
            'role' => 'owner',
        ]);
        $this->owner->assignRole($ownerRole);

        $this->staff = User::factory()->create([
            'name' => 'Staff Gudang',
            'email' => 'staff@distro.com',
            'role' => 'staff',
        ]);
        $this->staff->assignRole($staffRole);
    }

    public function test_owner_can_view_user_management_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('warehouse.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Warehouse/Users/Index')
            ->has('users.data', 2)
            ->has('stats')
        );
    }

    public function test_staff_cannot_view_user_management_page_and_receives_403(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.users.index'));

        $response->assertStatus(403);
    }

    public function test_owner_can_create_new_staff_user(): void
    {
        $response = $this->actingAs($this->owner)->post(route('warehouse.users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'karyawan.baru@distro.com',
            'role' => 'staff',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('warehouse.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Karyawan Baru',
            'email' => 'karyawan.baru@distro.com',
            'role' => 'staff',
        ]);

        $createdUser = User::where('email', 'karyawan.baru@distro.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_owner_can_update_user_details_and_password(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'User Lama',
            'email' => 'userlama@distro.com',
            'role' => 'staff',
        ]);

        $response = $this->actingAs($this->owner)->put(route('warehouse.users.update', $targetUser->id), [
            'name' => 'User Baru Diperbarui',
            'email' => 'userbaru@distro.com',
            'role' => 'owner',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('warehouse.users.index'));

        $targetUser->refresh();
        $this->assertSame('User Baru Diperbarui', $targetUser->name);
        $this->assertSame('userbaru@distro.com', $targetUser->email);
        $this->assertSame('owner', $targetUser->role);
        $this->assertTrue(Hash::check('newpassword123', $targetUser->password));
    }

    public function test_owner_cannot_demote_their_own_role(): void
    {
        $response = $this->actingAs($this->owner)->put(route('warehouse.users.update', $this->owner->id), [
            'name' => $this->owner->name,
            'email' => $this->owner->email,
            'role' => 'staff',
        ]);

        $response->assertSessionHas('error');
        $this->owner->refresh();
        $this->assertSame('owner', $this->owner->role);
    }

    public function test_owner_cannot_delete_their_own_account(): void
    {
        $response = $this->actingAs($this->owner)->delete(route('warehouse.users.destroy', $this->owner->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_owner_cannot_delete_user_with_stock_movements(): void
    {
        $category = Category::create(['name' => 'Kaos', 'slug' => 'kaos']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'TS-TEST',
            'name' => 'Kaos Mutasi',
            'cost_price' => 50000,
            'selling_price' => 100000,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'sku' => 'TS-TEST-M',
            'current_stock' => 10,
        ]);

        StockMovement::create([
            'product_variant_id' => $variant->id,
            'user_id' => $this->staff->id,
            'type' => 'in',
            'quantity' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('warehouse.users.destroy', $this->staff->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->staff->id]);
    }

    public function test_owner_can_delete_user_without_stock_movements(): void
    {
        $deletableStaff = User::factory()->create([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('warehouse.users.destroy', $deletableStaff->id));

        $response->assertRedirect(route('warehouse.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $deletableStaff->id]);
    }
}
