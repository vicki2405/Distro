<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockOpnameTest extends TestCase
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
        $opnamePermission = Permission::firstOrCreate(['name' => 'stock.opname', 'guard_name' => 'web']);

        $staffRole->givePermissionTo($opnamePermission);

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
            'name' => 'Kaos Oversize Vintage',
            'code' => 'KOS-01',
            'cost_price' => 50000,
            'selling_price' => 110000,
            'rack_location' => 'A1-02',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'KOS-01-XL',
            'size' => 'XL',
            'current_stock' => 50,
        ]);
    }

    public function test_staff_can_view_opname_page_with_blind_count_protection(): void
    {
        // Buat data opname yang sudah ada
        StockOpname::create([
            'user_id' => $this->staff->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 45,
            'difference' => -5,
            'notes' => 'Pemeriksaan fisik rak A1-02',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('warehouse.opnames.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Warehouse/StockOpname/Index')
            ->where('isOwner', false)
            // Blind count: system_stock & difference disembunyikan untuk staf
            ->where('opnames.data.0.system_stock', null)
            ->where('opnames.data.0.difference', null)
            ->where('opnames.data.0.physical_stock', 45)
        );
    }

    public function test_staff_can_record_physical_stock_opname_without_altering_stock_immediately(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('warehouse.opnames.store'), [
                'items' => [
                    [
                        'product_variant_id' => $this->variant->id,
                        'physical_stock' => 48,
                        'notes' => 'Hitung fisik langsung di rak',
                    ],
                ],
            ]);

        $response->assertRedirect(route('warehouse.opnames.index'));
        $response->assertSessionHas('success');

        // Pastikan record stock opname tersimpan dengan perhitungan selisih otomatis
        $this->assertDatabaseHas('stock_opnames', [
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 48,
            'difference' => -2,
            'notes' => 'Hitung fisik langsung di rak',
        ]);

        // Stok produk varian di sistem belum berubah sebelum disetujui owner
        $this->assertEquals(50, $this->variant->fresh()->current_stock);
    }

    public function test_staff_cannot_adjust_stock_opname(): void
    {
        $opname = StockOpname::create([
            'user_id' => $this->staff->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 48,
            'difference' => -2,
        ]);

        $response = $this->actingAs($this->staff)
            ->post(route('warehouse.opnames.adjust', $opname->id));

        $response->assertStatus(403);
        $this->assertEquals(50, $this->variant->fresh()->current_stock);
    }

    public function test_owner_can_adjust_stock_opname_and_sync_inventory(): void
    {
        $opname = StockOpname::create([
            'user_id' => $this->staff->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 48,
            'difference' => -2,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('warehouse.opnames.adjust', $opname->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Stok varian sekarang tersinkron menjadi 48
        $this->assertEquals(48, $this->variant->fresh()->current_stock);

        // Mutasi penyesuaian (adjustment) otomatis tercatat
        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $this->variant->id,
            'type' => 'adjustment',
            'quantity' => 2,
            'stock_before' => 50,
            'stock_after' => 48,
        ]);
    }

    public function test_staff_cannot_delete_stock_opname(): void
    {
        $opname = StockOpname::create([
            'user_id' => $this->staff->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 48,
            'difference' => -2,
        ]);

        $response = $this->actingAs($this->staff)
            ->delete(route('warehouse.opnames.destroy', $opname->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('stock_opnames', ['id' => $opname->id]);
    }

    public function test_owner_can_delete_stock_opname(): void
    {
        $opname = StockOpname::create([
            'user_id' => $this->staff->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 48,
            'difference' => -2,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('warehouse.opnames.destroy', $opname->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('stock_opnames', ['id' => $opname->id]);
    }

    public function test_owner_can_export_stock_opname_bast_as_pdf_and_excel(): void
    {
        StockOpname::create([
            'user_id' => $this->owner->id,
            'product_variant_id' => $this->variant->id,
            'system_stock' => 50,
            'physical_stock' => 50,
            'difference' => 0,
        ]);

        // PDF Export
        $pdfResponse = $this->actingAs($this->owner)
            ->get(route('warehouse.opnames.export', ['format' => 'pdf']));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // Excel Export
        $excelResponse = $this->actingAs($this->owner)
            ->get(route('warehouse.opnames.export', ['format' => 'excel']));
        $excelResponse->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $excelResponse->headers->get('Content-Type'));
    }

    public function test_user_can_view_sheet_page_and_download_blank_stock_count_sheet_pdf(): void
    {
        $viewResponse = $this->actingAs($this->owner)
            ->get(route('warehouse.opnames.sheet'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertInertia(fn (Assert $page) => $page->component('Warehouse/StockOpname/Sheet'));

        $downloadResponse = $this->actingAs($this->owner)
            ->get(route('warehouse.opnames.sheet.download', ['rack' => 'A1-02']));
        $downloadResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $downloadResponse->headers->get('Content-Type'));
    }

    public function test_user_can_view_batch_create_page(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('warehouse.opnames.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Warehouse/StockOpname/Create'));
    }
}
