<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Permissions
        $reportPerm = Permission::firstOrCreate(['name' => 'stock.report', 'guard_name' => 'web']);
        $inPerm = Permission::firstOrCreate(['name' => 'stock.in', 'guard_name' => 'web']);
        $outPerm = Permission::firstOrCreate(['name' => 'stock.out', 'guard_name' => 'web']);

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $ownerRole->syncPermissions(Permission::all());
        $staffRole->syncPermissions([$inPerm, $outPerm, $reportPerm]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'email' => 'owner@distro.test',
        ]);
        $this->owner->assignRole($ownerRole);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@distro.test',
        ]);
        $this->staff->assignRole($staffRole);

        // 2. Setup Dummy Stock Movements
        $category = Category::create(['name' => 'Kaos T-Shirt', 'slug' => 'kaos-t-shirt']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kaos Oversize Vintage',
            'code' => 'PRD-TEST01',
            'cost_price' => 50000,
            'selling_price' => 120000,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PRD-TEST01-L',
            'size' => 'L',
            'current_stock' => 50,
        ]);

        StockMovement::create([
            'product_variant_id' => $variant->id,
            'user_id' => $this->staff->id,
            'type' => 'in',
            'quantity' => 50,
            'stock_before' => 0,
            'stock_after' => 50,
            'reference_number' => 'SJ-IN-001',
            'destination' => 'Konveksi Bandung',
            'created_at' => now(),
        ]);

        StockMovement::create([
            'product_variant_id' => $variant->id,
            'user_id' => $this->staff->id,
            'type' => 'out',
            'quantity' => 10,
            'stock_before' => 50,
            'stock_after' => 40,
            'reference_number' => 'SJ-OUT-001',
            'destination' => 'Cabang Tebet Jakarta',
            'created_at' => now(),
        ]);
    }

    public function test_owner_can_export_stock_in_report_as_pdf(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('warehouse.in.export', [
                'format' => 'pdf',
                'period' => 'monthly',
                'month' => now()->month,
                'year' => now()->year,
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan-Barang-Masuk', $response->headers->get('Content-Disposition'));
    }

    public function test_owner_can_export_stock_in_report_as_excel(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('warehouse.in.export', [
                'format' => 'excel',
                'period' => 'weekly',
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function test_owner_can_export_stock_out_report_as_pdf_daily(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('warehouse.out.export', [
                'format' => 'pdf',
                'period' => 'daily',
                'date' => now()->toDateString(),
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan-Barang-Keluar', $response->headers->get('Content-Disposition'));
    }

    public function test_owner_can_export_stock_out_report_as_excel_custom(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('warehouse.out.export', [
                'format' => 'excel',
                'period' => 'custom',
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function test_staff_with_permission_can_export_reports(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('warehouse.in.export', ['format' => 'pdf', 'period' => 'monthly']));

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_export_reports(): void
    {
        $limitedUser = User::factory()->create([
            'role' => 'staff',
            'email' => 'limited@distro.test',
        ]);
        // Do not give stock.report permission
        $customRole = Role::create(['name' => 'limited_staff', 'guard_name' => 'web']);
        $limitedUser->assignRole($customRole);

        $response = $this->actingAs($limitedUser)
            ->get(route('warehouse.in.export', ['format' => 'pdf']));

        $response->assertStatus(403);
    }
}
