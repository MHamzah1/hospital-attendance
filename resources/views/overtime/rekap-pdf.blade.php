<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Pengajuan Lembur</title>
    <style>
        @page { size: A4 landscape; margin: 15mm 15mm 10mm 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #1a1a2e; }
        .header { border-bottom: 2px solid #0f3460; padding-bottom: 6px; margin-bottom: 8px; }
        .header h1 { font-size: 14px; color: #0f3460; margin-bottom: 2px; }
        .header p { font-size: 8px; color: #555; }
        .header .period { font-size: 9px; font-weight: bold; color: #0f3460; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr { background-color: #0f3460; color: white; }
        thead th { padding: 4px 5px; font-size: 7.5px; text-align: center; border: 1px solid #0a2a50; white-space: nowrap; }
        tbody tr:nth-child(even) { background-color: #f0f4f8; }
        tbody td { padding: 3px 5px; font-size: 7.5px; border: 0.5px solid #ddd; vertical-align: middle; }
        tbody td.center { text-align: center; }
        tbody td.right { text-align: right; }
        .status-pending { color: #b45309; font-weight: bold; }
        .status-approved { color: #0a7c3e; font-weight: bold; }
        .status-rejected { color: #b91c1c; font-weight: bold; }
        .summary-box { border: 1px solid #0f3460; padding: 8px; margin-top: 8px; page-break-inside: avoid; }
        .summary-box h3 { font-size: 9px; color: #0f3460; margin-bottom: 6px; border-bottom: 1px solid #0f3460; padding-bottom: 3px; }
        .summary-grid { display: table; width: 100%; }
        .summary-item { display: table-cell; text-align: center; padding: 4px 8px; }
        .summary-item .num { font-size: 16px; font-weight: bold; color: #0f3460; }
        .summary-item .lbl { font-size: 7px; color: #555; margin-top: 2px; }
        .footer { margin-top: 12px; font-size: 7px; color: #888; text-align: right; }
        .no-data { text-align: center; padding: 20px; color: #888; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAPITULASI PENGAJUAN LEMBUR</h1>
        <p>Rumah Sakit Kartika Husada Setu</p>
        <p class="period">Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
        @if(!$isAdmin)
        <p style="font-size:8px; margin-top:2px;">Karyawan: {{ $currentUser->name }} ({{ $currentUser->nip }})</p>
        @endif
    </div>

    @if($overtimes->isEmpty())
        <p class="no-data">Tidak ada data pengajuan lembur pada periode ini.</p>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:25px;">No</th>
                @if($isAdmin)
                <th style="width:60px;">NIP</th>
                <th style="min-width:90px;">Nama Karyawan</th>
                <th style="width:70px;">Departemen</th>
                <th style="width:70px;">Unit</th>
                @endif
                <th style="width:65px;">Tanggal</th>
                <th style="width:70px;">Waktu</th>
                <th style="width:40px;">Jam</th>
                <th style="width:60px;">Kategori</th>
                @if($isAdmin)
                <th style="width:65px;">Total Bayar</th>
                @endif
                <th style="min-width:100px;">Alasan</th>
                <th style="width:55px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimes as $i => $ot)
            @php
                $statusClass = match($ot->status) {
                    'approved' => 'status-approved',
                    'rejected' => 'status-rejected',
                    default => 'status-pending',
                };
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                @if($isAdmin)
                <td class="center">{{ $ot->user?->nip ?? '-' }}</td>
                <td>{{ $ot->user?->name ?? '-' }}</td>
                <td class="center">{{ $ot->user?->departmentModel?->name ?? '-' }}</td>
                <td class="center">{{ $ot->user?->unitModel?->name ?? '-' }}</td>
                @endif
                <td class="center">{{ \Carbon\Carbon::parse($ot->date)->format('d/m/Y') }}</td>
                <td class="center">{{ substr($ot->start_time, 0, 5) }} - {{ substr($ot->end_time, 0, 5) }}</td>
                <td class="center">{{ abs($ot->total_hours) }} jam</td>
                <td class="center">{{ $categoryLabels[$ot->category] ?? $ot->category }}</td>
                @if($isAdmin)
                <td class="right">{{ $ot->status === 'approved' ? 'Rp ' . number_format(abs($ot->total_pay), 0, ',', '.') : '-' }}</td>
                @endif
                <td>{{ $ot->reason }}</td>
                <td class="center {{ $statusClass }}">{{ $statusLabels[$ot->status] ?? $ot->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <h3>RINGKASAN</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="num">{{ $overtimes->count() }}</div>
                <div class="lbl">Total</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#0a7c3e;">{{ $overtimes->where('status', 'approved')->count() }}</div>
                <div class="lbl">Disetujui</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#b45309;">{{ $overtimes->where('status', 'pending')->count() }}</div>
                <div class="lbl">Pending</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#b91c1c;">{{ $overtimes->where('status', 'rejected')->count() }}</div>
                <div class="lbl">Ditolak</div>
            </div>
            @if($isAdmin)
            <div class="summary-item">
                <div class="num" style="color:#0f3460;">Rp {{ number_format($overtimes->where('status', 'approved')->sum('total_pay'), 0, ',', '.') }}</div>
                <div class="lbl">Total Bayar</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB
    </div>
</body>
</html>
