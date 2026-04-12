import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function SlipGaji({ payroll, cutiInfo }) {
    const months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
    const p = payroll;
    const u = payroll.user;
    const ci = cutiInfo || { jatah_cuti: 12, cuti_terpakai: 0, sisa_cuti: 12 };

    const totalOvertimeOther = Number(p.overtime_hourly || 0) + Number(p.overtime_on_call || 0)
        + Number(p.overtime_mod || 0) + Number(p.overtime_holiday || 0);
    const totalPendapatan = Number(p.gross_salary || 0) + totalOvertimeOther
        + Number(p.salary_correction || 0) + Number(p.other_allowance || 0);

    // Format decimal hours to "X jam Y menit Z detik"
    const fmtHours = (hours) => {
        const totalSec = Math.abs(Math.round((hours || 0) * 3600));
        const h = Math.floor(totalSec / 3600);
        const m = Math.floor((totalSec % 3600) / 60);
        const s = totalSec % 60;
        return `${h} jam ${m} menit ${s} detik`;
    };
    // Format minutes to "X jam Y menit Z detik"
    const fmtMinutes = (minutes) => {
        const totalSec = Math.abs(Math.round((minutes || 0) * 60));
        const h = Math.floor(totalSec / 3600);
        const m = Math.floor((totalSec % 3600) / 60);
        const s = totalSec % 60;
        return `${h} jam ${m} menit ${s} detik`;
    };

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
                        <Link href={`/payroll/${p.id}/export-react-pdf`}
                            className="inline-flex items-center gap-2 bg-violet-500 hover:bg-violet-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-violet-500/30">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export PDF (React)
                        </Link>
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

                    {/* Employee Info - bordered table format like image 2 */}
                    <div className="p-6 bg-slate-50/50 border-b border-slate-200/60">
                        <div className="border border-slate-300 rounded-lg overflow-hidden">
                            <table className="w-full text-sm">
                                <tbody className="divide-y divide-slate-200">
                                    <tr>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 w-36 uppercase text-xs">Nama Pegawai</td>
                                        <td className="px-1 py-2 text-slate-400 w-4">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.name}</td>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 w-36 uppercase text-xs">Periode</td>
                                        <td className="px-1 py-2 text-slate-400 w-4">:</td>
                                        <td className="px-3 py-2 text-slate-800">{months[p.month]} {p.year}</td>
                                    </tr>
                                    <tr>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 uppercase text-xs">NIP</td>
                                        <td className="px-1 py-2 text-slate-400">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.nip || u?.employee_id}</td>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 uppercase text-xs">No. BPJS Kesehatan</td>
                                        <td className="px-1 py-2 text-slate-400">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.bpjs_kesehatan || '-'}</td>
                                    </tr>
                                    <tr>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 uppercase text-xs">Jabatan</td>
                                        <td className="px-1 py-2 text-slate-400">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.position || '-'}</td>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 uppercase text-xs">No. BPJS TK</td>
                                        <td className="px-1 py-2 text-slate-400">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.bpjs_ketenagakerjaan || '-'}</td>
                                    </tr>
                                    <tr>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 uppercase text-xs">Tanggal Masuk</td>
                                        <td className="px-1 py-2 text-slate-400">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.join_date ? new Date(u.join_date).toLocaleDateString('id-ID') : '-'}</td>
                                        <td className="px-3 py-2 font-bold text-slate-600 bg-slate-50 uppercase text-xs">No. NPWP</td>
                                        <td className="px-1 py-2 text-slate-400">:</td>
                                        <td className="px-3 py-2 text-slate-800">{u?.npwp || '-'}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Attendance Summary */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-sm">📅</span>
                            Rekap Kehadiran
                        </h3>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <AttendanceCard label="Hadir" value={p.present_days} unit="hari" color="bg-emerald-50 text-emerald-700" />
                            <AttendanceCard label="Terlambat" value={fmtMinutes(p.late_minutes)} unit="" color="bg-amber-50 text-amber-700" isText />
                            <AttendanceCard label="Tidak Hadir" value={p.absent_days} unit="hari" color="bg-red-50 text-red-700" />
                            <AttendanceCard label="Cuti" value={p.leave_days} unit="hari" color="bg-blue-50 text-blue-700" />
                            <AttendanceCard label="Sakit" value={p.sick_days} unit="hari" color="bg-orange-50 text-orange-700" />
                            <AttendanceCard label="Lembur" value={fmtHours(p.overtime_hours)} unit="" color="bg-purple-50 text-purple-700" isText />
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-3">
                            <AttendanceCard label="Jatah Cuti" value={ci.jatah_cuti} unit="hari" color="bg-indigo-50 text-indigo-700" />
                            <AttendanceCard label="Cuti Terpakai" value={ci.cuti_terpakai} unit="hari" color="bg-pink-50 text-pink-700" />
                            <AttendanceCard label="Sisa Cuti" value={ci.sisa_cuti} unit="hari" color="bg-teal-50 text-teal-700" />
                        </div>
                    </div>

                    {/* Pendapatan */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-2 flex items-center gap-2">
                            <span className="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-sm">💰</span>
                            Pendapatan
                        </h3>
                        <p className="text-xs font-bold text-slate-500 uppercase mb-2 pb-1 border-b border-slate-200">Gaji & Tunjangan</p>
                        <div className="space-y-1.5">
                            <SalaryRow label="Gaji Pokok" value={p.base_salary} />
                            <SalaryRow label="Tunj. Jabatan" value={p.position_allowance} />
                            <SalaryRow label="Tunj. Fungsional" value={p.functional_allowance} />
                            <SalaryRow label="Tunj. Khusus" value={p.special_allowance} />
                            <SalaryRow label="Tunj. Makan" value={p.meal_allowance} />
                            <SalaryRow label="Tunj. Transport" value={p.transport_allowance} />
                            <SalaryRow label="Tunj. Kehadiran" value={p.attendance_allowance} />
                            <div className="flex justify-between items-center pt-2 mt-2 border-t-2 border-emerald-200">
                                <span className="font-bold text-slate-800 text-sm">BRUTO</span>
                                <span className="font-bold text-emerald-600">Rp {fmt(p.gross_salary)}</span>
                            </div>

                            {/* Lembur */}
                            <p className="text-xs font-bold text-slate-500 uppercase mt-3 mb-2 pb-1 border-b border-slate-200">Lembur</p>
                            <SalaryRow label="Lembur" value={p.overtime_hourly} />
                            <SalaryRow label="On Call" value={p.overtime_on_call} />
                            <SalaryRow label="MOD" value={p.overtime_mod} />
                            <SalaryRow label="Hari Raya" value={p.overtime_holiday} />

                            {/* Tambahan */}
                            <p className="text-xs font-bold text-slate-500 uppercase mt-3 mb-2 pb-1 border-b border-slate-200">Tambahan Lainnya</p>
                            <SalaryRow label="Koreksi Upah (+)" value={p.salary_correction} />
                            <SalaryRow label="Lain-lain (+)" value={p.other_allowance} />

                            <div className="flex justify-between items-center pt-2 mt-2 border-t-2 border-emerald-300">
                                <span className="font-bold text-slate-800 text-sm">TOTAL PENDAPATAN</span>
                                <span className="font-bold text-lg text-emerald-600">Rp {fmt(totalPendapatan)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Potongan */}
                    <div className="p-6 border-b border-slate-200/60">
                        <h3 className="font-bold text-slate-800 mb-2 flex items-center gap-2">
                            <span className="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center text-sm">📉</span>
                            Potongan
                        </h3>
                        <p className="text-xs font-bold text-slate-500 uppercase mb-2 pb-1 border-b border-slate-200">BPJS & Pajak</p>
                        <div className="space-y-1.5">
                            <SalaryRow label="BPJS Kesehatan (1%)" value={p.bpjs_kesehatan} isDeduction />
                            <SalaryRow label="BPJS TK - JHT (2%)" value={p.bpjs_ketenagakerjaan} isDeduction />
                            <SalaryRow label="BPJS TK - JP (1%)" value={p.bpjs_pensiun_jp || p.bpjs_pensiun} isDeduction />
                            <SalaryRow label="PPh 21" value={p.pph21} isDeduction />
                            
                            <p className="text-xs font-bold text-slate-500 uppercase mt-3 mb-2 pb-1 border-b border-slate-200">Potongan Admin</p>
                            <SalaryRow label="CDT" value={p.cdt_deduction} isDeduction />
                            <SalaryRow label="Alpha / Ketidakhadiran" value={p.alpha_deduction} isDeduction />
                            <SalaryRow label="Cashbond" value={p.cashbond_deduction} isDeduction />
                            <SalaryRow label="Piutang Obat" value={p.piutang_obat_deduction} isDeduction />
                            <SalaryRow label="Koreksi Upah (-)" value={p.salary_correction_deduction} isDeduction />
                            <SalaryRow label="Adm. Bank" value={p.bank_admin_deduction} isDeduction />
                            <SalaryRow label="Potongan Lainnya" value={p.other_deduction} isDeduction />
                            <div className="flex justify-between items-center pt-2 mt-2 border-t-2 border-red-200">
                                <span className="font-bold text-slate-800 text-sm">TOTAL POTONGAN</span>
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

                    {/* Signatures */}
                    <div className="p-6 grid grid-cols-2 gap-6">
                        <div className="text-center">
                            <p className="text-sm text-slate-500 mb-16">Diterima oleh,</p>
                            <p className="font-bold text-slate-800 border-b border-slate-300 inline-block pb-1 px-4">{u?.name}</p>
                            <p className="text-xs text-slate-500 mt-1">Karyawan</p>
                        </div>
                        <div className="text-center">
                            <p className="text-xs text-slate-500 mb-1">Bekasi, {new Date().getDate()} {months[p.month]} {p.year}</p>
                            <p className="text-sm text-slate-500 mb-14">Disetujui oleh,</p>
                            <p className="font-bold text-slate-800 border-b border-slate-300 inline-block pb-1 px-8">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
                            <p className="text-xs text-slate-500 mt-1">HRD</p>
                            <p className="text-xs text-slate-500">Admin SDM</p>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-4 bg-slate-50/50 text-center border-t border-slate-200/60">
                        <p className="text-xs text-red-400 italic">Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.</p>
                        <p className="text-xs text-slate-400 mt-1">Dicetak {new Date().toLocaleDateString('id-ID')} {new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'})} | RS Kartika Husada Setu by:{u?.name}</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function AttendanceCard({ label, value, unit, color, isText = false }) {
    return (
        <div className={`${color} rounded-xl p-3 text-center`}>
            {isText ? (
                <p className="text-sm font-bold">{value}</p>
            ) : (
                <p className="text-xl font-extrabold">{value}</p>
            )}
            <p className="text-xs font-medium mt-0.5">{label}{unit ? ` (${unit})` : ''}</p>
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
