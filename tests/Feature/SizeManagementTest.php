<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SizeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    public function test_authenticated_user_can_view_sizes_index(): void
    {
        Size::create(['name' => 'XL', 'order' => 5]);

        $response = $this->actingAs($this->owner)->get(route('warehouse.sizes.index'));
        $response->assertOk();
    }

    public function test_can_create_new_size(): void
    {
        $response = $this->actingAs($this->owner)->post(route('warehouse.sizes.store'), [
            'name' => '5XL',
            'order' => 10,
        ]);

        $response->assertRedirect(route('warehouse.sizes.index'));
        $this->assertDatabaseHas('sizes', [
            'name' => '5XL',
            'order' => 10,
        ]);
    }

    public function test_cannot_create_duplicate_size(): void
    {
        Size::create(['name' => 'XL', 'order' => 5]);

        $response = $this->actingAs($this->owner)->post(route('warehouse.sizes.store'), [
            'name' => 'XL',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_update_size_and_sync_variants(): void
    {
        $size = Size::create(['name' => 'ALL_SIZE', 'order' => 9]);

        $category = Category::create(['name' => 'Kaos', 'slug' => 'kaos']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'TS-01',
            'name' => 'Kaos Polos',
            'selling_price' => 100000,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'ALL_SIZE',
            'sku' => 'TS-01-ALL',
            'current_stock' => 10,
        ]);

        $response = $this->actingAs($this->owner)->put(route('warehouse.sizes.update', $size->id), [
            'name' => 'FREESIZE',
            'order' => 12,
        ]);

        $response->assertRedirect(route('warehouse.sizes.index'));
        $this->assertDatabaseHas('sizes', ['name' => 'FREESIZE', 'order' => 12]);

        $variant->refresh();
        $this->assertEquals('FREESIZE', $variant->size);
    }

    public function test_cannot_delete_size_in_use(): void
    {
        $size = Size::create(['name' => 'M', 'order' => 3]);

        $category = Category::create(['name' => 'Kaos', 'slug' => 'kaos']);
        $product = Product::create([
            'category_id' => $category->id,
            'code' => 'TS-02',
            'name' => 'Kaos Hitam',
            'selling_price' => 100000,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'sku' => 'TS-02-M',
            'current_stock' => 5,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('warehouse.sizes.destroy', $size->id));
        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('sizes', ['id' => $size->id]);
    }

    public function test_can_delete_unused_size(): void
    {
        $size = Size::create(['name' => 'UNUSED', 'order' => 99]);

        $response = $this->actingAs($this->owner)->delete(route('warehouse.sizes.destroy', $size->id));
        $response->assertRedirect(route('warehouse.sizes.index'));
        $this->assertDatabaseMissing('sizes', ['id' => $size->id]);
    }
}
