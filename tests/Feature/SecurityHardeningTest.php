<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected Product $product;

    protected ProductVariant $variant;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $permissions = [
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'categories.view', 'categories.manage',
            'sizes.view', 'sizes.manage',
            'stock.view', 'stock.in', 'stock.out', 'stock.opname', 'stock.report',
            'users.manage', 'roles.manage', 'settings.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $ownerRole->syncPermissions(Permission::all());
        $staffRole->syncPermissions([
            'products.view',
            'categories.view',
            'sizes.view',
            'stock.view',
            'stock.in',
            'stock.out',
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->owner->assignRole($ownerRole);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->assignRole($staffRole);

        $this->category = Category::create(['name' => 'Kaos Security', 'slug' => 'kaos-security']);
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'code' => 'SEC-01',
            'name' => 'Kaos Security Test',
            'cost_price' => 50000,
            'selling_price' => 125000,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'size' => 'L',
            'sku' => 'SEC-01-L',
            'current_stock' => 15,
        ]);
    }

    public function test_cost_price_is_hidden_from_public_landing_page(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $products = $page->toArray()['props']['products']['data'];
            $this->assertNotEmpty($products);
            foreach ($products as $p) {
                $this->assertArrayNotHasKey('cost_price', $p, 'HPP Modal (cost_price) must not be exposed to the public landing page.');
            }
        });
    }

    public function test_staff_cannot_create_master_product(): void
    {
        $response = $this->actingAs($this->staff)->post(route('warehouse.products.store'), [
            'name' => 'Hacked Product',
            'category_id' => $this->category->id,
            'selling_price' => 99000,
            'sizes' => ['M'],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => 'Hacked Product']);
    }

    public function test_staff_cannot_delete_master_product(): void
    {
        $newProduct = Product::create([
            'category_id' => $this->category->id,
            'code' => 'DEL-01',
            'name' => 'Product To Delete',
            'cost_price' => 30000,
            'selling_price' => 80000,
        ]);

        $response = $this->actingAs($this->staff)->delete(route('warehouse.products.destroy', $newProduct->id));

        $response->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $newProduct->id]);
    }

    public function test_staff_without_manage_permission_cannot_create_category(): void
    {
        $response = $this->actingAs($this->staff)->post(route('warehouse.categories.store'), [
            'name' => 'Unauthorized Category',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Unauthorized Category']);
    }

    public function test_staff_without_manage_permission_cannot_create_size(): void
    {
        $response = $this->actingAs($this->staff)->post(route('warehouse.sizes.store'), [
            'name' => 'FAKE_SIZE',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sizes', ['name' => 'FAKE_SIZE']);
    }

    public function test_non_owner_cannot_modify_or_hijack_owner_account(): void
    {
        // Berikan hak akses users.manage pada staff
        $this->staff->givePermissionTo('users.manage');

        $response = $this->actingAs($this->staff)->put(route('warehouse.users.update', $this->owner->id), [
            'name' => 'Hacked Owner',
            'email' => 'hacked@owner.com',
            'role' => 'staff',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertForbidden();

        $this->owner->refresh();
        $this->assertNotEquals('Hacked Owner', $this->owner->name);
        $this->assertEquals('owner', $this->owner->role);
    }

    public function test_non_owner_cannot_promote_user_to_owner(): void
    {
        $this->staff->givePermissionTo('users.manage');

        $targetUser = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($this->staff)->put(route('warehouse.users.update', $targetUser->id), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => 'owner',
        ]);

        $response->assertSessionHas('error');
        $targetUser->refresh();
        $this->assertEquals('staff', $targetUser->role);
    }

    public function test_cross_product_variant_manipulation_is_rejected_on_stock_out(): void
    {
        // Buat produk kedua dengan variannya
        $otherProduct = Product::create([
            'category_id' => $this->category->id,
            'code' => 'OTHER-01',
            'name' => 'Jaket Kulit Mahal',
            'cost_price' => 200000,
            'selling_price' => 450000,
        ]);

        $otherVariant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'size' => 'XL',
            'sku' => 'OTHER-01-XL',
            'current_stock' => 10,
        ]);

        // Coba kirim transaksi keluar untuk $this->product, tapi menyusupkan variant_id milik $otherProduct
        $response = $this->actingAs($this->staff)->post(route('warehouse.out.store'), [
            'product_id' => $this->product->id, // Produk Kaos
            'destination' => 'Uji Cross Product',
            'reference_number' => 'DO-CROSS-01',
            'items' => [
                ['variant_id' => $otherVariant->id, 'quantity' => 2], // Varian milik Jaket
            ],
        ]);

        $response->assertSessionHasErrors('error');

        // Pastikan stok jaket tidak berkurang
        $otherVariant->refresh();
        $this->assertEquals(10, $otherVariant->current_stock);
    }

    public function test_stock_opname_cannot_be_adjusted_twice(): void
    {
        $opname = StockOpname::create([
            'user_id' => $this->staff->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 15,
            'physical_stock' => 12,
            'difference' => -3,
        ]);

        // Adjustment pertama oleh Owner berhasil
        $firstResponse = $this->actingAs($this->owner)->post(route('warehouse.opnames.adjust', $opname->id));
        $firstResponse->assertSessionHas('success');
        $this->assertEquals(12, $this->variant->fresh()->current_stock);

        // Adjustment kedua ditolak
        $secondResponse = $this->actingAs($this->owner)->post(route('warehouse.opnames.adjust', $opname->id));
        $secondResponse->assertSessionHas('error');
    }
}
