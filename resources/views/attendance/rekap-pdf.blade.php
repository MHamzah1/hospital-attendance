<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi</title>
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
        tbody tr:hover { background-color: #e8eef7; }
        tbody td { padding: 3px 5px; font-size: 7.5px; border: 0.5px solid #ddd; vertical-align: middle; }
        tbody td.center { text-align: center; }
        tbody td.right { text-align: right; }

        .status-hadir    { color: #0a7c3e; font-weight: bold; }
        .status-terlambat{ color: #b45309; font-weight: bold; }
        .status-tidak    { color: #b91c1c; font-weight: bold; }
        .status-cuti     { color: #1d4ed8; font-weight: bold; }
        .status-sakit    { color: #c2410c; font-weight: bold; }

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
        <h1>REKAPITULASI ABSENSI KARYAWAN</h1>
        <p>Rumah Sakit Kartika Husada Setu</p>
        <p class="period">Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
        @if(!$isAdmin)
        <p style="font-size:8px; margin-top:2px;">Karyawan: {{ $currentUser->name }} ({{ $currentUser->nip ?? $currentUser->employee_id }})</p>
        @endif
    </div>

    @if($attendances->isEmpty())
        <p class="no-data">Tidak ada data absensi pada periode ini.</p>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:25px;">No</th>
                <th style="width:65px;">Tanggal</th>
                <th style="width:50px;">Hari</th>
                @if($isAdmin)
                <th style="width:60px;">NIP</th>
                <th style="min-width:90px;">Nama Karyawan</th>
                <th style="width:70px;">Departemen</th>
                @endif
                <th style="width:55px;">Shift</th>
                <th style="width:60px;">Jam Shift</th>
                <th style="width:45px;">Clock In</th>
                <th style="width:45px;">Clock Out</th>
                <th style="width:55px;">Status</th>
                <th style="min-width:80px;">Keterlambatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $i => $att)
            @php
                $date = \Carbon\Carbon::parse($att->date);
                $shiftName = $att->shift?->name ?? '-';
                $shiftTime = ($att->shift?->start_time && $att->shift?->end_time)
                    ? substr($att->shift->start_time, 0, 5) . ' - ' . substr($att->shift->end_time, 0, 5)
                    : '-';
                $statusLabel = $statusLabels[$att->status] ?? $att->status;
                $statusClass = match($att->status) {
                    'present' => 'status-hadir',
                    'late'    => 'status-terlambat',
                    'absent'  => 'status-tidak',
                    'leave'   => 'status-cuti',
                    'sick'    => 'status-sakit',
                    default   => '',
                };
                $hariId = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
                $hari = $hariId[$date->format('l')] ?? $date->format('l');
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ $date->format('d/m/Y') }}</td>
                <td class="center">{{ $hari }}</td>
                @if($isAdmin)
                <td class="center">{{ $att->user?->nip ?? $att->user?->employee_id ?? '-' }}</td>
                <td>{{ $att->user?->name ?? '-' }}</td>
                <td>{{ $att->user?->department ?? '-' }}</td>
                @endif
                <td class="center">{{ $shiftName }}</td>
                <td class="center">{{ $shiftTime }}</td>
                <td class="center">{{ $att->clock_in ?? '-' }}</td>
                <td class="center">{{ $att->clock_out ?? '-' }}</td>
                <td class="center {{ $statusClass }}">{{ $statusLabel }}</td>
                <td class="center">{{ $att->status === 'late' ? ($att->late_duration ?? '-') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <h3>RINGKASAN</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="num">{{ $summary['total'] }}</div>
                <div class="lbl">Total Record</div>
            </div>
            <div class="summary-item" style="color:#0a7c3e;">
                <div class="num" style="color:#0a7c3e;">{{ $summary['present'] }}</div>
                <div class="lbl">Hadir</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#b45309;">{{ $summary['late'] }}</div>
                <div class="lbl">Terlambat</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#b91c1c;">{{ $summary['absent'] }}</div>
                <div class="lbl">Tidak Hadir</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#1d4ed8;">{{ $summary['leave'] }}</div>
                <div class="lbl">Cuti</div>
            </div>
            <div class="summary-item">
                <div class="num" style="color:#c2410c;">{{ $summary['sick'] }}</div>
                <div class="lbl">Sakit</div>
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y H:i') }} WIB
    </div>
</body>
</html>
