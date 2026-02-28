<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a2e; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #0f3460; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #0f3460; margin-bottom: 3px; }
        .header h2 { font-size: 14px; color: #16213e; font-weight: normal; }
        .header p { font-size: 10px; color: #666; }
        .slip-title { text-align: center; background: #0f3460; color: white; padding: 8px; font-size: 14px; font-weight: bold; margin-bottom: 15px; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px 5px; }
        .info-table .label { font-weight: bold; width: 160px; color: #333; }
        .section-title { background: #e8eef7; padding: 6px 10px; font-weight: bold; color: #0f3460; margin: 10px 0 5px; font-size: 12px; }
        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .detail-table th, .detail-table td { padding: 5px 10px; border-bottom: 1px solid #ddd; }
        .detail-table th { background: #f0f4f8; text-align: left; font-weight: bold; }
        .detail-table .amount { text-align: right; font-family: monospace; }
        .total-row { background: #e8eef7; font-weight: bold; }
        .net-salary { background: #0f3460; color: white; padding: 10px; font-size: 14px; display: flex; justify-content: space-between; margin-top: 10px; }
        .net-salary-table { width: 100%; background: #0f3460; color: white; padding: 10px; font-size: 14px; margin-top: 15px; }
        .net-salary-table td { padding: 5px 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .signatures { margin-top: 40px; width: 100%; }
        .signatures td { text-align: center; padding-top: 60px; width: 33%; }
        .confidential { text-align: center; color: #e74c3c; font-size: 9px; font-style: italic; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 RUMAH SAKIT SEHAT SEJAHTERA</h1>
        <h2>Jl. Kesehatan No. 123, Jakarta</h2>
        <p>Telp: (021) 1234567 | Email: info@rs-sehatsejahtera.co.id</p>
    </div>

    <div class="slip-title">SLIP GAJI KARYAWAN</div>

    <table class="info-table">
        <tr>
            <td class="label">Periode</td>
            <td>: {{ $monthName }} {{ $payroll->year }}</td>
            <td class="label">ID Karyawan</td>
            <td>: {{ $payroll->user->employee_id }}</td>
        </tr>
        <tr>
            <td class="label">Nama Karyawan</td>
            <td>: {{ $payroll->user->name }}</td>
            <td class="label">Departemen</td>
            <td>: {{ $payroll->user->department }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td>: {{ $payroll->user->position }}</td>
            <td class="label">NPWP</td>
            <td>: {{ $payroll->user->npwp ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">📅 REKAP KEHADIRAN</div>
    <table class="detail-table">
        <tr>
            <td>Total Hari Kerja</td>
            <td class="amount">{{ $payroll->total_work_days }} hari</td>
            <td>Hari Hadir</td>
            <td class="amount">{{ $payroll->present_days }} hari</td>
        </tr>
        <tr>
            <td>Terlambat</td>
            <td class="amount">{{ $payroll->late_days }} hari</td>
            <td>Tidak Hadir</td>
            <td class="amount">{{ $payroll->absent_days }} hari</td>
        </tr>
        <tr>
            <td>Cuti</td>
            <td class="amount">{{ $payroll->leave_days }} hari</td>
            <td>Sakit</td>
            <td class="amount">{{ $payroll->sick_days }} hari</td>
        </tr>
        <tr>
            <td>Jam Lembur</td>
            <td class="amount">{{ $payroll->overtime_hours }} jam</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <div class="section-title">💰 PENDAPATAN</div>
    <table class="detail-table">
        <tr>
            <td>Gaji Pokok</td>
            <td class="amount">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Jabatan</td>
            <td class="amount">Rp {{ number_format($payroll->position_allowance, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Makan ({{ $payroll->present_days }} hari)</td>
            <td class="amount">Rp {{ number_format($payroll->meal_allowance, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Transport ({{ $payroll->present_days }} hari)</td>
            <td class="amount">Rp {{ number_format($payroll->transport_allowance, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Uang Lembur ({{ $payroll->overtime_hours }} jam)</td>
            <td class="amount">Rp {{ number_format($payroll->overtime_pay, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total Pendapatan</strong></td>
            <td class="amount"><strong>Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <div class="section-title">📉 POTONGAN</div>
    <table class="detail-table">
        <tr>
            <td>BPJS Kesehatan (1%)</td>
            <td class="amount">Rp {{ number_format($payroll->bpjs_kesehatan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>BPJS Ketenagakerjaan / JHT (2%)</td>
            <td class="amount">Rp {{ number_format($payroll->bpjs_ketenagakerjaan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>BPJS Pensiun (1%)</td>
            <td class="amount">Rp {{ number_format($payroll->bpjs_pensiun, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>PPh 21</td>
            <td class="amount">Rp {{ number_format($payroll->pph21, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Potongan Ketidakhadiran & Keterlambatan</td>
            <td class="amount">Rp {{ number_format($payroll->absence_deduction, 0, ',', '.') }}</td>
        </tr>
        @if($payroll->other_deduction > 0)
        <tr>
            <td>Potongan Lainnya</td>
            <td class="amount">Rp {{ number_format($payroll->other_deduction, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td><strong>Total Potongan</strong></td>
            <td class="amount"><strong>Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="net-salary-table">
        <tr>
            <td><strong>GAJI BERSIH (Take Home Pay)</strong></td>
            <td style="text-align: right;"><strong>Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</strong></td>
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
                <strong>Admin SDM</strong><br>
                <small>HRD</small>
            </td>
        </tr>
    </table>

    <div class="confidential">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} | RS Sehat Sejahtera - Sistem Penggajian
    </div>
</body>
</html>
