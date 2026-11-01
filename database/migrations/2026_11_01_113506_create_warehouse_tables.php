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
        // 1. Tambah kolom role ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'staff'])->default('staff')->after('email');
        });

        // 2. Tabel Kategori Baju
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 3. Tabel Artikel / Produk Baju
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // Kode Artikel misal: TS-OVERSIZE-01
            $table->string('name');
            $table->decimal('cost_price', 12, 2)->default(0); // HPP Modal (Khusus Owner)
            $table->decimal('selling_price', 12, 2)->default(0); // Harga Jual
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // 4. Tabel Varian Ukuran & Stok
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 10); // S, M, L, XL, XXL, ALL_SIZE
            $table->string('sku')->unique(); // TS-OVERSIZE-01-L
            $table->integer('current_stock')->default(0);
            $table->timestamps();
        });

        // 5. Tabel Jurnal Mutasi Stok (Buku Besar Abadi)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Pegawai yang input
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reference_number')->nullable(); // No Nota / Surat Jalan
            $table->string('destination')->nullable(); // Toko, Reseller, Online Shop, Reject
            $table->string('proof_image')->nullable(); // Foto surat jalan / resi
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Tabel Laporan Stock Opname
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('system_stock');
            $table->integer('physical_stock');
            $table->integer('difference'); // physical_stock - system_stock
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
