<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a2e; padding: 15px 20px; }
        .header { text-align: center; border-bottom: 3px double #0f3460; padding-bottom: 12px; margin-bottom: 12px; }
        .header h1 { font-size: 18px; color: #0f3460; margin-bottom: 2px; letter-spacing: 1px; }
        .header h2 { font-size: 12px; color: #16213e; font-weight: normal; }
        .header p { font-size: 9px; color: #666; }
        .slip-title { text-align: center; background: #0f3460; color: white; padding: 6px; font-size: 12px; font-weight: bold; margin-bottom: 10px; letter-spacing: 1px; }
        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { padding: 2px 4px; font-size: 10px; }
        .info-table .label { font-weight: bold; width: 130px; color: #333; }
        .two-col { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .two-col > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        .two-col > tbody > tr > td:first-child { padding-right: 8px; }
        .two-col > tbody > tr > td:last-child { padding-left: 8px; }
        .section-title { background: #e8eef7; padding: 4px 8px; font-weight: bold; color: #0f3460; margin-bottom: 4px; font-size: 10px; border-left: 3px solid #0f3460; }
        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .detail-table td { padding: 3px 6px; border-bottom: 1px solid #eee; font-size: 9.5px; }
        .detail-table .amount { text-align: right; font-family: monospace; font-size: 9.5px; }
        .total-row { background: #e8eef7; font-weight: bold; }
        .total-row td { border-top: 2px solid #0f3460; border-bottom: 2px solid #0f3460; }
        .net-salary-table { width: 100%; background: #0f3460; color: white; margin-top: 8px; }
        .net-salary-table td { padding: 8px 10px; font-size: 13px; font-weight: bold; }
        .attendance-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .attendance-table td { padding: 3px 6px; border: 1px solid #ddd; font-size: 9.5px; text-align: center; }
        .attendance-table .att-label { background: #f0f4f8; font-weight: bold; text-align: left; }
        .signatures { margin-top: 30px; width: 100%; }
        .signatures td { text-align: center; padding-top: 50px; width: 33%; font-size: 10px; }
        .confidential { text-align: center; color: #e74c3c; font-size: 8px; font-style: italic; margin-top: 5px; }
        .footer { margin-top: 15px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('logo.png')))
        <img src="{{ public_path('logo.png') }}" style="height: 50px; margin-bottom: 5px;" alt="Logo">
        <br>
        @endif
        <h1>RUMAH SAKIT KARTIKA HUSADA SETU</h1>
        <h2>Jl. Raya Serang - Cibarusah KM.29, Setu, Bekasi</h2>
        <p>Telp: (021) 89956215 | Email: rskartikahusadasetu@gmail.com</p>
    </div>

    <div class="slip-title">SLIP GAJI KARYAWAN — {{ strtoupper($monthName) }} {{ $payroll->year }}</div>

    <table class="info-table">
        <tr>
            <td class="label">NIP</td>
            <td>: {{ $payroll->user->nip ?? $payroll->user->employee_id }}</td>
            <td class="label">Jabatan</td>
            <td>: {{ $payroll->user->position ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Karyawan</td>
            <td>: {{ $payroll->user->name }}</td>
            <td class="label">Unit / Dept.</td>
            <td>: {{ $payroll->user->department ?? '-' }}</td>
        </tr>
    </table>

    {{-- Rekap Kehadiran --}}
    <div class="section-title">REKAP KEHADIRAN</div>
    <table class="attendance-table">
        <tr>
            <td class="att-label">Hari Kerja</td>
            <td>{{ $payroll->total_work_days }} hari</td>
            <td class="att-label">Hadir</td>
            <td>{{ $payroll->present_days }} hari</td>
            <td class="att-label">Terlambat</td>
            <td>{{ $payroll->late_days }} hari</td>
        </tr>
        <tr>
            <td class="att-label">Cuti</td>
            <td>{{ $payroll->leave_days }} hari</td>
            <td class="att-label">Sakit</td>
            <td>{{ $payroll->sick_days }} hari</td>
            <td class="att-label">Tidak Hadir</td>
            <td>{{ $payroll->absent_days }} hari</td>
        </tr>
    </table>

    {{-- Two-column: Pendapatan (left) | Potongan (right) --}}
    <table class="two-col">
        <tr>
            {{-- LEFT: PENDAPATAN --}}
            <td>
                <div class="section-title">PENDAPATAN</div>
                <table class="detail-table">
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="amount">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                    </tr>
                    @if($payroll->position_allowance > 0)
                    <tr>
                        <td>Tunj. Jabatan</td>
                        <td class="amount">{{ number_format($payroll->position_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->functional_allowance > 0)
                    <tr>
                        <td>Tunj. Fungsional</td>
                        <td class="amount">{{ number_format($payroll->functional_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->special_allowance > 0)
                    <tr>
                        <td>Tunj. Khusus</td>
                        <td class="amount">{{ number_format($payroll->special_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->meal_allowance > 0)
                    <tr>
                        <td>Tunj. Makan</td>
                        <td class="amount">{{ number_format($payroll->meal_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->transport_allowance > 0)
                    <tr>
                        <td>Tunj. Transport</td>
                        <td class="amount">{{ number_format($payroll->transport_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->attendance_allowance > 0)
                    <tr>
                        <td>Tunj. Kehadiran</td>
                        <td class="amount">{{ number_format($payroll->attendance_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>BRUTO</strong></td>
                        <td class="amount"><strong>{{ number_format($payroll->gross_salary, 0, ',', '.') }}</strong></td>
                    </tr>

                    {{-- Lembur --}}
                    @if($payroll->overtime_hourly > 0)
                    <tr>
                        <td>Lembur Jam</td>
                        <td class="amount">{{ number_format($payroll->overtime_hourly, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->overtime_night > 0)
                    <tr>
                        <td>Lembur Malam</td>
                        <td class="amount">{{ number_format($payroll->overtime_night, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->overtime_shift > 0)
                    <tr>
                        <td>Lembur Shift</td>
                        <td class="amount">{{ number_format($payroll->overtime_shift, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->overtime_on_call > 0)
                    <tr>
                        <td>Lembur On Call</td>
                        <td class="amount">{{ number_format($payroll->overtime_on_call, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->overtime_mod > 0)
                    <tr>
                        <td>Lembur MOD</td>
                        <td class="amount">{{ number_format($payroll->overtime_mod, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->overtime_holiday > 0)
                    <tr>
                        <td>Lembur Hari Raya</td>
                        <td class="amount">{{ number_format($payroll->overtime_holiday, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->salary_correction > 0)
                    <tr>
                        <td>Koreksi Upah (+)</td>
                        <td class="amount">{{ number_format($payroll->salary_correction, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->other_allowance > 0)
                    <tr>
                        <td>Lain-lain (+)</td>
                        <td class="amount">{{ number_format($payroll->other_allowance, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @php
                        $totalPendapatan = $payroll->gross_salary
                            + ($payroll->overtime_hourly ?? 0) + ($payroll->overtime_night ?? 0)
                            + ($payroll->overtime_shift ?? 0) + ($payroll->overtime_on_call ?? 0)
                            + ($payroll->overtime_mod ?? 0) + ($payroll->overtime_holiday ?? 0)
                            + ($payroll->salary_correction ?? 0) + ($payroll->other_allowance ?? 0);
                    @endphp
                    <tr class="total-row">
                        <td><strong>TOTAL PENDAPATAN</strong></td>
                        <td class="amount"><strong>{{ number_format($totalPendapatan, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </td>

            {{-- RIGHT: POTONGAN --}}
            <td>
                <div class="section-title">POTONGAN</div>
                <table class="detail-table">
                    <tr>
                        <td>CDT</td>
                        <td class="amount">{{ number_format($payroll->cdt_deduction, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Alpa</td>
                        <td class="amount">{{ number_format($payroll->alpha_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @if($payroll->cashbond_deduction > 0)
                    <tr>
                        <td>Cashbond</td>
                        <td class="amount">{{ number_format($payroll->cashbond_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->piutang_obat_deduction > 0)
                    <tr>
                        <td>Piutang Obat</td>
                        <td class="amount">{{ number_format($payroll->piutang_obat_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->salary_correction_deduction > 0)
                    <tr>
                        <td>Koreksi Upah (-)</td>
                        <td class="amount">{{ number_format($payroll->salary_correction_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->bank_admin_deduction > 0)
                    <tr>
                        <td>Adm. Bank</td>
                        <td class="amount">{{ number_format($payroll->bank_admin_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->pph21 > 0)
                    <tr>
                        <td>PPh 21</td>
                        <td class="amount">{{ number_format($payroll->pph21, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($payroll->other_deduction > 0)
                    <tr>
                        <td>Potongan Lainnya</td>
                        <td class="amount">{{ number_format($payroll->other_deduction, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>BPJS Kesehatan (1%)</td>
                        <td class="amount">{{ number_format($payroll->bpjs_kesehatan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK JHT (2%)</td>
                        <td class="amount">{{ number_format($payroll->bpjs_ketenagakerjaan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK JP (1%)</td>
                        <td class="amount">{{ number_format($payroll->bpjs_pensiun_jp ?? $payroll->bpjs_pensiun ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL POTONGAN</strong></td>
                        <td class="amount"><strong>{{ number_format($payroll->total_deduction, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net-salary-table">
        <tr>
            <td>GAJI DIBAYARKAN (Take Home Pay)</td>
            <td style="text-align: right;">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Diterima oleh,<br><br><br><br>
                <strong>{{ $payroll->user->name }}</strong><br>
                <small>Karyawan</small>
            </td>
            <td></td>
            <td>
                Disetujui oleh,<br><br><br><br>
                <strong>______________________</strong><br>
                <small>HRD / Keuangan</small>
            </td>
        </tr>
    </table>

    <div class="confidential">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} | RS Kartika Husada Setu — Sistem Penggajian
    </div>
</body>
</html>
