<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockReportExportService
{
    /**
     * Parse periode dan ambil koleksi mutasi stok sesuai tipe ('in' | 'out').
     *
     * @return array{movements: Collection, periodTitle: string, filenameBase: string, startDate: Carbon, endDate: Carbon}
     */
    public function getFilteredMovements(Request $request, string $type): array
    {
        $period = (string) $request->input('period', 'monthly');
        $now = Carbon::now();

        switch ($period) {
            case 'daily':
                $dateInput = $request->input('date');
                $targetDate = $dateInput ? Carbon::parse($dateInput) : $now->copy();
                $startDate = $targetDate->copy()->startOfDay();
                $endDate = $targetDate->copy()->endOfDay();
                $periodTitle = 'Harian ('.$targetDate->translatedFormat('d F Y').')';
                $filenameSuffix = 'Harian-'.$targetDate->format('Y-m-d');
                break;

            case 'weekly':
                // 7 hari terakhir s/d hari ini
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodTitle = 'Mingguan ('.$startDate->translatedFormat('d M Y').' - '.$endDate->translatedFormat('d M Y').')';
                $filenameSuffix = 'Mingguan-'.$startDate->format('Ymd').'-'.$endDate->format('Ymd');
                break;

            case 'custom':
                $startInput = $request->input('start_date');
                $endInput = $request->input('end_date');
                $startDate = $startInput ? Carbon::parse($startInput)->startOfDay() : $now->copy()->startOfMonth();
                $endDate = $endInput ? Carbon::parse($endInput)->endOfDay() : $now->copy()->endOfDay();

                if ($startDate->gt($endDate)) {
                    [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
                }

                $periodTitle = 'Periode ('.$startDate->translatedFormat('d M Y').' - '.$endDate->translatedFormat('d M Y').')';
                $filenameSuffix = 'Kustom-'.$startDate->format('Ymd').'-'.$endDate->format('Ymd');
                break;

            case 'monthly':
            default:
                $month = (int) $request->input('month', $now->month);
                $year = (int) $request->input('year', $now->year);
                $targetMonth = Carbon::createFromDate($year, $month, 1);
                $startDate = $targetMonth->copy()->startOfMonth();
                $endDate = $targetMonth->copy()->endOfMonth();
                $periodTitle = 'Bulanan ('.$targetMonth->translatedFormat('F Y').')';
                $filenameSuffix = 'Bulanan-'.$targetMonth->format('Y-m');
                break;
        }

        $typeLabel = $type === 'in' ? 'Barang-Masuk' : 'Barang-Keluar';
        $filenameBase = "Laporan-{$typeLabel}-{$filenameSuffix}";

        $movements = StockMovement::with(['variant.product.category', 'user'])
            ->where('type', $type)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'movements' => $movements,
            'periodTitle' => $periodTitle,
            'filenameBase' => $filenameBase,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Generate & Download PDF Dokumen Laporan Stok.
     */
    public function exportPdf(Collection $movements, string $type, string $periodTitle, string $filenameBase): Response
    {
        $storeSetting = StoreSetting::current();
        $typeTitle = $type === 'in' ? 'LAPORAN PENERIMAAN BARANG MASUK (RESTOCK)' : 'LAPORAN PENGELUARAN BARANG KELUAR (OUTBOUND)';
        $totalQuantity = $movements->sum('quantity');

        $pdf = Pdf::loadView('reports.stock_movement_pdf', [
            'movements' => $movements,
            'type' => $type,
            'typeTitle' => $typeTitle,
            'periodTitle' => $periodTitle,
            'storeSetting' => $storeSetting,
            'totalQuantity' => $totalQuantity,
            'printedAt' => Carbon::now()->translatedFormat('d F Y, H:i').' WIB',
            'printedBy' => auth()->user()?->name ?? 'Petugas Gudang',
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("{$filenameBase}.pdf");
    }

    /**
     * Generate & Download Excel (.xlsx) Dokumen Laporan Stok.
     */
    public function exportExcel(Collection $movements, string $type, string $periodTitle, string $filenameBase): StreamedResponse
    {
        $storeSetting = StoreSetting::current();
        $storeName = $storeSetting->store_name ?? 'Distro Ops Warehouse';
        $typeTitle = $type === 'in' ? 'Laporan Penerimaan Barang Masuk (Restock)' : 'Laporan Pengeluaran Barang Keluar (Outbound)';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($type === 'in' ? 'Barang Masuk' : 'Barang Keluar');

        // 1. HEADER DOKUMEN TOKO
        $sheet->setCellValue('A1', strtoupper($storeName));
        $sheet->setCellValue('A2', strtoupper($typeTitle));
        $sheet->setCellValue('A3', 'Periode: '.$periodTitle.' | Dicetak: '.Carbon::now()->translatedFormat('d M Y, H:i').' WIB');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0284C7');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('64748B');

        // 2. HEADER TABEL
        $partyColumn = $type === 'in' ? 'Asal / Supplier Konveksi' : 'Tujuan / Penerima';
        $headers = [
            'No',
            'Tanggal & Waktu',
            'No. Referensi / Surat Jalan',
            'Kode & Nama Artikel Produk',
            'Kategori',
            'Ukuran (Size)',
            'Qty (Pcs)',
            $partyColumn,
            'Operator Gudang',
            'Catatan',
        ];

        $headerRow = 5;
        $sheet->fromArray($headers, null, 'A'.$headerRow);

        // Style Table Header (Dark Indigo/Slate with White Bold Text)
        $headerRange = 'A'.$headerRow.':J'.$headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        // 3. ROWS DATA
        $currentRow = 6;
        $no = 1;

        foreach ($movements as $m) {
            $productName = $m->variant?->product?->name ?? 'Produk Dihapus';
            $productCode = $m->variant?->product?->code ?? '-';
            $categoryName = $m->variant?->product?->category?->name ?? '-';
            $size = $m->variant?->size ?? '-';
            $userName = $m->user?->name ?? 'Sistem';

            $sheet->setCellValue('A'.$currentRow, $no++);
            $sheet->setCellValue('B'.$currentRow, $m->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('C'.$currentRow, $m->reference_number ?: '-');
            $sheet->setCellValue('D'.$currentRow, "[{$productCode}] {$productName}");
            $sheet->setCellValue('E'.$currentRow, $categoryName);
            $sheet->setCellValue('F'.$currentRow, $size);
            $sheet->setCellValue('G'.$currentRow, (int) $m->quantity);
            $sheet->setCellValue('H'.$currentRow, $m->destination ?: '-');
            $sheet->setCellValue('I'.$currentRow, $userName);
            $sheet->setCellValue('J'.$currentRow, $m->notes ?: '-');

            // Baris zebra striping
            if ($currentRow % 2 === 0) {
                $sheet->getStyle('A'.$currentRow.':J'.$currentRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getRowDimension($currentRow)->setRowHeight(20);
            $currentRow++;
        }

        // Baris jika tidak ada data
        if ($movements->isEmpty()) {
            $sheet->mergeCells('A'.$currentRow.':J'.$currentRow);
            $sheet->setCellValue('A'.$currentRow, 'Tidak ada transaksi barang pada periode ini.');
            $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$currentRow)->getFont()->setItalic(true)->getColor()->setRGB('94A3B8');
            $currentRow++;
        }

        // 4. TOTAL REKAPITULASI ROW
        $totalRow = $currentRow;
        $sheet->setCellValue('A'.$totalRow, 'TOTAL KESELURUHAN KUANTITI');
        $sheet->mergeCells('A'.$totalRow.':F'.$totalRow);

        if ($movements->isNotEmpty()) {
            $sheet->setCellValue('G'.$totalRow, '=SUM(G6:G'.($totalRow - 1).')');
        } else {
            $sheet->setCellValue('G'.$totalRow, 0);
        }

        $sheet->setCellValue('H'.$totalRow, 'Pcs');
        $sheet->mergeCells('H'.$totalRow.':J'.$totalRow);

        $sheet->getStyle('A'.$totalRow.':J'.$totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('A'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($totalRow)->setRowHeight(24);

        // 5. STYLING BORDER & ALIGNMENT
        $tableRange = 'A5:J'.$totalRow;
        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // Alignment kolom spesifik
        $dataEnd = $totalRow - 1;
        if ($dataEnd >= 6) {
            $sheet->getStyle('A6:A'.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B6:B'.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C6:C'.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E6:E'.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F6:F'.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G6:G'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G6:G'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Auto-size kolom A s/d J
        foreach (range('A', 'J') as $colID) {
            $sheet->getColumnDimension($colID)->setAutoSize(true);
        }

        // Return Streamed Response untuk download .xlsx
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "{$filenameBase}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
