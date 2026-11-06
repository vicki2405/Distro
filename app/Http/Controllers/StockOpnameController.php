<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StoreSetting;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockOpnameController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Halaman 1: Hasil Pencocokan Sistem & Rekonsiliasi SO.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $isOwner = $user->isOwner();

        if (! $isOwner && ! $user->can('stock.opname')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Stock Opname.');
        }

        $query = StockOpname::with(['variant.product.category', 'variant.product.images', 'user'])
            ->latest();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('variant', function ($sub) use ($search) {
                        $sub->where('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($p) use ($search) {
                                $p->where('name', 'like', "%{$search}%")
                                    ->orWhere('rack_location', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($request->filled('rack')) {
            $rack = (string) $request->input('rack');
            $query->whereHas('variant.product', fn ($p) => $p->where('rack_location', $rack));
        }

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'matched') {
                $query->where('difference', 0);
            } elseif ($status === 'minus') {
                $query->where('difference', '<', 0);
            } elseif ($status === 'plus') {
                $query->where('difference', '>', 0);
            }
        }

        $stats = [
            'total_audits' => StockOpname::count(),
            'matched_count' => StockOpname::where('difference', 0)->count(),
            'minus_count' => StockOpname::where('difference', '<', 0)->count(),
            'plus_count' => StockOpname::where('difference', '>', 0)->count(),
            'total_discrepancy_value' => 0,
        ];

        if ($isOwner) {
            $discrepancies = StockOpname::with('variant.product')
                ->where('difference', '!=', 0)
                ->get();

            $stats['total_discrepancy_value'] = (int) $discrepancies->sum(function ($item) {
                $costPrice = $item->variant?->product?->cost_price ?? 0;

                return $item->difference * $costPrice;
            });
        }

        $opnames = $query->paginate(15)->withQueryString();

        $adjustedRefs = StockMovement::where('type', 'adjustment')
            ->where('reference_number', 'like', 'OPNAME-ADJ-%')
            ->pluck('reference_number')
            ->flip();

        $opnames->getCollection()->transform(function ($item) use ($isOwner, $adjustedRefs) {
            $item->is_adjusted = isset($adjustedRefs['OPNAME-ADJ-'.$item->id]);

            if (! $isOwner) {
                $item->system_stock = null;
                $item->difference = null;
            }

            return $item;
        });

        $racks = Product::whereNotNull('rack_location')
            ->where('rack_location', '!=', '')
            ->distinct()
            ->orderBy('rack_location')
            ->pluck('rack_location');

        return Inertia::render('Warehouse/StockOpname/Index', [
            'opnames' => $opnames,
            'racks' => $racks,
            'stats' => $stats,
            'filters' => $request->only(['search', 'rack', 'status']),
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Halaman 2: Cetak Berkas SO (Lembar Hitung Fisik Lapangan).
     */
    public function sheetView(Request $request): InertiaResponse
    {
        $user = $request->user();
        if (! $user->isOwner() && ! $user->can('stock.opname')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak berkas Stock Opname.');
        }

        $racks = Product::whereNotNull('rack_location')
            ->where('rack_location', '!=', '')
            ->distinct()
            ->orderBy('rack_location')
            ->pluck('rack_location');

        $categories = Category::orderBy('name')->get();

        // Preview varian yang akan dicetak
        $query = ProductVariant::with(['product.category'])
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('product_variants.*')
            ->orderBy('products.rack_location')
            ->orderBy('products.name')
            ->orderBy('product_variants.id');

        if ($request->filled('rack')) {
            $rack = (string) $request->input('rack');
            $query->where('products.rack_location', $rack);
        }

        if ($request->filled('category_id')) {
            $catId = (int) $request->input('category_id');
            $query->where('products.category_id', $catId);
        }

        $previewVariants = $query->take(50)->get();
        $totalVariants = $query->count();

        return Inertia::render('Warehouse/StockOpname/Sheet', [
            'racks' => $racks,
            'categories' => $categories,
            'previewVariants' => $previewVariants,
            'totalVariants' => $totalVariants,
            'filters' => $request->only(['rack', 'category_id']),
        ]);
    }

    /**
     * Unduh PDF Berkas Lembar Kerja Hitung Fisik Lapangan Kosong (Blank Count Sheet).
     */
    public function sheetDownload(Request $request): Response
    {
        $user = $request->user();
        if (! $user->isOwner() && ! $user->can('stock.opname')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak berkas Stock Opname.');
        }

        $setting = StoreSetting::current();
        $query = ProductVariant::with(['product.category'])
            ->whereHas('product');

        $filterLabels = [];

        if ($request->filled('rack')) {
            $rack = (string) $request->input('rack');
            $query->whereHas('product', fn ($p) => $p->where('rack_location', $rack));
            $filterLabels[] = "Rak {$rack}";
        }

        if ($request->filled('category_id')) {
            $catId = (int) $request->input('category_id');
            $query->whereHas('product', fn ($p) => $p->where('category_id', $catId));
            $category = Category::find($catId);
            if ($category) {
                $filterLabels[] = "Kategori {$category->name}";
            }
        }

        $filterLabel = empty($filterLabels) ? 'Seluruh Rak & Produk Gudang' : implode(' • ', $filterLabels);

        $variants = $query->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('product_variants.*')
            ->orderBy('products.rack_location')
            ->orderBy('products.name')
            ->orderBy('product_variants.id')
            ->get();

        $pdf = Pdf::loadView('reports.stock_opname_sheet_pdf', [
            'store' => $setting,
            'variants' => $variants,
            'filterLabel' => $filterLabel,
            'dateStr' => Carbon::now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'portrait');

        $filename = 'Lembar-Hitung-Fisik-SO-'.Carbon::now()->format('Ymd-His').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Halaman 3: Input Hasil SO Massal dari Kertas Staf Gudang.
     */
    public function create(Request $request): InertiaResponse
    {
        $user = $request->user();
        $isOwner = $user->isOwner();

        if (! $isOwner && ! $user->can('stock.opname')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat Stock Opname.');
        }

        $racks = Product::whereNotNull('rack_location')
            ->where('rack_location', '!=', '')
            ->distinct()
            ->orderBy('rack_location')
            ->pluck('rack_location');

        $categories = Category::orderBy('name')->get();

        // Ambil daftar varian produk untuk lembar input massal
        $query = ProductVariant::with(['product.category', 'product.images'])
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('product_variants.*')
            ->orderBy('products.rack_location')
            ->orderBy('products.name')
            ->orderBy('product_variants.id');

        if ($request->filled('rack')) {
            $rack = (string) $request->input('rack');
            $query->where('products.rack_location', $rack);
        }

        if ($request->filled('category_id')) {
            $catId = (int) $request->input('category_id');
            $query->where('products.category_id', $catId);
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('product_variants.sku', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%");
            });
        }

        $variants = $query->get();

        if (! $isOwner) {
            $variants->each(function ($v) {
                $v->current_stock = null;
            });
        }

        return Inertia::render('Warehouse/StockOpname/Create', [
            'racks' => $racks,
            'categories' => $categories,
            'variants' => $variants,
            'filters' => $request->only(['rack', 'category_id', 'search']),
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Simpan hasil hitung fisik yang telah dicatat di berkas kertas oleh staf.
     * Sistem otomatis mencocokkan fisik dengan stok sistem saat itu dan menghitung selisih.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isOwner() && ! $user->can('stock.opname')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat Stock Opname.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.physical_stock' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            foreach ($validated['items'] as $item) {
                $variant = ProductVariant::findOrFail($item['product_variant_id']);

                $this->stockService->recordOpname(
                    variant: $variant,
                    user: $user,
                    physicalStock: (int) $item['physical_stock'],
                    notes: $item['notes'] ?? null,
                    adjustStock: false
                );
            }

            return redirect()->route('warehouse.opnames.index')
                ->with('success', 'Hasil hitung fisik berhasil dicatat. Sistem telah selesai mencocokkan data fisik dengan stok sistem.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mencatat hasil audit stock opname: '.$e->getMessage());
        }
    }

    /**
     * Eksekusi penyesuaian stok sistem agar sinkron dengan hasil hitungan fisik (Khusus Owner).
     */
    public function adjust(StockOpname $opname, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            abort(403, 'Hanya Owner yang berhak menyetujui penyesuaian stok sistem.');
        }

        try {
            $this->stockService->adjustOpnameStock($opname, $user);

            return redirect()->back()
                ->with('success', 'Stok sistem berhasil diselaraskan dengan hasil hitung fisik.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyelaraskan stok sistem: '.$e->getMessage());
        }
    }

    /**
     * Hapus riwayat audit opname (Khusus Owner).
     */
    public function destroy(StockOpname $opname, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            abort(403, 'Hanya Owner yang berhak menghapus riwayat audit opname.');
        }

        $opname->delete();

        return redirect()->back()
            ->with('success', 'Riwayat audit stock opname berhasil dihapus.');
    }

    /**
     * Export Berita Acara Rekonsiliasi Hasil SO (BAST) ke PDF atau Excel.
     */
    public function export(Request $request): Response|StreamedResponse
    {
        $user = $request->user();
        if (! $user->isOwner() && ! $user->can('stock.opname')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh laporan Stock Opname.');
        }

        $format = (string) $request->input('format', 'pdf');
        $setting = StoreSetting::current();

        $query = StockOpname::with(['variant.product.category', 'user'])->latest();

        if ($request->filled('rack')) {
            $rack = (string) $request->input('rack');
            $query->whereHas('variant.product', fn ($p) => $p->where('rack_location', $rack));
        }

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'matched') {
                $query->where('difference', 0);
            } elseif ($status === 'minus') {
                $query->where('difference', '<', 0);
            } elseif ($status === 'plus') {
                $query->where('difference', '>', 0);
            }
        }

        $opnames = $query->get();
        $dateStr = Carbon::now()->translatedFormat('d F Y');
        $filenameBase = 'Berita-Acara-Rekonsiliasi-SO-'.Carbon::now()->format('Ymd-His');

        if ($format === 'excel') {
            return $this->exportExcel($opnames, $setting, $dateStr, $filenameBase);
        }

        return $this->exportPdf($opnames, $setting, $dateStr, $filenameBase);
    }

    /**
     * Render PDF Berita Acara Rekonsiliasi Hasil Opname (BAST).
     */
    protected function exportPdf($opnames, StoreSetting $setting, string $dateStr, string $filenameBase): Response
    {
        $pdf = Pdf::loadView('reports.stock_opname_pdf', [
            'store' => $setting,
            'opnames' => $opnames,
            'dateStr' => $dateStr,
            'printedAt' => Carbon::now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filenameBase.'.pdf"',
        ]);
    }

    /**
     * Render Excel Berita Acara Rekonsiliasi Hasil Opname.
     */
    protected function exportExcel($opnames, StoreSetting $setting, string $dateStr, string $filenameBase): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekonsiliasi SO');

        $sheet->setCellValue('A1', strtoupper($setting->store_name));
        $sheet->setCellValue('A2', 'BERITA ACARA REKONSILIASI STOCK OPNAME (BAST)');
        $sheet->setCellValue('A3', 'Tanggal Cetak: '.$dateStr);

        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

        $headers = [
            'A5' => 'No',
            'B5' => 'Waktu Audit',
            'C5' => 'Nama Artikel Produk',
            'D5' => 'Lokasi Rak',
            'E5' => 'Ukuran',
            'F5' => 'SKU Barcode',
            'G5' => 'Stok Sistem',
            'H5' => 'Stok Fisik',
            'I5' => 'Selisih',
            'J5' => 'Petugas Auditor',
            'K5' => 'Catatan',
        ];

        foreach ($headers as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A5:K5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(24);

        $row = 6;
        $no = 1;

        foreach ($opnames as $opname) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $opname->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('C'.$row, $opname->variant?->product?->name ?? '-');
            $sheet->setCellValue('D'.$row, $opname->variant?->product?->rack_location ?: 'Tanpa Rak');
            $sheet->setCellValue('E'.$row, $opname->variant?->size ?? '-');
            $sheet->setCellValue('F'.$row, $opname->variant?->sku ?? '-');
            $sheet->setCellValue('G'.$row, $opname->system_stock);
            $sheet->setCellValue('H'.$row, $opname->physical_stock);
            $sheet->setCellValue('I'.$row, $opname->difference);
            $sheet->setCellValue('J'.$row, $opname->user?->name ?? '-');
            $sheet->setCellValue('K'.$row, $opname->notes ?? '-');

            $sheet->getStyle('A'.$row.':K'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.$row.':E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G'.$row.':I'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filenameBase.'.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
