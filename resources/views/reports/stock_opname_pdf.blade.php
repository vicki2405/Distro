<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Stock Opname</title>
    <style>
        @page {
            margin: 25px 30px 30px 30px;
            size: A4 landscape;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.3;
        }

        /* HEADER & KOP TOKO */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .store-name {
            font-size: 15pt;
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
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #0284c7;
        }

        /* METADATA PERIODE */
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
        }

        .meta-label {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .meta-value {
            font-size: 8.5pt;
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
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 5px;
            border: 1px solid #0f172a;
            text-align: left;
        }

        .data-table td {
            padding: 5px 5px;
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

        .badge-rack {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3px;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .status-match {
            color: #059669;
            font-weight: bold;
        }

        .status-minus {
            color: #dc2626;
            font-weight: bold;
        }

        .status-plus {
            color: #2563eb;
            font-weight: bold;
        }

        .total-row td {
            background-color: #e2e8f0 !important;
            font-weight: bold;
            border-top: 2px solid #64748b;
            font-size: 8pt;
        }

        /* SIGNATURE SECTION */
        .signature-table {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-space {
            height: 50px;
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
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 3px;
        }

        /* FOOTER NOTE */
        .footer-note {
            margin-top: 15px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI BERITA ACARA -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="store-name">{{ $store->store_name ?? 'DISTRO OPERATIONS' }}</div>
                <div class="store-sub">
                    Pusat Distribusi & Pergudangan | {{ $store->store_tagline ?? 'APPAREL & STREETWEAR' }}
                </div>
            </td>
            <td class="report-badge" style="width: 40%;">
                <div class="badge-type">
                    📋 BERITA ACARA STOCK OPNAME (BAST)
                </div>
                <div style="font-size: 10pt; font-weight: 800; color: #0f172a; margin-top: 5px;">
                    Hasil Audit Fisik & Rekonsiliasi Gudang
                </div>
            </td>
        </tr>
    </table>

    <!-- METADATA AUDIT -->
    <table class="meta-table">
        <tr>
            <td style="width: 35%;">
                <div class="meta-label">Tanggal Pelaksanaan Audit</div>
                <div class="meta-value">{{ $dateStr }}</div>
            </td>
            <td style="width: 35%;">
                <div class="meta-label">Waktu Cetak Dokumen</div>
                <div class="meta-value">{{ $printedAt }}</div>
            </td>
            <td style="width: 30%;">
                <div class="meta-label">Status Dokumen</div>
                <div class="meta-value">RESMI / ARSIP GUDANG</div>
            </td>
        </tr>
    </table>

    <!-- TABEL DATA AUDIT STOCK OPNAME -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20px;" class="text-center">No</th>
                <th style="width: 55px;" class="text-center">Waktu</th>
                <th style="width: 65px;">Kode</th>
                <th>Artikel Produk</th>
                <th style="width: 70px;" class="text-center">Lokasi Rak</th>
                <th style="width: 40px;" class="text-center">Size</th>
                <th style="width: 50px;" class="text-right">Sistem</th>
                <th style="width: 50px;" class="text-right">Fisik</th>
                <th style="width: 55px;" class="text-right">Selisih</th>
                <th style="width: 80px;">Auditor</th>
                <th>Catatan Investigasi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totSystem = 0;
                $totPhysical = 0;
                $totDiff = 0;
            @endphp
            @forelse($opnames as $index => $op)
                @php
                    $totSystem += $op->system_stock;
                    $totPhysical += $op->physical_stock;
                    $totDiff += $op->difference;
                @endphp
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="text-center font-mono" style="font-size: 7pt;">
                        {{ $op->created_at->format('d/m/y H:i') }}
                    </td>
                    <td class="font-mono font-bold" style="font-size: 7.5pt;">
                        {{ $op->variant?->product?->code ?: '-' }}
                    </td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">
                            {{ $op->variant?->product?->name ?? 'Produk Dihapus' }}
                        </div>
                        <div style="font-size: 6.5pt; color: #64748b;">
                            SKU: {{ $op->variant?->sku ?? '-' }} &bull; {{ $op->variant?->product?->category?->name ?? '-' }}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge-rack">
                            📍 {{ $op->variant?->product?->rack_location ?: 'Tanpa Rak' }}
                        </span>
                    </td>
                    <td class="text-center font-bold font-mono">
                        {{ $op->variant?->size ?? '-' }}
                    </td>
                    <td class="text-right font-mono">
                        {{ number_format($op->system_stock) }}
                    </td>
                    <td class="text-right font-mono font-bold">
                        {{ number_format($op->physical_stock) }}
                    </td>
                    <td class="text-right font-mono font-bold {{ $op->difference === 0 ? 'status-match' : ($op->difference < 0 ? 'status-minus' : 'status-plus') }}">
                        {{ $op->difference > 0 ? '+'.$op->difference : $op->difference }}
                    </td>
                    <td style="font-size: 7.5pt;">
                        {{ $op->user?->name ?? 'Sistem' }}
                    </td>
                    <td style="font-size: 7.5pt; color: #475569;">
                        {{ $op->notes ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 16px; color: #94a3b8; font-style: italic;">
                        Tidak ada data rekonsiliasi stock opname.
                    </td>
                </tr>
            @endforelse

            <!-- BARIS REKAP TOTAL -->
            <tr class="total-row">
                <td colspan="6" class="text-right uppercase">
                    TOTAL KESELURUHAN ({{ $opnames->count() }} ITEM):
                </td>
                <td class="text-right font-mono">
                    {{ number_format($totSystem) }}
                </td>
                <td class="text-right font-mono">
                    {{ number_format($totPhysical) }}
                </td>
                <td class="text-right font-mono {{ $totDiff === 0 ? 'status-match' : ($totDiff < 0 ? 'status-minus' : 'status-plus') }}">
                    {{ $totDiff > 0 ? '+'.$totDiff : $totDiff }}
                </td>
                <td colspan="2" style="font-size: 7pt; color: #475569;">
                    Pcs
                </td>
            </tr>
        </tbody>
    </table>

    <!-- KOLOM TANDA TANGAN BERITA ACARA -->
    <table class="signature-table">
        <tr>
            <td class="signature-box" style="text-align: left;">
                <div class="signature-title">Petugas Penghitung Fisik:</div>
                <div class="signature-space"></div>
                <div class="signature-line">( ............................................ )</div>
                <div class="signature-title">Staff Auditor / Operator Gudang</div>
            </td>
            <td style="width: 40%;"></td>
            <td class="signature-box" style="text-align: right;">
                <div class="signature-title">Mengetahui & Menyetujui:</div>
                <div class="signature-space"></div>
                <div class="signature-line">( ............................................ )</div>
                <div class="signature-title">Kepala Gudang / Owner Toko</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer-note">
        Berita Acara Stock Opname ini diterbitkan sebagai bukti fisik sah rekonsiliasi ketersediaan barang aktual di rak dan sistem inventaris Distro.
    </div>

</body>
</html>

