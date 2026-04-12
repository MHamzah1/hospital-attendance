<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        @page { size: A4 portrait; margin: 30mm 20mm 15mm 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #1a1a2e; }

        /* Header - logo beside text, centered */
        .header { border-bottom: 2px double #0f3460; padding-bottom: 8px; margin-bottom: 5px; margin-top: 10px; padding-top: 8px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; text-align: center; }
        .header-logo { width: 70px; text-align: right; padding-right: 12px; }
        .header-logo img { height: 60px; }
        .header-text { text-align: left; }
        .header-text h1 { font-size: 18px; color: #0f3460; margin-bottom: 3px; letter-spacing: 0.5px; }
        .header-text p { font-size: 9px; color: #333; }

        /* Info Karyawan */
        .info-box { width: 100%; border: 1px solid #0f3460; border-collapse: collapse; margin-bottom: 5px; }
        .info-box td { padding: 1.5px 5px; font-size: 8px; border: 0.5px solid #ccc; }
        .info-box .label { font-weight: bold; color: #0f3460; width: 105px; text-transform: uppercase; font-size: 7px; }
        .info-box .separator { width: 8px; text-align: center; }

        /* Section titles */
        .section-title { background: #0f3460; color: white; padding: 2px 8px; font-weight: bold; font-size: 8px; }
        .sub-section { background: #e8eef7; padding: 1.5px 8px; font-weight: bold; color: #0f3460; font-size: 7.5px; border-bottom: 1px solid #ccc; }

        /* Detail tables - fixed column widths for alignment */
        .detail-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .detail-table td { padding: 1.5px 5px; font-size: 7.5px; border-bottom: 0.5px solid #eee; overflow: hidden; }
        .detail-table .col-label { width: auto; }
        .detail-table .col-rp { width: 22px; text-align: right; font-family: monospace; font-size: 7.5px; }
        .detail-table .col-amount { width: 75px; text-align: right; font-family: monospace; font-size: 7.5px; white-space: nowrap; }

        /* Total rows */
        .total-row { background: #e8eef7; font-weight: bold; }
        .total-row td { border-top: 1px solid #0f3460; border-bottom: 1px solid #0f3460; padding: 2px 5px; }

        /* Net salary */
        .net-salary-table { width: 100%; background: #0f3460; color: white; margin-top: 4px; }
        .net-salary-table td { padding: 4px 10px; font-size: 11px; font-weight: bold; }

        /* Attendance */
        .attendance-box { border: 1px solid #0f3460; margin-bottom: 5px; }
        .attendance-table { width: 100%; border-collapse: collapse; }
        .attendance-table td { padding: 1.5px 5px; border: 0.5px solid #ccc; font-size: 7.5px; text-align: center; }
        .attendance-table .att-label { background: #f0f4f8; font-weight: bold; text-align: left; }

        /* Signatures */
        .signatures { margin-top: 8px; width: 100%; }
        .signatures td { text-align: center; width: 50%; font-size: 8px; vertical-align: top; }
        .sig-name { font-weight: bold; border-bottom: 1px solid #333; display: inline-block; padding-bottom: 1px; min-width: 130px; }
        .sig-title { font-size: 7px; color: #555; }
        /* ==> UBAH UKURAN TANDA TANGAN DISINI (height) <== */
        .sig-img { height: 80px; margin: 1px 0; }
        .sig-space { height: 80px; }

        .confidential { text-align: center; color: #e74c3c; font-size: 6.5px; font-style: italic; margin-top: 3px; }
        .footer { margin-top: 3px; text-align: center; font-size: 6.5px; color: #999; border-top: 1px solid #ddd; padding-top: 2px; }

        .col-wrapper { border: 1px solid #0f3460; margin-bottom: 4px; }
    </style>
</head>
<body>
    @php
        $formatHours = function($hours) {
            $totalSeconds = abs(round($hours * 3600));
            $h = floor($totalSeconds / 3600);
            $m = floor(($totalSeconds % 3600) / 60);
            $s = $totalSeconds % 60;
            return "{$h} jam {$m} menit {$s} detik";
        };
        $formatMinutes = function($minutes) {
            $totalSeconds = abs(round($minutes * 60));
            $h = floor($totalSeconds / 3600);
            $m = floor(($totalSeconds % 3600) / 60);
            $s = $totalSeconds % 60;
            return "{$h} jam {$m} menit {$s} detik";
        };
    @endphp

    {{-- Header - logo di samping kop --}}
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
            <td class="label">NO. BPJS KES</td>
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
            <td class="label">TGL MASUK</td>
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
            <col class="col-label"><col class="col-rp"><col class="col-amount">
            <tr><td>Gaji Pokok</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td></tr>
            <tr><td>Tunjangan Jabatan</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->position_allowance ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Tunjangan Fungsional</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->functional_allowance ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Tunjangan Khusus</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->special_allowance ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Tunjangan Makan</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->meal_allowance ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Tunjangan Transport</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->transport_allowance ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Tunjangan Kehadiran</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->attendance_allowance ?? 0, 0, ',', '.') }}</td></tr>
            <tr class="total-row"><td><strong>BRUTO</strong></td><td class="col-rp"><strong>Rp</strong></td><td class="col-amount"><strong>{{ number_format($payroll->gross_salary, 0, ',', '.') }}</strong></td></tr>
        </table>
        <div class="sub-section">Lembur</div>
        <table class="detail-table">
            <col class="col-label"><col class="col-rp"><col class="col-amount">
            <tr><td>Lembur</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->overtime_hourly ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>On Call</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->overtime_on_call ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>MOD</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->overtime_mod ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Hari Raya</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->overtime_holiday ?? 0, 0, ',', '.') }}</td></tr>
            @php
                $totalLembur = ($payroll->overtime_hourly ?? 0) + ($payroll->overtime_on_call ?? 0)
                    + ($payroll->overtime_mod ?? 0) + ($payroll->overtime_holiday ?? 0);
            @endphp
            <tr class="total-row"><td><strong>Total Lembur</strong></td><td class="col-rp"><strong>Rp</strong></td><td class="col-amount"><strong>{{ number_format($totalLembur, 0, ',', '.') }}</strong></td></tr>
        </table>
        <div class="sub-section">Tambahan Lainnya</div>
        <table class="detail-table">
            <col class="col-label"><col class="col-rp"><col class="col-amount">
            <tr><td>Koreksi Upah (+)</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->salary_correction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Lain-lain (+)</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->other_allowance ?? 0, 0, ',', '.') }}</td></tr>
            @php
                $totalPendapatan = $payroll->gross_salary
                    + ($payroll->overtime_hourly ?? 0) + ($payroll->overtime_on_call ?? 0)
                    + ($payroll->overtime_mod ?? 0) + ($payroll->overtime_holiday ?? 0)
                    + ($payroll->salary_correction ?? 0) + ($payroll->other_allowance ?? 0);
            @endphp
            <tr class="total-row"><td><strong>TOTAL PENDAPATAN</strong></td><td class="col-rp"><strong>Rp</strong></td><td class="col-amount"><strong>{{ number_format($totalPendapatan, 0, ',', '.') }}</strong></td></tr>
        </table>
    </div>

    {{-- POTONGAN (full width, below pendapatan) --}}
    <div class="col-wrapper">
        <div class="section-title">POTONGAN</div>
        <div class="sub-section">BPJS & Pajak</div>
        <table class="detail-table">
            <col class="col-label"><col class="col-rp"><col class="col-amount">
            <tr><td>BPJS Kesehatan (1%)</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->bpjs_kesehatan, 0, ',', '.') }}</td></tr>
            <tr><td>BPJS TK - JHT (2%)</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->bpjs_ketenagakerjaan, 0, ',', '.') }}</td></tr>
            <tr><td>BPJS TK - JP (1%)</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->bpjs_pensiun_jp ?? $payroll->bpjs_pensiun ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>PPh 21</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->pph21 ?? 0, 0, ',', '.') }}</td></tr>
        </table>
        <div class="sub-section">Potongan Admin</div>
        <table class="detail-table">
            <col class="col-label"><col class="col-rp"><col class="col-amount">
            <tr><td>CDT</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->cdt_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Alpha / Ketidakhadiran</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->alpha_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Cashbond</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->cashbond_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Piutang Obat</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->piutang_obat_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Koreksi Upah (-)</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->salary_correction_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Adm. Bank</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->bank_admin_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr><td>Potongan Lainnya</td><td class="col-rp">Rp</td><td class="col-amount">{{ number_format($payroll->other_deduction ?? 0, 0, ',', '.') }}</td></tr>
            <tr class="total-row"><td><strong>TOTAL POTONGAN</strong></td><td class="col-rp"><strong>Rp</strong></td><td class="col-amount"><strong>{{ number_format($payroll->total_deduction, 0, ',', '.') }}</strong></td></tr>
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
                Diterima oleh,
                <div class="sig-space"></div>
                <span class="sig-name">{{ $payroll->user->name }}</span><br>
                <span class="sig-title">Karyawan</span>
            </td>
            <td style="text-align: center;">
                <div style="font-size: 8px; margin-bottom: 4px;">Bekasi, {{ now()->format('d') }} {{ $monthName }} {{ $payroll->year }}</div>
                Disetujui oleh,
                @php
                    $qrText = 'Dokumen ini telah di verifikasi oleh Payroll RS Kartika Husada Setu';
                    $qrOptions = new \chillerlan\QRCode\QROptions([
                        'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                        'scale' => 5,
                        'quietzoneSize' => 1,
                        'outputBase64' => true,
                    ]);
                    $qrBase64 = (new \chillerlan\QRCode\QRCode($qrOptions))->render($qrText);
                @endphp
                <div style="text-align: center; padding: 5px 0;">
                    <img src="{{ $qrBase64 }}" style="width: 80px; height: 80px;" alt="QR Verification">
                </div>
                <span class="sig-name">Yanuwar Syawaludin, S.I.A.P</span><br>
                <span class="sig-title">HRD / Admin SDM</span>
            </td>
        </tr>
    </table>

    <div class="confidential">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</div>
    <div class="footer">Dicetak {{ now()->format('d/m/Y H:i') }} | RS Kartika Husada Setu by:{{ $payroll->user->name }}</div>
</body>
</html>
