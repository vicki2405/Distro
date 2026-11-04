<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStoreSettingRequest;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user || (! $user->isOwner() && ! $user->can('settings.manage'))) {
            abort(403, 'Akses terbatas untuk Administrator Toko (Owner).');
        }

        $setting = StoreSetting::current();

        return Inertia::render('Warehouse/Settings/Index', [
            'setting' => $setting,
        ]);
    }

    public function update(UpdateStoreSettingRequest $request): RedirectResponse
    {
        $setting = StoreSetting::current();
        $validated = $request->validated();

        if ($request->boolean('remove_logo')) {
            if ($setting->logo_path && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $setting->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($setting->logo_path && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $path = $request->file('logo')->store('branding', 'public');
            $setting->logo_path = $path;
        }

        $setting->update([
            'store_name' => $validated['store_name'],
            'store_tagline' => $validated['store_tagline'] ?? null,
            'hero_badge' => $validated['hero_badge'] ?? null,
            'hero_title_prefix' => $validated['hero_title_prefix'],
            'hero_title_highlight' => $validated['hero_title_highlight'],
            'hero_description' => $validated['hero_description'] ?? null,
            'live_feed_text' => $validated['live_feed_text'] ?? null,
            'footer_text' => $validated['footer_text'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
        ]);

        return redirect()->route('warehouse.settings.index')
            ->with('success', 'Pengaturan tampilan & branding toko berhasil diperbarui.');
    }
}
