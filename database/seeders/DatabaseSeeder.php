<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Buat Master Permissions Terstruktur
        $permissions = [
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'categories.view',
            'categories.manage',
            'sizes.view',
            'sizes.manage',
            'stock.view',
            'stock.in',
            'stock.out',
            'stock.opname',
            'stock.report',
            'users.manage',
            'roles.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        StoreSetting::current();

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $ownerRole->syncPermissions(Permission::all());
        $staffRole->syncPermissions([
            'products.view',
            'categories.view',
            'stock.view',
            'stock.in',
            'stock.out',
            'stock.report',
        ]);

        // 1. Akun Pengguna (Owner & Pegawai Gudang)
        $owner = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Pemilik Distro (Owner)',
                'role' => 'owner',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $owner->assignRole($ownerRole);

        $staff = User::firstOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'name' => 'Budi Pegawai Gudang',
                'role' => 'staff',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $staff->assignRole($staffRole);

        // 2. Kategori Produk Distro Lengkap
        $categoriesData = [
            'Kaos T-Shirt' => 'kaos-t-shirt',
            'Hoodie & Crewneck' => 'hoodie-crewneck',
            'Kemeja' => 'kemeja',
            'Celana' => 'celana',
            'Jaket & Outerwear' => 'jaket-outerwear',
            'Aksesoris' => 'aksesoris',
        ];

        $categories = [];
        foreach ($categoriesData as $name => $slug) {
            $categories[$name] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }

        // Master Ukuran (Sizes) Standar Distro
        $defaultSizes = [
            'XS' => 1,
            'S' => 2,
            'M' => 3,
            'L' => 4,
            'XL' => 5,
            'XXL' => 6,
            '3XL' => 7,
            '4XL' => 8,
            'ALL_SIZE' => 9,
        ];
        foreach ($defaultSizes as $szName => $szOrder) {
            Size::firstOrCreate(['name' => $szName], ['order' => $szOrder]);
        }

        // 3. 10 Data Artikel Baju & Produk Distro Riil
        $products = [
            [
                'category' => 'Kaos T-Shirt',
                'name' => 'Kaos Oversize "Black Metal Vintage Wash"',
                'cost_price' => 60000,
                'selling_price' => 135000,
                'description' => 'Heavyweight cotton 24s sablon plastisol vintage wash.',
                'variants' => ['S' => 15, 'M' => 35, 'L' => 45, 'XL' => 25, 'XXL' => 10],
            ],
            [
                'category' => 'Kaos T-Shirt',
                'name' => 'Kaos Boxy Fit "Raw Aesthetic White"',
                'cost_price' => 55000,
                'selling_price' => 125000,
                'description' => 'Combed 30s premium lembut, fitting boxy modern.',
                'variants' => ['S' => 20, 'M' => 40, 'L' => 30, 'XL' => 18, 'XXL' => 8],
            ],
            [
                'category' => 'Hoodie & Crewneck',
                'name' => 'Hoodie Boxy "Onyx Heavy Fleece 330gsm"',
                'cost_price' => 135000,
                'selling_price' => 295000,
                'description' => 'Fleece cotton 330 gsm tebal, saku kanguru ganda.',
                'variants' => ['M' => 20, 'L' => 30, 'XL' => 15, 'XXL' => 5],
            ],
            [
                'category' => 'Hoodie & Crewneck',
                'name' => 'Crewneck Graphic "Tokyo Underground"',
                'cost_price' => 110000,
                'selling_price' => 245000,
                'description' => 'Cotton fleece 280 gsm bordir hand-stitched.',
                'variants' => ['S' => 12, 'M' => 25, 'L' => 25, 'XL' => 10],
            ],
            [
                'category' => 'Kemeja',
                'name' => 'Kemeja Flanel Tartan "Seattle Grunge"',
                'cost_price' => 95000,
                'selling_price' => 210000,
                'description' => 'Wool-blend flannel lembut motif kotak merah hitam.',
                'variants' => ['S' => 10, 'M' => 20, 'L' => 25, 'XL' => 15],
            ],
            [
                'category' => 'Kemeja',
                'name' => 'Workshirt Twill "Mechanic Streetwear"',
                'cost_price' => 105000,
                'selling_price' => 225000,
                'description' => 'American drill premium dengan patch bordir dada.',
                'variants' => ['M' => 18, 'L' => 22, 'XL' => 14],
            ],
            [
                'category' => 'Celana',
                'name' => 'Celana Cargo Multi-Pocket "Tactical Street"',
                'cost_price' => 125000,
                'selling_price' => 265000,
                'description' => 'Ripstop cotton durable 6 kantong, strap adjuster bawah.',
                'variants' => ['S' => 8, 'M' => 18, 'L' => 20, 'XL' => 12],
            ],
            [
                'category' => 'Celana',
                'name' => 'Celana Denim Baggy "Vintage Blue 14oz"',
                'cost_price' => 140000,
                'selling_price' => 299000,
                'description' => 'Non-stretch 14oz raw denim washing natural whisker.',
                'variants' => ['S' => 10, 'M' => 20, 'L' => 20, 'XL' => 10],
            ],
            [
                'category' => 'Jaket & Outerwear',
                'name' => 'Jaket Varsity Wool "Champions Edition"',
                'cost_price' => 180000,
                'selling_price' => 385000,
                'description' => 'Melton wool dengan lengan oscar faux-leather dan patch chenille.',
                'variants' => ['M' => 12, 'L' => 15, 'XL' => 8],
            ],
            [
                'category' => 'Aksesoris',
                'name' => 'Topi Trucker Cap "Distro Authentic"',
                'cost_price' => 35000,
                'selling_price' => 85000,
                'description' => 'Polyester foam jaring breathable dengan sablon timbul.',
                'variants' => ['ALL' => 35],
            ],
        ];

        foreach ($products as $pData) {
            $cat = $categories[$pData['category']];

            // Generate kode internal unik
            do {
                $code = 'PRD-'.strtoupper(Str::random(6));
            } while (Product::where('code', $code)->exists());

            $product = Product::firstOrCreate(
                ['name' => $pData['name']],
                [
                    'category_id' => $cat->id,
                    'code' => $code,
                    'cost_price' => $pData['cost_price'],
                    'selling_price' => $pData['selling_price'],
                    'description' => $pData['description'],
                ]
            );

            foreach ($pData['variants'] as $size => $stock) {
                $sku = $product->code.'-'.$size;
                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'size' => $size,
                    ],
                    [
                        'sku' => $sku,
                        'current_stock' => $stock,
                    ]
                );

                // Catat mutasi awal jika belum ada
                if ($variant->stockMovements()->count() === 0 && $stock > 0) {
                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'user_id' => $staff->id,
                        'type' => 'in',
                        'quantity' => $stock,
                        'stock_before' => 0,
                        'stock_after' => $stock,
                        'reference_number' => 'SJ-RESTOCK-'.rand(1000, 9999),
                        'destination' => 'Penerimaan Konveksi Mitra Bandung',
                        'notes' => 'Stok awal masuk gudang.',
                        'created_at' => now()->subHours(rand(1, 24)),
                    ]);
                }
            }
        }

        // Buat beberapa mutasi keluar (Outbound) sebagai contoh riil pengiriman hari ini
        $outVariants = ProductVariant::where('current_stock', '>', 5)->take(3)->get();
        foreach ($outVariants as $ov) {
            $qtyOut = rand(2, 4);
            $before = $ov->current_stock;
            $after = $before - $qtyOut;
            $ov->update(['current_stock' => $after]);

            StockMovement::create([
                'product_variant_id' => $ov->id,
                'user_id' => $staff->id,
                'type' => 'out',
                'quantity' => $qtyOut,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_number' => 'DO-TIKTOK-'.rand(1000, 9999),
                'destination' => 'Pesanan Online (Shopee/TikTok)',
                'notes' => 'Paket polymailer diserahkan ke kurir J&T.',
                'created_at' => now()->subMinutes(rand(10, 180)),
            ]);
        }
    }
}
