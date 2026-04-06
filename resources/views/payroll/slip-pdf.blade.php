<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a2e; }
        
        /* Header - logo left, text left */
        .header { border-bottom: 3px double #0f3460; padding-bottom: 10px; margin-bottom: 12px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .header-logo { width: 70px; padding-right: 12px; }
        .header-logo img { height: 60px; }
        .header-text h1 { font-size: 18px; color: #0f3460; margin-bottom: 2px; letter-spacing: 1px; }
        .header-text p { font-size: 9px; color: #333; }
        
        /* Info Karyawan */
        .info-box { width: 100%; border: 1.5px solid #0f3460; border-collapse: collapse; margin-bottom: 10px; }
        .info-box td { padding: 3px 6px; font-size: 10px; border: 0.5px solid #ccc; }
        .info-box .label { font-weight: bold; color: #0f3460; width: 130px; text-transform: uppercase; font-size: 9px; }
        .info-box .separator { width: 10px; text-align: center; }
        
        /* Section titles */
        .section-title { background: #0f3460; color: white; padding: 4px 8px; font-weight: bold; font-size: 10px; }
        .sub-section { background: #e8eef7; padding: 3px 8px; font-weight: bold; color: #0f3460; font-size: 9px; border-bottom: 1px solid #ccc; }
        
        /* Detail tables */
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { padding: 2.5px 6px; font-size: 9.5px; border-bottom: 1px solid #eee; }
        .detail-table .amount { text-align: right; font-family: monospace; font-size: 9.5px; white-space: nowrap; }
        .detail-table .amount-prefix { text-align: right; font-family: monospace; font-size: 9.5px; width: 20px; }
        
        /* Total rows */
        .total-row { background: #e8eef7; font-weight: bold; }
        .total-row td { border-top: 1.5px solid #0f3460; border-bottom: 1.5px solid #0f3460; padding: 4px 6px; }
        
        /* Net salary */
        .net-salary-table { width: 100%; background: #0f3460; color: white; margin-top: 8px; }
        .net-salary-table td { padding: 8px 10px; font-size: 14px; font-weight: bold; }
        
        /* Attendance */
        .attendance-box { border: 1px solid #0f3460; margin-bottom: 10px; }
        .attendance-table { width: 100%; border-collapse: collapse; }
        .attendance-table td { padding: 3px 6px; border: 0.5px solid #ccc; font-size: 9.5px; text-align: center; }
        .attendance-table .att-label { background: #f0f4f8; font-weight: bold; text-align: left; }
        
        /* Signatures */
        .signatures { margin-top: 25px; width: 100%; }
        .signatures td { text-align: center; width: 50%; font-size: 10px; vertical-align: top; }
        .sig-name { font-weight: bold; border-bottom: 1px solid #333; display: inline-block; padding-bottom: 2px; min-width: 150px; }
        .sig-title { font-size: 9px; color: #555; }
        
        .confidential { text-align: center; color: #e74c3c; font-size: 8px; font-style: italic; margin-top: 5px; }
        .footer { margin-top: 10px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
        
        .col-wrapper { border: 1px solid #0f3460; margin-bottom: 8px; }
        
        /* Two column layout for pendapatan/potongan side labels */
        .two-col-info { width: 100%; border-collapse: collapse; }
        .two-col-info > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        .two-col-info > tbody > tr > td:first-child { padding-right: 4px; }
        .two-col-info > tbody > tr > td:last-child { padding-left: 4px; }
    </style>
</head>
<body>
    @php
        // Helper: convert decimal hours to "X jam Y menit Z detik"
        $formatHours = function($hours) {
            $totalSeconds = abs(round($hours * 3600));
            $h = floor($totalSeconds / 3600);
            $m = floor(($totalSeconds % 3600) / 60);
            $s = $totalSeconds % 60;
            return "{$h} jam {$m} menit {$s} detik";
        };
        // Helper: convert minutes to "X jam Y menit Z detik"
        $formatMinutes = function($minutes) {
            $totalSeconds = abs(round($minutes * 60));
            $h = floor($totalSeconds / 3600);
            $m = floor(($totalSeconds % 3600) / 60);
            $s = $totalSeconds % 60;
            return "{$h} jam {$m} menit {$s} detik";
        };
    @endphp

    {{-- Header - logo left, text left --}}
    <div class="header">
        <table class="header-table">
            <tr>
                @if(file_exists(public_path('logo.png')))
                <td class="header-logo">
                    <img src="{{ public_path('logo.png') }}" alt="Logo">
                </td>
                @endif
                <td class="header-text">
                    <h1>RUMAH SAKIT KARTIKA HUSADA SETU</h1>
                    <p>Jl. MT. Haryono, Burangkeng, Kec. Setu, Kabupaten Bekasi, Jawa Barat 17320 | Telp: (021) 1234567</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Info Karyawan --}}
    <table class="info-box">
        <tr>
            <td class="label">NAMA PEGAWAI</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->name }}</td>
            <td class="label">PERIODE</td>
            <td class="separator">:</td>
            <td>{{ $monthName }} {{ $payroll->year }}</td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->nip ?? $payroll->user->employee_id }}</td>
            <td class="label">NO. BPJS KESEHATAN</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->bpjs_kesehatan ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">JABATAN</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->position ?? '-' }}</td>
            <td class="label">NO. BPJS TK</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->bpjs_ketenagakerjaan ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">TANGGAL MASUK</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->join_date ? $payroll->user->join_date->format('d/m/Y') : '-' }}</td>
            <td class="label">NO. NPWP</td>
            <td class="separator">:</td>
            <td>{{ $payroll->user->npwp ?: '-' }}</td>
        </tr>
    </table>

    {{-- Rekap Kehadiran --}}
    <div class="attendance-box">
        <div class="section-title">REKAP KEHADIRAN</div>
        <table class="attendance-table">
            <tr>
                <td class="att-label">Hadir</td>
                <td>{{ $payroll->present_days }} hari</td>
                <td class="att-label">Terlambat</td>
                <td>{{ $formatMinutes($payroll->late_minutes ?? 0) }}</td>
                <td class="att-label">Tidak Hadir</td>
                <td>{{ $payroll->absent_days }} hari</td>
            </tr>
            <tr>
                <td class="att-label">Lembur</td>
                <td>{{ $formatHours($payroll->overtime_hours ?? 0) }}</td>
                <td class="att-label">Cuti</td>
                <td>{{ $payroll->leave_days }} hari</td>
                <td class="att-label">Sakit</td>
                <td>{{ $payroll->sick_days }} hari</td>
            </tr>
            <tr>
                <td class="att-label">Jatah Cuti</td>
                <td>{{ $cutiInfo['jatah_cuti'] ?? 12 }} hari</td>
                <td class="att-label">Sisa Cuti</td>
                <td>{{ $cutiInfo['sisa_cuti'] ?? 12 }} hari</td>
                <td class="att-label"></td>
                <td></td>
            </tr>
        </table>
    </div>

    {{-- PENDAPATAN (full width) --}}
    <div class="col-wrapper">
        <div class="section-title">PENDAPATAN</div>
        <div class="sub-section">Gaji & Tunjangan</div>
        <table class="detail-table">
            <tr>
                <td>Gaji Pokok</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->position_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Fungsional</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->functional_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Khusus</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->special_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Makan</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->meal_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Transport</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->transport_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Kehadiran</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->attendance_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>BRUTO</strong></td>
                <td class="amount-prefix"><strong>Rp</strong></td>
                <td class="amount"><strong>{{ number_format($payroll->gross_salary, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        <div class="sub-section">Lembur</div>
        <table class="detail-table">
            <tr>
                <td>Lembur Jam (@10rb/jam)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->overtime_hourly ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lembur Malam (@20rb)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->overtime_night ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lembur Shift (@60rb)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->overtime_shift ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lembur On Call (@50rb)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->overtime_on_call ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lembur Hari Raya (@120rb)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->overtime_holiday ?? 0, 0, ',', '.') }}</td>
            </tr>
            @php
                $totalLembur = ($payroll->overtime_hourly ?? 0) + ($payroll->overtime_night ?? 0)
                    + ($payroll->overtime_shift ?? 0) + ($payroll->overtime_on_call ?? 0)
                    + ($payroll->overtime_mod ?? 0) + ($payroll->overtime_holiday ?? 0);
            @endphp
            <tr class="total-row">
                <td><strong>Total Lembur</strong></td>
                <td class="amount-prefix"><strong>Rp</strong></td>
                <td class="amount"><strong>{{ number_format($totalLembur, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        <div class="sub-section">Tambahan Lainnya</div>
        <table class="detail-table">
            <tr>
                <td>Koreksi Upah (+)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->salary_correction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Lain-lain (+)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->other_allowance ?? 0, 0, ',', '.') }}</td>
            </tr>
            @php
                $totalPendapatan = $payroll->gross_salary
                    + ($payroll->overtime_hourly ?? 0) + ($payroll->overtime_night ?? 0)
                    + ($payroll->overtime_shift ?? 0) + ($payroll->overtime_on_call ?? 0)
                    + ($payroll->overtime_mod ?? 0) + ($payroll->overtime_holiday ?? 0)
                    + ($payroll->salary_correction ?? 0) + ($payroll->other_allowance ?? 0);
            @endphp
            <tr class="total-row">
                <td><strong>TOTAL PENDAPATAN</strong></td>
                <td class="amount-prefix"><strong>Rp</strong></td>
                <td class="amount"><strong>{{ number_format($totalPendapatan, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- POTONGAN (full width, below pendapatan) --}}
    <div class="col-wrapper">
        <div class="section-title">POTONGAN</div>
        <div class="sub-section">BPJS & Pajak</div>
        <table class="detail-table">
            <tr>
                <td>BPJS Kesehatan (1%)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->bpjs_kesehatan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>BPJS TK - JHT (2%)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->bpjs_ketenagakerjaan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>BPJS TK - JP (1%)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->bpjs_pensiun_jp ?? $payroll->bpjs_pensiun ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>PPh 21</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->pph21 ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="sub-section">Potongan Admin</div>
        <table class="detail-table">
            <tr>
                <td>CDT</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->cdt_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Alpha / Ketidakhadiran</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->alpha_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Cashbond</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->cashbond_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Piutang Obat</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->piutang_obat_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Koreksi Upah (-)</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->salary_correction_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Adm. Bank</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->bank_admin_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Potongan Lainnya</td>
                <td class="amount-prefix">Rp</td>
                <td class="amount">{{ number_format($payroll->other_deduction ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL POTONGAN</strong></td>
                <td class="amount-prefix"><strong>Rp</strong></td>
                <td class="amount"><strong>{{ number_format($payroll->total_deduction, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <table class="net-salary-table">
        <tr>
            <td>GAJI BERSIH (Take Home Pay)</td>
            <td style="text-align: right;">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <br>Diterima oleh,<br><br><br><br>
                <span class="sig-name">{{ $payroll->user->name }}</span><br>
                <span class="sig-title">Karyawan</span>
            </td>
            <td>
                Bekasi, {{ now()->format('d') }} {{ $monthName }} {{ $payroll->year }}<br>
                Disetujui oleh,<br><br><br><br>
                <span class="sig-name">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><br>
                <span class="sig-title">HRD</span><br>
                <span class="sig-title">Admin SDM</span>
            </td>
        </tr>
    </table>

    <div class="confidential">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</div>

    <div class="footer">
        Dicetak {{ now()->format('d/m/Y H:i') }} | RS Kartika Husada Setu by:{{ $payroll->user->name }}
    </div>
</body>
</html>
