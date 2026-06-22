<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Gaji</title>
    <style>
        @page { size: A4 landscape; margin: 22mm 8mm 10mm 8mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 6.5px; color: #1a1a2e; }

        .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #0f3460; padding-bottom: 6px; }
        .header-table { width: auto; margin: 0 auto; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-logo { padding-right: 10px; text-align: center; }
        .header-logo img { height: 56px; }
        .header-text { text-align: left; }
        .header h1 { font-size: 12px; color: #0f3460; }
        .header p { font-size: 8px; color: #555; margin-top: 2px; }
        .header .period { font-size: 8px; font-weight: bold; color: #0f3460; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }
        table:not(.header-table) th, table:not(.header-table) td { border: 0.5px solid #999; padding: 2px 3px; }
        th { background: #0f3460; color: white; font-size: 6px; text-align: center; white-space: nowrap; }
        td { font-size: 6.5px; }
        td.num { text-align: right; font-family: monospace; white-space: nowrap; }
        td.ctr { text-align: center; }

        .section-header { background: #e8eef7; font-weight: bold; text-align: center; font-size: 6px; color: #0f3460; }
        .total-row { background: #f0f4f8; font-weight: bold; }
        .footer { text-align: center; font-size: 6px; color: #999; margin-top: 5px; border-top: 1px solid #ddd; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                @if(file_exists(public_path('logo.png')))
                <td class="header-logo">
                    <img src="{{ public_path('logo.png') }}" alt="Logo">
                </td>
                @endif
                <td class="header-text">
                    <h1>REKAP GAJI KARYAWAN</h1>
                    <p>Rumah Sakit Kartika Husada Setu</p>
                    <p>Jl. MT. Haryono, Burangkeng, Kec. Setu, Kabupaten Bekasi, Jawa Barat 17320 | Telp: (021) 1234567</p>
                    <p class="period">Periode: {{ $monthName }} {{ $year }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">NIP</th>
                <th rowspan="2">Nama</th>
                <th rowspan="2">Dept</th>
                <th colspan="6">Kehadiran</th>
                <th colspan="8">Pendapatan</th>
                <th colspan="4">Lembur</th>
                <th rowspan="2">Total<br>Pendapatan</th>
                <th colspan="7">Potongan</th>
                <th rowspan="2">Total<br>Potongan</th>
                <th rowspan="2">Gaji<br>Bersih</th>
            </tr>
            <tr>
                {{-- Kehadiran --}}
                <th>Kerja</th><th>Hadir</th><th>Telat</th><th>Absen</th><th>Cuti</th><th>Sakit</th>
                {{-- Pendapatan --}}
                <th>Gapok</th><th>Jabatan</th><th>Fungsional</th><th>Khusus</th><th>Makan</th><th>Transport</th><th>Kehadiran</th><th>BRUTO</th>
                {{-- Lembur --}}
                <th>Lembur</th><th>On Call</th><th>MOD</th><th>Hari Raya</th>
                {{-- Potongan --}}
                <th>BPJS Kes</th><th>BPJS JHT</th><th>BPJS JP</th><th>PPh21</th><th>CDT</th><th>Alpa</th><th>Lainnya</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBruto = 0; $totalNetto = 0; $totalPotongan = 0; $totalPendapatan = 0;
            @endphp
            @forelse($payrolls as $i => $p)
                @php
                    $pendapatan = $p->gross_salary + ($p->overtime_hourly ?? 0) + ($p->overtime_on_call ?? 0)
                        + ($p->overtime_mod ?? 0) + ($p->overtime_holiday ?? 0)
                        + ($p->salary_correction ?? 0) + ($p->other_allowance ?? 0);
                    $potLainnya = ($p->cashbond_deduction ?? 0) + ($p->piutang_obat_deduction ?? 0)
                        + ($p->salary_correction_deduction ?? 0) + ($p->bank_admin_deduction ?? 0) + ($p->other_deduction ?? 0);
                    $totalBruto += $p->gross_salary;
                    $totalNetto += $p->net_salary;
                    $totalPotongan += $p->total_deduction;
                    $totalPendapatan += $pendapatan;
                @endphp
                <tr>
                    <td class="ctr">{{ $i + 1 }}</td>
                    <td>{{ $p->user->nip ?? $p->user->employee_id }}</td>
                    <td>{{ $p->user->name }}</td>
                    <td>{{ $p->user->department }}</td>
                    {{-- Kehadiran --}}
                    <td class="ctr">{{ $p->total_work_days }}</td>
                    <td class="ctr">{{ $p->present_days }}</td>
                    <td class="ctr">{{ $p->late_days }}</td>
                    <td class="ctr">{{ $p->absent_days }}</td>
                    <td class="ctr">{{ $p->leave_days }}</td>
                    <td class="ctr">{{ $p->sick_days }}</td>
                    {{-- Pendapatan --}}
                    <td class="num">{{ number_format($p->base_salary, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->position_allowance, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->functional_allowance ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->special_allowance ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->meal_allowance, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->transport_allowance, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->attendance_allowance ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->gross_salary, 0, ',', '.') }}</td>
                    {{-- Lembur --}}
                    <td class="num">{{ number_format($p->overtime_hourly ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->overtime_on_call ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->overtime_mod ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->overtime_holiday ?? 0, 0, ',', '.') }}</td>
                    {{-- Total Pendapatan --}}
                    <td class="num" style="font-weight: bold;">{{ number_format($pendapatan, 0, ',', '.') }}</td>
                    {{-- Potongan --}}
                    <td class="num">{{ number_format($p->bpjs_kesehatan, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->bpjs_ketenagakerjaan, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->bpjs_pensiun_jp ?? $p->bpjs_pensiun ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->pph21 ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->cdt_deduction ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($p->alpha_deduction ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($potLainnya, 0, ',', '.') }}</td>
                    {{-- Total Potongan & Gaji Bersih --}}
                    <td class="num" style="font-weight: bold;">{{ number_format($p->total_deduction, 0, ',', '.') }}</td>
                    <td class="num" style="font-weight: bold; color: #059669;">{{ number_format($p->net_salary, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="30" style="text-align: center; padding: 10px;">Tidak ada data penggajian</td></tr>
            @endforelse

            @if($payrolls->count() > 0)
            <tr class="total-row">
                <td colspan="4" style="text-align: center; font-weight: bold;">TOTAL ({{ $payrolls->count() }} karyawan)</td>
                <td colspan="6"></td>
                <td colspan="7"></td>
                <td class="num">{{ number_format($totalBruto, 0, ',', '.') }}</td>
                <td colspan="4"></td>
                <td class="num">{{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                <td colspan="7"></td>
                <td class="num">{{ number_format($totalPotongan, 0, ',', '.') }}</td>
                <td class="num" style="color: #059669;">{{ number_format($totalNetto, 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Dicetak {{ now()->format('d/m/Y H:i') }} | RS Kartika Husada Setu
    </div>
</body>
</html>
