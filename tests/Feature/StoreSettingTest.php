<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup roles & permissions
        $perm = Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $ownerRole->givePermissionTo($perm);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->owner->assignRole('owner');

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->assignRole('staff');

        StoreSetting::current();
    }

    public function test_owner_can_view_store_settings_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('warehouse.settings.index'));

        $response->assertOk();
    }

    public function test_staff_cannot_view_store_settings_page(): void
    {
        $response = $this->actingAs($this->staff)->get(route('warehouse.settings.index'));

        $response->assertForbidden();
    }

    public function test_owner_can_update_store_texts(): void
    {
        $response = $this->actingAs($this->owner)->post(route('warehouse.settings.update'), [
            'store_name' => 'DISTRO PREMIUM INDONESIA',
            'store_tagline' => 'ORIGINAL STREETWEAR & CULTURE',
            'hero_badge' => 'PROMO SPESIAL AKHIR TAHUN',
            'hero_title_prefix' => 'Koleksi Eksklusif',
            'hero_title_highlight' => 'Limited Drop Series',
            'hero_description' => 'Deskripsi baru toko yang diperbarui langsung dari admin.',
            'live_feed_text' => 'READY STOCK REAL-TIME',
            'footer_text' => '© 2026 Distro Premium Indonesia',
            'whatsapp_number' => '6281234567890',
        ]);

        $response->assertRedirect(route('warehouse.settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('store_settings', [
            'store_name' => 'DISTRO PREMIUM INDONESIA',
            'store_tagline' => 'ORIGINAL STREETWEAR & CULTURE',
            'hero_badge' => 'PROMO SPESIAL AKHIR TAHUN',
            'hero_title_prefix' => 'Koleksi Eksklusif',
            'hero_title_highlight' => 'Limited Drop Series',
            'whatsapp_number' => '6281234567890',
        ]);
    }

    public function test_owner_can_upload_and_remove_store_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('brand-logo.png', 300, 300);

        $response = $this->actingAs($this->owner)->post(route('warehouse.settings.update'), [
            'store_name' => 'DISTRO LOGO TEST',
            'hero_title_prefix' => 'Koleksi',
            'hero_title_highlight' => 'Streetwear',
            'logo' => $file,
        ]);

        $response->assertRedirect(route('warehouse.settings.index'));

        $setting = StoreSetting::current();
        $this->assertNotNull($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);

        // Test removing logo
        $removeResponse = $this->actingAs($this->owner)->post(route('warehouse.settings.update'), [
            'store_name' => 'DISTRO LOGO TEST',
            'hero_title_prefix' => 'Koleksi',
            'hero_title_highlight' => 'Streetwear',
            'remove_logo' => true,
        ]);

        $removeResponse->assertRedirect(route('warehouse.settings.index'));

        $setting->refresh();
        $this->assertNull($setting->logo_path);
    }

    public function test_welcome_page_displays_custom_store_setting(): void
    {
        StoreSetting::current()->update([
            'store_name' => 'AWESOME URBAN WEAR',
            'hero_badge' => 'NEW DROP ARRIVAL',
        ]);

        $response = $this->get(route('welcome'));

        $response->assertOk();
        $response->assertSee('AWESOME URBAN WEAR');
    }
}
