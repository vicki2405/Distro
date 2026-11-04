<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_tagline',
        'logo_path',
        'hero_badge',
        'hero_title_prefix',
        'hero_title_highlight',
        'hero_description',
        'live_feed_text',
        'footer_text',
        'whatsapp_number',
    ];

    protected $appends = [
        'logo_url',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'store_name' => 'DISTRO CATALOG',
            'store_tagline' => 'APPAREL & STREETWEAR',
            'hero_badge' => 'Rilisan Resmi Katalog Baju Distro',
            'hero_title_prefix' => 'Eksplorasi Koleksi',
            'hero_title_highlight' => 'Distro & Streetwear',
            'hero_description' => 'Daftar master artikel pakaian distro dengan ketersediaan stok aktual di rak gudang kami.',
            'live_feed_text' => 'LIVE STOCK FEED',
            'footer_text' => '© 2026 Distro Operations • Official Apparel Catalog & Real-Time Logistics Feed',
            'whatsapp_number' => null,
        ]);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path ? Storage::url($this->logo_path) : null,
        );
    }
}
