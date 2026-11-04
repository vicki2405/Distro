<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('DISTRO CATALOG');
            $table->string('store_tagline')->nullable()->default('APPAREL & STREETWEAR');
            $table->string('logo_path')->nullable();
            $table->string('hero_badge')->nullable()->default('Rilisan Resmi Katalog Baju Distro');
            $table->string('hero_title_prefix')->default('Eksplorasi Koleksi');
            $table->string('hero_title_highlight')->default('Distro & Streetwear');
            $table->text('hero_description')->nullable();
            $table->string('live_feed_text')->nullable()->default('LIVE STOCK FEED');
            $table->string('footer_text')->nullable()->default('© 2026 Distro Operations • Official Apparel Catalog & Real-Time Logistics Feed');
            $table->string('whatsapp_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
