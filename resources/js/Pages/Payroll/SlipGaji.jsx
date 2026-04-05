import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function SlipGaji({ payroll }) {
    const months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
    const p = payroll;
    const u = payroll.user;

    const totalOvertimeOther = Number(p.overtime_hourly || 0) + Number(p.overtime_night || 0)
        + Number(p.overtime_shift || 0) + Number(p.overtime_on_call || 0)
        + Number(p.overtime_mod || 0) + Number(p.overtime_holiday || 0);
    const totalPendapatan = Number(p.gross_salary || 0) + totalOvertimeOther
        + Number(p.salary_correction || 0) + Number(p.other_allowance || 0);

    return (
        <AuthenticatedLayout header="Slip Gaji">
            <Head title="Slip Gaji Detail" />

            <div className="max-w-4xl mx-auto">
                <div className="flex items-center justify-between mb-6">
                    <Link href="/payroll" className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                        Kembali
                    </Link>
                    <div className="flex gap-2">
                        <a href={`/payroll/${p.id}/export-pdf`} target="_blank" rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-red-500/30">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            Export PDF
                        </a>
                        <a href={`/payroll/${p.id}/export-excel`} target="_blank" rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-emerald-600/30">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export Excel
                        </a>
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden shadow-sm" id="slip-gaji">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-[#0f2027] via-[#203a43] to-[#2c5364] p-8 text-white text-center">
                        <img src="/logo.png" alt="Logo" className="h-14 mx-auto mb-2" onError={(e) => { e.target.style.display = 'none'; }} />
                        <h1 className="text-xl font-bold tracking-wide">RUMAH SAKIT KARTIKA HUSADA SETU</h1>
                        <p className="text-slate-300 text-sm mt-1">Jl. Raya Serang - Cibarusah KM.29, Setu, Bekasi</p>
                        <div className="mt-4 inline-block bg-white/10 backdrop-blur px-6 py-2 rounded-full">
                            <p className="text-sm font-semibold">SLIP GAJI KARYAWAN — {months[p.month]?.toUpperCase()} {p.year}</p>
                        </div>
                    </div>

                    {/* Employee Info */}
                    <div className="grid grid-cols-2 gap-6 p-6 bg-slate-50/50 border-b border-slate-200/60">
                        <div className="space-y-2">
                            <InfoRow label="NIP" value={u?.nip || u?.employee_id} />
                            <InfoRow label="Nama Karyawan" value={u?.name} />
                            <InfoRow label="Jabatan" value={u?.position} />
                        </div>
                        <div className="space-y-2">
                            <InfoRow label="Unit / Dept." value={u?.department} />
                            <InfoRow label="Periode" value={`${months[p.month]} ${p.year}`} />
                        </div>
                    </div>

                    {/* Attendance Summary */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-sm">📅</span>
                            Rekap Kehadiran
                        </h3>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <AttendanceCard label="Hari Kerja" value={p.total_work_days} unit="hari" color="bg-slate-50" />
                            <AttendanceCard label="Hadir" value={p.present_days} unit="hari" color="bg-emerald-50 text-emerald-700" />
                            <AttendanceCard label="Terlambat" value={p.late_days} unit="hari" color="bg-amber-50 text-amber-700" />
                            <AttendanceCard label="Cuti" value={p.leave_days} unit="hari" color="bg-blue-50 text-blue-700" />
                            <AttendanceCard label="Sakit" value={p.sick_days} unit="hari" color="bg-orange-50 text-orange-700" />
                            <AttendanceCard label="Tidak Hadir" value={p.absent_days} unit="hari" color="bg-red-50 text-red-700" />
                        </div>
                    </div>

                    {/* Two-column: Pendapatan + Potongan */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-0 divide-x divide-slate-200/60">
                        {/* Left: Pendapatan */}
                        <div className="p-6">
                            <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <span className="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-sm">💰</span>
                                Pendapatan
                            </h3>
                            <div className="space-y-1.5">
                                <SalaryRow label="Gaji Pokok" value={p.base_salary} />
                                {Number(p.position_allowance) > 0 && <SalaryRow label="Tunj. Jabatan" value={p.position_allowance} />}
                                {Number(p.functional_allowance) > 0 && <SalaryRow label="Tunj. Fungsional" value={p.functional_allowance} />}
                                {Number(p.special_allowance) > 0 && <SalaryRow label="Tunj. Khusus" value={p.special_allowance} />}
                                {Number(p.meal_allowance) > 0 && <SalaryRow label="Tunj. Makan" value={p.meal_allowance} />}
                                {Number(p.transport_allowance) > 0 && <SalaryRow label="Tunj. Transport" value={p.transport_allowance} />}
                                {Number(p.attendance_allowance) > 0 && <SalaryRow label="Tunj. Kehadiran" value={p.attendance_allowance} />}
                                <div className="flex justify-between items-center pt-2 mt-2 border-t-2 border-emerald-200">
                                    <span className="font-bold text-slate-800 text-sm">BRUTO</span>
                                    <span className="font-bold text-emerald-600">Rp {fmt(p.gross_salary)}</span>
                                </div>

                                {/* Lembur & Tambahan */}
                                {Number(p.overtime_hourly) > 0 && <SalaryRow label="Lembur Jam" value={p.overtime_hourly} />}
                                {Number(p.overtime_night) > 0 && <SalaryRow label="Lembur Malam" value={p.overtime_night} />}
                                {Number(p.overtime_shift) > 0 && <SalaryRow label="Lembur Shift" value={p.overtime_shift} />}
                                {Number(p.overtime_on_call) > 0 && <SalaryRow label="Lembur On Call" value={p.overtime_on_call} />}
                                {Number(p.overtime_mod) > 0 && <SalaryRow label="Lembur MOD" value={p.overtime_mod} />}
                                {Number(p.overtime_holiday) > 0 && <SalaryRow label="Lembur Hari Raya" value={p.overtime_holiday} />}
                                {Number(p.salary_correction) > 0 && <SalaryRow label="Koreksi Upah (+)" value={p.salary_correction} />}
                                {Number(p.other_allowance) > 0 && <SalaryRow label="Lain-lain (+)" value={p.other_allowance} />}

                                <div className="flex justify-between items-center pt-2 mt-2 border-t-2 border-emerald-300">
                                    <span className="font-bold text-slate-800 text-sm">TOTAL PENDAPATAN</span>
                                    <span className="font-bold text-lg text-emerald-600">Rp {fmt(totalPendapatan)}</span>
                                </div>
                            </div>
                        </div>

                        {/* Right: Potongan */}
                        <div className="p-6">
                            <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <span className="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center text-sm">📉</span>
                                Potongan
                            </h3>
                            <div className="space-y-1.5">
                                <SalaryRow label="CDT" value={p.cdt_deduction} isDeduction />
                                <SalaryRow label="Alpa" value={p.alpha_deduction} isDeduction />
                                {Number(p.cashbond_deduction) > 0 && <SalaryRow label="Cashbond" value={p.cashbond_deduction} isDeduction />}
                                {Number(p.piutang_obat_deduction) > 0 && <SalaryRow label="Piutang Obat" value={p.piutang_obat_deduction} isDeduction />}
                                {Number(p.salary_correction_deduction) > 0 && <SalaryRow label="Koreksi Upah (-)" value={p.salary_correction_deduction} isDeduction />}
                                {Number(p.bank_admin_deduction) > 0 && <SalaryRow label="Adm. Bank" value={p.bank_admin_deduction} isDeduction />}
                                {Number(p.pph21) > 0 && <SalaryRow label="PPh 21" value={p.pph21} isDeduction />}
                                {Number(p.other_deduction) > 0 && <SalaryRow label="Potongan Lainnya" value={p.other_deduction} isDeduction />}
                                <SalaryRow label="BPJS Kesehatan (1%)" value={p.bpjs_kesehatan} isDeduction />
                                <SalaryRow label="BPJS TK JHT (2%)" value={p.bpjs_ketenagakerjaan} isDeduction />
                                <SalaryRow label="BPJS TK JP (1%)" value={p.bpjs_pensiun_jp || p.bpjs_pensiun} isDeduction />
                                <div className="flex justify-between items-center pt-2 mt-2 border-t-2 border-red-200">
                                    <span className="font-bold text-slate-800 text-sm">TOTAL POTONGAN</span>
                                    <span className="font-bold text-lg text-red-600">- Rp {fmt(p.total_deduction)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Net Salary */}
                    <div className="p-6 bg-gradient-to-r from-emerald-500 to-emerald-600">
                        <div className="flex items-center justify-between text-white">
                            <div>
                                <p className="text-emerald-100 text-sm font-medium uppercase">Gaji Dibayarkan (Take Home Pay)</p>
                                <p className="text-3xl font-extrabold mt-1">Rp {fmt(p.net_salary)}</p>
                            </div>
                            <div className="text-5xl opacity-30">💵</div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-6 bg-slate-50/50 text-center">
                        <p className="text-xs text-slate-400">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</p>
                        <p className="text-xs text-slate-400 mt-1">RS Kartika Husada Setu — Sistem Penggajian</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function InfoRow({ label, value }) {
    return (
        <div className="flex text-sm">
            <span className="w-40 text-slate-500 font-medium">{label}</span>
            <span className="text-slate-800 font-semibold">: {value}</span>
        </div>
    );
}

function AttendanceCard({ label, value, unit, color }) {
    return (
        <div className={`${color} rounded-xl p-3 text-center`}>
            <p className="text-xl font-extrabold">{value}</p>
            <p className="text-xs font-medium mt-0.5">{label} ({unit})</p>
        </div>
    );
}

function SalaryRow({ label, value, isDeduction = false }) {
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
    return (
        <div className="flex justify-between items-center py-1.5">
            <span className="text-sm text-slate-600">{label}</span>
            <span className={`text-sm font-mono font-medium ${isDeduction ? 'text-red-600' : 'text-slate-700'}`}>
                {isDeduction ? '- ' : ''}Rp {fmt(value)}
            </span>
        </div>
    );
}
