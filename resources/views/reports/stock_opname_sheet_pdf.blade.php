<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar Hitung Fisik Gudang (Stock Count Sheet)</title>
    <style>
        @page {
            margin: 25px 28px 28px 28px;
            size: A4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
            line-height: 1.3;
        }

        /* HEADER KOP TOKO */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .store-name {
            font-size: 14pt;
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

        .sheet-badge {
            text-align: right;
            vertical-align: top;
        }

        .badge-title {
            display: inline-block;
            padding: 4px 10px;
            font-size: 8.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #94a3b8;
        }

        /* METADATA AUDIT */
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
        }

        .meta-label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .meta-value {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
        }

        /* PETUNJUK KERJA */
        .instruction-box {
            background-color: #fffbeb;
            border: 1px dashed #f59e0b;
            padding: 6px 10px;
            margin-bottom: 12px;
            border-radius: 4px;
            font-size: 7.5pt;
            color: #92400e;
        }

        /* TABEL DATA */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .data-table th {
            background-color: #0f172a;
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
            padding: 7px 5px;
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

        /* KOTAK TULIS FISIK OLEH STAF DENGAN PULPEN */
        .count-box {
            height: 22px;
            border: 1.5px dashed #475569;
            background-color: #ffffff;
            border-radius: 3px;
        }

        .badge-rack {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #1e293b;
        }

        /* SIGNATURE SECTION */
        .signature-table {
            width: 100%;
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 32%;
        }

        .signature-space {
            height: 48px;
        }

        .signature-line {
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            width: 85%;
            font-weight: bold;
            color: #0f172a;
            padding-bottom: 2px;
        }

        .signature-title {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 3px;
        }

        .footer-note {
            margin-top: 12px;
            font-size: 6.5pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI DOKUMEN -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="store-name">{{ $store->store_name ?? 'DISTRO OPERATIONS' }}</div>
                <div class="store-sub">
                    Gudang & Logistik | {{ $store->store_tagline ?? 'APPAREL & STREETWEAR' }}
                </div>
            </td>
            <td class="sheet-badge" style="width: 45%;">
                <div class="badge-title">
                    📋 BERKAS KERJA HITUNG FISIK LAPANGAN
                </div>
                <div style="font-size: 9pt; font-weight: bold; color: #0f172a; margin-top: 4px;">
                    Stock Count Sheet (Tally Sheet)
                </div>
            </td>
        </tr>
    </table>

    <!-- METADATA AUDIT -->
    <table class="meta-table">
        <tr>
            <td style="width: 25%;">
                <div class="meta-label">Tanggal Audit</div>
                <div class="meta-value">{{ $dateStr }}</div>
            </td>
            <td style="width: 25%;">
                <div class="meta-label">Lokasi Rak / Kategori</div>
                <div class="meta-value">{{ $filterLabel }}</div>
            </td>
            <td style="width: 25%;">
                <div class="meta-label">Petugas Lapangan</div>
                <div class="meta-value">....................................</div>
            </td>
            <td style="width: 25%;">
                <div class="meta-label">Waktu Pengerjaan</div>
                <div class="meta-value">Mulai: .....  Selesai: .....</div>
            </td>
        </tr>
    </table>

    <!-- INSTRUKSI KERJA PETUGAS GUDANG -->
    <div class="instruction-box">
        <strong>PETUNJUK PETUGAS GUDANG:</strong> Bawa lembar ini ke lorong rak/gudang. Hitung fisik pakaian aktual yang tersusun di rak/gantungan secara teliti. Tuliskan jumlah fisik hasil hitungan menggunakan pulpen pada kotak <strong>[ HASIL HITUNG FISIK ]</strong>. Serahkan kembali lembar ini ke Admin/Owner untuk diinput ke sistem.
    </div>

    <!-- TABEL LEMBAR KERJA HITUNG FISIK -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 85px;" class="text-center">Lokasi Rak</th>
                <th>Nama Artikel Produk & Kategori</th>
                <th style="width: 50px;" class="text-center">Ukuran</th>
                <th style="width: 130px;" class="text-center">HASIL HITUNG FISIK</th>
                <th style="width: 130px;">Catatan Fisik / Kondisi</th>
                <th style="width: 40px;" class="text-center">Paraf</th>
            </tr>
        </thead>
        <tbody>
            @forelse($variants as $index => $v)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="text-center">
                        <span class="badge-rack">
                            📍 {{ $v->product?->rack_location ?: 'Tanpa Rak' }}
                        </span>
                    </td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">
                            {{ $v->product?->name }}
                        </div>
                        <div style="font-size: 6.5pt; color: #64748b;">
                            {{ $v->product?->category?->name ?? 'Kategori Umum' }}
                        </div>
                    </td>
                    <td class="text-center font-bold font-mono" style="font-size: 9pt;">
                        {{ $v->size }}
                    </td>
                    <td class="text-center" style="padding: 4px 8px;">
                        <!-- Kotak kosong untuk diisi staf dengan pulpen -->
                        <div class="count-box"></div>
                    </td>
                    <td style="font-size: 7pt; color: #64748b;">
                        &nbsp;
                    </td>
                    <td class="text-center">
                        &nbsp;
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8; font-style: italic;">
                        Tidak ada varian produk yang sesuai dengan filter pencetakan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN SERAH TERIMA BERKAS -->
    <table class="signature-table">
        <tr>
            <td class="signature-box" style="text-align: left;">
                <div class="signature-title">Petugas Penghitung Fisik:</div>
                <div class="signature-space"></div>
                <div class="signature-line">( ............................................ )</div>
                <div class="signature-title">Staf Gudang / Lapangan</div>
            </td>
            <td style="width: 36%;"></td>
            <td class="signature-box" style="text-align: right;">
                <div class="signature-title">Diterima & Diinput Oleh:</div>
                <div class="signature-space"></div>
                <div class="signature-line">( ............................................ )</div>
                <div class="signature-title">Admin Gudang / Owner</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini adalah formulir kerja fisik operasional gudang. Setelah diisi, lembar ini wajib diarsipkan sebagai bukti audit fisik rekonsiliasi stok.
    </div>

</body>
</html>

