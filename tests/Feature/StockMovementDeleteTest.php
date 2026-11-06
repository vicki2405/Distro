<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockMovementDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'email' => 'admin@distro.test',
        ]);
        $this->owner->assignRole($ownerRole);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@distro.test',
        ]);
        $this->staff->assignRole($staffRole);

        $category = Category::create(['name' => 'T-Shirt', 'slug' => 't-shirt']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kaos Polos Hitam',
            'code' => 'KPS-01',
            'cost_price' => 40000,
            'selling_price' => 90000,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KPS-01-L',
            'size' => 'L',
            'current_stock' => 50,
        ]);
    }

    public function test_owner_can_delete_stock_in_movement_and_stock_is_deducted(): void
    {
        // Mutasi masuk +20 pcs, current_stock sekarang 50
        $movement = StockMovement::create([
            'product_variant_id' => $this->variant->id,
            'user_id' => $this->staff->id,
            'type' => 'in',
            'quantity' => 20,
            'stock_before' => 30,
            'stock_after' => 50,
            'reference_number' => 'SJ-IN-TEST-01',
            'destination' => 'Konveksi Bandung',
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('warehouse.in.destroy', $movement->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan record mutasi terhapus
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);

        // Pastikan stok varian berkurang kembali sebanyak 20 (50 - 20 = 30)
        $this->assertEquals(30, $this->variant->fresh()->current_stock);
    }

    public function test_owner_cannot_delete_stock_in_movement_if_stock_is_insufficient(): void
    {
        // Mutasi masuk 50 pcs, tetapi sisa stok saat ini tinggal 10
        $this->variant->update(['current_stock' => 10]);

        $movement = StockMovement::create([
            'product_variant_id' => $this->variant->id,
            'user_id' => $this->staff->id,
            'type' => 'in',
            'quantity' => 50,
            'stock_before' => 0,
            'stock_after' => 50,
            'reference_number' => 'SJ-IN-TEST-02',
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('warehouse.in.destroy', $movement->id));

        $response->assertSessionHasErrors('error');

        // Record tetap ada dan stok tidak berubah
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
        $this->assertEquals(10, $this->variant->fresh()->current_stock);
    }

    public function test_owner_can_delete_stock_out_movement_and_stock_is_restored(): void
    {
        // Mutasi keluar 15 pcs, current_stock sekarang 50
        $movement = StockMovement::create([
            'product_variant_id' => $this->variant->id,
            'user_id' => $this->staff->id,
            'type' => 'out',
            'quantity' => 15,
            'stock_before' => 65,
            'stock_after' => 50,
            'reference_number' => 'SJ-OUT-TEST-01',
            'destination' => 'Toko Cabang Depok',
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('warehouse.out.destroy', $movement->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan record mutasi terhapus
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);

        // Pastikan stok varian bertambah kembali sebanyak 15 (50 + 15 = 65)
        $this->assertEquals(65, $this->variant->fresh()->current_stock);
    }

    public function test_staff_cannot_delete_stock_in_movement(): void
    {
        $movement = StockMovement::create([
            'product_variant_id' => $this->variant->id,
            'user_id' => $this->staff->id,
            'type' => 'in',
            'quantity' => 10,
            'stock_before' => 40,
            'stock_after' => 50,
            'reference_number' => 'SJ-IN-STAFF',
        ]);

        $response = $this->actingAs($this->staff)
            ->delete(route('warehouse.in.destroy', $movement->id));

        $response->assertStatus(403);

        // Record tetap ada dan stok tidak berubah
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
        $this->assertEquals(50, $this->variant->fresh()->current_stock);
    }

    public function test_staff_cannot_delete_stock_out_movement(): void
    {
        $movement = StockMovement::create([
            'product_variant_id' => $this->variant->id,
            'user_id' => $this->staff->id,
            'type' => 'out',
            'quantity' => 5,
            'stock_before' => 55,
            'stock_after' => 50,
            'reference_number' => 'SJ-OUT-STAFF',
        ]);

        $response = $this->actingAs($this->staff)
            ->delete(route('warehouse.out.destroy', $movement->id));

        $response->assertStatus(403);

        // Record tetap ada dan stok tidak berubah
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
        $this->assertEquals(50, $this->variant->fresh()->current_stock);
    }
}
