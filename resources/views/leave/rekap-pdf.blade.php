<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Pengajuan Cuti</title>
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
        <h1>REKAPITULASI PENGAJUAN CUTI</h1>
        <p>Rumah Sakit Kartika Husada Setu</p>
        <p class="period">Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
        @if(!$isAdmin)
        <p style="font-size:8px; margin-top:2px;">Karyawan: {{ $currentUser->name }} ({{ $currentUser->nip }})</p>
        @endif
    </div>

    @if($leaves->isEmpty())
        <p class="no-data">Tidak ada data pengajuan cuti pada periode ini.</p>
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
                <th style="width:80px;">Jenis Cuti</th>
                <th style="width:65px;">Tgl Mulai</th>
                <th style="width:65px;">Tgl Selesai</th>
                <th style="width:40px;">Durasi</th>
                <th style="min-width:100px;">Alasan</th>
                <th style="width:55px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leaves as $i => $leave)
            @php
                $statusClass = match($leave->status) {
                    'approved' => 'status-approved',
                    'rejected' => 'status-rejected',
                    default => 'status-pending',
                };
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                @if($isAdmin)
                <td class="center">{{ $leave->user?->nip ?? '-' }}</td>
                <td>{{ $leave->user?->name ?? '-' }}</td>
                <td class="center">{{ $leave->user?->departmentModel?->name ?? '-' }}</td>
                <td class="center">{{ $leave->user?->unitModel?->name ?? '-' }}</td>
                @endif
                <td class="center">{{ $typeLabels[$leave->type] ?? $leave->type }}</td>
                <td class="center">{{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }}</td>
                <td class="center">{{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}</td>
                <td class="center">{{ $leave->total_days }} hari</td>
                <td>{{ $leave->reason }}</td>
                <td class="center {{ $statusClass }}">{{ $statusLabels[$leave->status] ?? $leave->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <h3>RINGKASAN</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="num">{{ $leaves->count() }}</div>
                <div class="lbl">Total</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#0a7c3e;">{{ $leaves->where('status', 'approved')->count() }}</div>
                <div class="lbl">Disetujui</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#b45309;">{{ $leaves->where('status', 'pending')->count() }}</div>
                <div class="lbl">Pending</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#b91c1c;">{{ $leaves->where('status', 'rejected')->count() }}</div>
                <div class="lbl">Ditolak</div>
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB
    </div>
</body>
</html>
