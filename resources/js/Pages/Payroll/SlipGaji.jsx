import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function SlipGaji({ payroll }) {
    const months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
    const p = payroll;
    const u = payroll.user;

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
                        <div className="text-3xl mb-2">🏥</div>
                        <h1 className="text-xl font-bold tracking-wide">RUMAH SAKIT SEHAT SEJAHTERA</h1>
                        <p className="text-slate-300 text-sm mt-1">Jl. Kesehatan No. 123, Jakarta</p>
                        <div className="mt-4 inline-block bg-white/10 backdrop-blur px-6 py-2 rounded-full">
                            <p className="text-sm font-semibold">SLIP GAJI KARYAWAN</p>
                        </div>
                    </div>

                    {/* Employee Info */}
                    <div className="grid grid-cols-2 gap-6 p-6 bg-slate-50/50 border-b border-slate-200/60">
                        <div className="space-y-2">
                            <InfoRow label="Periode" value={`${months[p.month]} ${p.year}`} />
                            <InfoRow label="Nama Karyawan" value={u?.name} />
                            <InfoRow label="Jabatan" value={u?.position} />
                        </div>
                        <div className="space-y-2">
                            <InfoRow label="ID Karyawan" value={u?.employee_id} />
                            <InfoRow label="Departemen" value={u?.department} />
                            <InfoRow label="NPWP" value={u?.npwp || '-'} />
                        </div>
                    </div>

                    {/* Attendance Summary */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-sm">📅</span>
                            Rekap Kehadiran
                        </h3>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <AttendanceCard label="Hari Kerja" value={p.total_work_days} unit="hari" color="bg-slate-50" />
                            <AttendanceCard label="Hadir" value={p.present_days} unit="hari" color="bg-emerald-50 text-emerald-700" />
                            <AttendanceCard label="Terlambat" value={p.late_days} unit="hari" color="bg-amber-50 text-amber-700" />
                            <AttendanceCard label="Tidak Hadir" value={p.absent_days} unit="hari" color="bg-red-50 text-red-700" />
                            <AttendanceCard label="Cuti" value={p.leave_days} unit="hari" color="bg-blue-50 text-blue-700" />
                            <AttendanceCard label="Sakit" value={p.sick_days} unit="hari" color="bg-orange-50 text-orange-700" />
                            <AttendanceCard label="Lembur" value={p.overtime_hours} unit="jam" color="bg-purple-50 text-purple-700" />
                        </div>
                    </div>

                    {/* Income */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-sm">💰</span>
                            Pendapatan
                        </h3>
                        <div className="space-y-2">
                            <SalaryRow label="Gaji Pokok" value={p.base_salary} />
                            <SalaryRow label="Tunjangan Jabatan" value={p.position_allowance} />
                            <SalaryRow label={`Tunjangan Makan (${p.present_days} hari)`} value={p.meal_allowance} />
                            <SalaryRow label={`Tunjangan Transport (${p.present_days} hari)`} value={p.transport_allowance} />
                            <SalaryRow label={`Uang Lembur (${p.overtime_hours} jam)`} value={p.overtime_pay} />
                            {Number(p.other_allowance) > 0 && <SalaryRow label="Tunjangan Lainnya" value={p.other_allowance} />}
                            <div className="flex justify-between items-center pt-3 mt-3 border-t-2 border-emerald-200">
                                <span className="font-bold text-slate-800">Total Pendapatan</span>
                                <span className="font-bold text-lg text-emerald-600">Rp {fmt(p.gross_salary)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Deductions */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center text-sm">📉</span>
                            Potongan
                        </h3>
                        <div className="space-y-2">
                            <SalaryRow label="BPJS Kesehatan (1%)" value={p.bpjs_kesehatan} isDeduction />
                            <SalaryRow label="BPJS Ketenagakerjaan / JHT (2%)" value={p.bpjs_ketenagakerjaan} isDeduction />
                            <SalaryRow label="BPJS Pensiun (1%)" value={p.bpjs_pensiun} isDeduction />
                            <SalaryRow label="PPh 21" value={p.pph21} isDeduction />
                            <SalaryRow label="Potongan Ketidakhadiran & Keterlambatan" value={p.absence_deduction} isDeduction />
                            {Number(p.other_deduction) > 0 && <SalaryRow label="Potongan Lainnya" value={p.other_deduction} isDeduction />}
                            <div className="flex justify-between items-center pt-3 mt-3 border-t-2 border-red-200">
                                <span className="font-bold text-slate-800">Total Potongan</span>
                                <span className="font-bold text-lg text-red-600">- Rp {fmt(p.total_deduction)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Net Salary */}
                    <div className="p-6 bg-gradient-to-r from-emerald-500 to-emerald-600">
                        <div className="flex items-center justify-between text-white">
                            <div>
                                <p className="text-emerald-100 text-sm font-medium uppercase">Gaji Bersih (Take Home Pay)</p>
                                <p className="text-3xl font-extrabold mt-1">Rp {fmt(p.net_salary)}</p>
                            </div>
                            <div className="text-5xl opacity-30">💵</div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-6 bg-slate-50/50 text-center">
                        <p className="text-xs text-slate-400">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</p>
                        <p className="text-xs text-slate-400 mt-1">RS Sehat Sejahtera — Sistem Penggajian</p>
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
