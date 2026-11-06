<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected Product $product;

    protected ProductVariant $variantL;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->staff = User::factory()->create(['role' => 'staff']);

        $this->category = Category::create(['name' => 'Kaos T-Shirt', 'slug' => 'kaos-t-shirt']);
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'code' => 'TS-TEST-01',
            'name' => 'Kaos Test Distro',
            'cost_price' => 50000,
            'selling_price' => 120000,
        ]);

        $this->variantL = ProductVariant::create([
            'product_id' => $this->product->id,
            'size' => 'L',
            'sku' => 'TS-TEST-01-L',
            'current_stock' => 20,
        ]);
    }

    public function test_authenticated_user_can_view_warehouse_dashboard(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.dashboard'));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_view_product_master_list(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.products.index'));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_view_category_list(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.categories.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_new_category(): void
    {
        $response = $this->actingAs($this->owner)->post(route('warehouse.categories.store'), [
            'name' => 'Celana Jeans Denim',
        ]);

        $response->assertRedirect(route('warehouse.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Celana Jeans Denim',
            'slug' => 'celana-jeans-denim',
        ]);
    }

    public function test_can_update_category(): void
    {
        $category = Category::create(['name' => 'Topi', 'slug' => 'topi']);

        $response = $this->actingAs($this->owner)->put(route('warehouse.categories.update', $category->id), [
            'name' => 'Topi & Beanie',
        ]);

        $response->assertRedirect(route('warehouse.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Topi & Beanie',
            'slug' => 'topi-beanie',
        ]);
    }

    public function test_cannot_delete_category_with_associated_products(): void
    {
        // Category $this->category has $this->product
        $response = $this->actingAs($this->owner)->delete(route('warehouse.categories.destroy', $this->category->id));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('categories', ['id' => $this->category->id]);
    }

    public function test_can_create_new_master_product_with_variants(): void
    {
        $response = $this->actingAs($this->owner)->post(route('warehouse.products.store'), [
            'name' => 'Kaos Baru Master',
            'category_id' => $this->category->id,
            'cost_price' => 45000,
            'selling_price' => 110000,
            'description' => 'Heavy cotton 24s',
            'sizes' => ['S', 'M', 'L', 'XL'],
        ]);

        $response->assertRedirect(route('warehouse.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Kaos Baru Master',
            'cost_price' => 45000,
            'selling_price' => 110000,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'size' => 'S',
            'current_stock' => 0,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'size' => 'XL',
            'current_stock' => 0,
        ]);
    }

    public function test_can_view_product_detail_page(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.products.show', $this->product->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Warehouse/Products/Show')
            ->has('product')
            ->has('totalStock')
            ->has('movements')
        );
    }

    public function test_can_update_master_product(): void
    {
        $response = $this->actingAs($this->owner)->put(route('warehouse.products.update', $this->product->id), [
            'name' => 'Kaos Test Distro V2',
            'category_id' => $this->category->id,
            'cost_price' => 52000,
            'selling_price' => 125000,
            'description' => 'Updated spec',
            'new_sizes' => ['XXL'],
        ]);

        $response->assertRedirect(route('warehouse.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'Kaos Test Distro V2',
            'selling_price' => 125000,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $this->product->id,
            'size' => 'XXL',
            'current_stock' => 0,
        ]);
    }

    public function test_can_create_and_update_product_with_image_upload(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('hoodie.webp', 800, 800);

        $response = $this->actingAs($this->owner)->post(route('warehouse.products.store'), [
            'name' => 'Hoodie Zipper Heavy',
            'category_id' => $this->category->id,
            'cost_price' => 90000,
            'selling_price' => 220000,
            'description' => 'Fleece cotton 330gsm',
            'image' => $image,
            'sizes' => ['M', 'L', 'XL'],
        ]);

        $response->assertRedirect(route('warehouse.products.index'));

        $product = Product::where('name', 'Hoodie Zipper Heavy')->firstOrFail();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertNotNull($product->image_url);

        // Update with new image
        $newImage = UploadedFile::fake()->image('hoodie-v2.webp', 800, 800);
        $oldImagePath = $product->image;

        $updateResponse = $this->actingAs($this->owner)->put(route('warehouse.products.update', $product->id), [
            'name' => 'Hoodie Zipper Heavy V2',
            'category_id' => $this->category->id,
            'cost_price' => 95000,
            'selling_price' => 230000,
            'image' => $newImage,
        ]);

        $updateResponse->assertRedirect(route('warehouse.products.index'));

        $product->refresh();
        $this->assertEquals('Hoodie Zipper Heavy V2', $product->name);
        Storage::disk('public')->assertExists($product->image);
        Storage::disk('public')->assertMissing($oldImagePath);
    }

    public function test_can_upload_and_manage_multiple_gallery_photos(): void
    {
        Storage::fake('public');

        $imgFront = UploadedFile::fake()->image('depan.webp', 800, 800);
        $imgBack = UploadedFile::fake()->image('belakang.webp', 800, 800);
        $imgLeft = UploadedFile::fake()->image('kiri.webp', 800, 800);
        $imgRight = UploadedFile::fake()->image('kanan.webp', 800, 800);
        $imgDetail = UploadedFile::fake()->image('detail.webp', 800, 800);

        // Store with 5 photos
        $response = $this->actingAs($this->owner)->post(route('warehouse.products.store'), [
            'name' => 'Jaket Coach Vintage',
            'category_id' => $this->category->id,
            'cost_price' => 120000,
            'selling_price' => 280000,
            'description' => 'Taslan waterproof',
            'images' => [$imgFront, $imgBack, $imgLeft, $imgRight, $imgDetail],
            'image_labels' => ['Tampak Depan', 'Tampak Belakang', 'Samping Kiri', 'Samping Kanan', 'Detail Kancing'],
            'sizes' => ['M', 'L', 'XL'],
        ]);

        $response->assertRedirect(route('warehouse.products.index'));

        $product = Product::where('name', 'Jaket Coach Vintage')->firstOrFail();
        $this->assertCount(5, $product->images);
        $this->assertNotNull($product->image);

        // Show page loads gallery
        $showResponse = $this->actingAs($this->staff)->get(route('warehouse.products.show', $product->id));
        $showResponse->assertOk();

        // Update: delete 1 photo, add another
        $deletedImgId = $product->images->first()->id;
        $imgNewExtra = UploadedFile::fake()->image('extra.webp', 800, 800);

        $updateResponse = $this->actingAs($this->owner)->put(route('warehouse.products.update', $product->id), [
            'name' => 'Jaket Coach Vintage',
            'category_id' => $this->category->id,
            'selling_price' => 280000,
            'deleted_image_ids' => [$deletedImgId],
            'images' => [$imgNewExtra],
            'image_labels' => ['Foto Tambahan'],
        ]);

        $updateResponse->assertRedirect(route('warehouse.products.index'));
        $product->refresh();
        $this->assertCount(5, $product->images); // 5 - 1 + 1 = 5
        $this->assertDatabaseMissing('product_images', ['id' => $deletedImgId]);
        $this->assertNotNull($product->image);
        $this->assertTrue(Storage::disk('public')->exists($product->image));
    }

    public function test_owner_can_edit_stock_delete_and_add_variants(): void
    {
        $variantM = ProductVariant::create([
            'product_id' => $this->product->id,
            'size' => 'M',
            'sku' => 'TS-TEST-01-M',
            'current_stock' => 5,
        ]);
        $variantL = $this->variantL;

        // 1. Owner updates stock of Size L from 20 to 25, deletes Size M, and adds Size 3XL with 10 pcs
        $response = $this->actingAs($this->owner)->put(route('warehouse.products.update', $this->product->id), [
            'name' => $this->product->name,
            'category_id' => $this->category->id,
            'selling_price' => $this->product->selling_price,
            'variant_stocks' => [
                ['id' => $variantL->id, 'current_stock' => 25],
            ],
            'deleted_variant_ids' => [$variantM->id],
            'new_variants' => [
                ['size' => '3XL', 'initial_stock' => 10],
            ],
        ]);

        $response->assertRedirect(route('warehouse.products.index'));

        // Check Size L stock updated to 25
        $variantL->refresh();
        $this->assertEquals(25, $variantL->current_stock);

        // Check Size M was deleted
        $this->assertDatabaseMissing('product_variants', ['id' => $variantM->id]);

        // Check Size 3XL was created with 10 pcs
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $this->product->id,
            'size' => '3XL',
            'current_stock' => 10,
        ]);

        // Check adjustment movements were recorded in audit ledger
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variantL->id,
            'type' => 'adjustment',
            'stock_before' => 20,
            'stock_after' => 25,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_delete_all_variants_of_a_product(): void
    {
        $response = $this->actingAs($this->owner)->put(route('warehouse.products.update', $this->product->id), [
            'name' => $this->product->name,
            'category_id' => $this->category->id,
            'selling_price' => $this->product->selling_price,
            'deleted_variant_ids' => [$this->variantL->id],
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('product_variants', ['id' => $this->variantL->id]);
    }

    public function test_cannot_delete_product_with_existing_stock_movements(): void
    {
        // Catat mutasi untuk produk
        $this->actingAs($this->staff)->post(route('warehouse.in.store'), [
            'product_id' => $this->product->id,
            'reference_number' => 'SJ-DEL-TEST',
            'items' => [
                ['size' => 'L', 'quantity' => 10],
            ],
        ]);

        // Coba hapus
        $response = $this->actingAs($this->owner)->delete(route('warehouse.products.destroy', $this->product->id));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
    }

    public function test_can_record_incoming_stock(): void
    {
        $response = $this->actingAs($this->staff)->post(route('warehouse.in.store'), [
            'product_id' => $this->product->id,
            'reference_number' => 'PO-TEST-101',
            'supplier_name' => 'Konveksi Uji',
            'items' => [
                ['size' => 'L', 'quantity' => 15],
            ],
        ]);

        $response->assertRedirect(route('warehouse.dashboard'));

        $this->variantL->refresh();
        $this->assertEquals(35, $this->variantL->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variantL->id,
            'type' => 'in',
            'quantity' => 15,
            'stock_before' => 20,
            'stock_after' => 35,
            'reference_number' => 'PO-TEST-101',
        ]);
    }

    public function test_can_record_outgoing_stock(): void
    {
        $response = $this->actingAs($this->staff)->post(route('warehouse.out.store'), [
            'product_id' => $this->product->id,
            'destination' => 'Kirim ke Toko Distro',
            'reference_number' => 'DO-TEST-202',
            'items' => [
                ['variant_id' => $this->variantL->id, 'quantity' => 8],
            ],
        ]);

        $response->assertRedirect(route('warehouse.dashboard'));

        $this->variantL->refresh();
        $this->assertEquals(12, $this->variantL->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variantL->id,
            'type' => 'out',
            'quantity' => 8,
            'stock_before' => 20,
            'stock_after' => 12,
            'reference_number' => 'DO-TEST-202',
        ]);
    }

    public function test_outgoing_stock_fails_when_quantity_exceeds_available_stock(): void
    {
        $response = $this->actingAs($this->staff)->post(route('warehouse.out.store'), [
            'product_id' => $this->product->id,
            'destination' => 'Kirim ke Toko Distro',
            'reference_number' => 'DO-EXCEED',
            'items' => [
                ['variant_id' => $this->variantL->id, 'quantity' => 999],
            ],
        ]);

        $response->assertSessionHasErrors('error');
        $this->variantL->refresh();
        $this->assertEquals(20, $this->variantL->current_stock);
    }

    public function test_authenticated_user_can_view_stock_in_page(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.in.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Warehouse/StockIn')
            ->has('products')
            ->has('movements')
            ->has('filters')
        );
    }
}
