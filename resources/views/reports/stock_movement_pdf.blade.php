<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $typeTitle }}</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
            size: A4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.35;
        }

        /* HEADER & KOP TOKO */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .store-name {
            font-size: 16pt;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .store-sub {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        .report-badge {
            text-align: right;
            vertical-align: top;
        }

        .badge-type {
            display: inline-block;
            padding: 4px 10px;
            font-size: 8.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            {{ $type === 'in' ? 'background-color: #d1fae5; color: #065f46; border: 1px solid #10b981;' : 'background-color: #fef3c7; color: #92400e; border: 1px solid #f59e0b;' }}
        }

        /* METADATA PERIODE */
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
        }

        .meta-label {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .meta-value {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
        }

        /* DATA TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 6px;
            border: 1px solid #0f172a;
            text-align: left;
        }

        .data-table td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            font-size: 8pt;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: Courier, monospace; }

        .total-row td {
            background-color: #e2e8f0 !important;
            font-weight: bold;
            border-top: 2px solid #64748b;
            font-size: 8.5pt;
        }

        /* SIGNATURE SECTION */
        .signature-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 35%;
        }

        .signature-space {
            height: 55px;
        }

        .signature-line {
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            width: 80%;
            font-weight: bold;
            color: #0f172a;
            padding-bottom: 2px;
        }

        .signature-title {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
        }

        /* FOOTER NOTE */
        .footer-note {
            margin-top: 18px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI LAPORAN -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="store-name">{{ $storeSetting->store_name ?? 'DISTRO OPERATIONS' }}</div>
                <div class="store-sub">
                    Gudang & Logistik | {{ $storeSetting->address ?? 'Pusat Distribusi & Manajemen Stok Apparel' }}
                </div>
                @if(!empty($storeSetting->phone))
                    <div class="store-sub">Kontak / Telp: {{ $storeSetting->phone }}</div>
                @endif
            </td>
            <td class="report-badge" style="width: 40%;">
                <div class="badge-type">
                    {{ $type === 'in' ? '📥 RESTOCK BARANG MASUK' : '📤 OUTBOUND BARANG KELUAR' }}
                </div>
                <div style="font-size: 11pt; font-weight: 800; color: #0f172a; margin-top: 6px;">
                    {{ $type === 'in' ? 'Laporan Penerimaan Stok' : 'Laporan Pengeluaran Stok' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- METADATA PERIODE & PENCETAKAN -->
    <table class="meta-table">
        <tr>
            <td style="width: 38%;">
                <div class="meta-label">Periode Laporan</div>
                <div class="meta-value">{{ $periodTitle }}</div>
            </td>
            <td style="width: 32%;">
                <div class="meta-label">Tanggal Cetak</div>
                <div class="meta-value">{{ $printedAt }}</div>
            </td>
            <td style="width: 30%;">
                <div class="meta-label">Dicetak Oleh</div>
                <div class="meta-value">{{ $printedBy }}</div>
            </td>
        </tr>
    </table>

    <!-- TABEL DATA MUTASI BARANG -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 24px;" class="text-center">No</th>
                <th style="width: 68px;" class="text-center">Tanggal</th>
                <th style="width: 90px;">No. Referensi / SJ</th>
                <th>Artikel Produk</th>
                <th style="width: 42px;" class="text-center">Size</th>
                <th style="width: 45px;" class="text-right">Qty</th>
                <th style="width: 115px;">{{ $type === 'in' ? 'Asal Konveksi / Supplier' : 'Tujuan / Penerima' }}</th>
                <th style="width: 80px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $index => $m)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="text-center font-mono" style="font-size: 7.5pt;">
                        {{ $m->created_at->format('d/m/y H:i') }}
                    </td>
                    <td class="font-mono font-bold" style="font-size: 7.5pt;">
                        {{ $m->reference_number ?: '-' }}
                    </td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">
                            {{ $m->variant?->product?->name ?? 'Produk Dihapus' }}
                        </div>
                        <div style="font-size: 7pt; color: #64748b;">
                            SKU: {{ $m->variant?->sku ?? '-' }} &bull; {{ $m->variant?->product?->category?->name ?? '-' }}
                        </div>
                    </td>
                    <td class="text-center font-bold font-mono">
                        {{ $m->variant?->size ?? '-' }}
                    </td>
                    <td class="text-right font-bold font-mono" style="color: {{ $type === 'in' ? '#047857' : '#b45309' }};">
                        {{ number_format($m->quantity) }}
                    </td>
                    <td style="font-size: 7.5pt;">
                        {{ $m->destination ?: '-' }}
                    </td>
                    <td style="font-size: 7.5pt;">
                        {{ $m->user?->name ?? 'Sistem' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 18px; color: #94a3b8; font-style: italic;">
                        Tidak ada transaksi pergerakan barang pada periode laporan ini.
                    </td>
                </tr>
            @endforelse

            <!-- BARIS REKAPITULASI TOTAL -->
            <tr class="total-row">
                <td colspan="5" class="text-right uppercase">
                    TOTAL KESELURUHAN KUANTITI:
                </td>
                <td class="text-right font-mono" style="font-size: 9.5pt; color: #0f172a;">
                    {{ number_format($totalQuantity) }}
                </td>
                <td colspan="2" style="font-size: 8pt; color: #475569;">
                    Pcs ({{ $movements->count() }} transaksi)
                </td>
            </tr>
        </tbody>
    </table>

    <!-- KOLOM TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td class="signature-box" style="text-align: left;">
                <div class="signature-title">Dicatat & Diverifikasi Oleh:</div>
                <div class="signature-space"></div>
                <div class="signature-line">{{ $printedBy }}</div>
                <div class="signature-title">Petugas / Operator Gudang</div>
            </td>
            <td style="width: 30%;"></td>
            <td class="signature-box" style="text-align: right;">
                <div class="signature-title">Mengetahui & Menyetujui:</div>
                <div class="signature-space"></div>
                <div class="signature-line">( ............................................ )</div>
                <div class="signature-title">Pemilik Toko / Supervisor Gudang</div>
            </td>
        </tr>
    </table>

    <!-- CATATAN KAKI DOKUMEN -->
    <div class="footer-note">
        Dokumen ini diterbitkan secara otomatis oleh Sistem Distro Operations. Bukti fisik surat jalan dan mutasi stok diarsipkan di database gudang.
    </div>

</body>
</html>

