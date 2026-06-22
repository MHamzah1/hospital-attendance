import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, Link, usePage } from '@inertiajs/react';

export default function EmployeeDashboard({ todayAttendance, stats, latestPayroll, retirementInfo }) {
    const { auth } = usePage().props;
    const user = auth.user;

    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);
    const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    const retirementDateFallback = user?.birth_date
        ? new Date(new Date(user.birth_date).setFullYear(new Date(user.birth_date).getFullYear() + 60))
        : null;
    const retirementDate = retirementInfo?.retirementDate
        ? new Date(retirementInfo.retirementDate)
        : retirementDateFallback;
    const retirementDateText = retirementDate && !Number.isNaN(retirementDate.getTime())
        ? formatDate(retirementDate)
        : '-';

    return (
        <AuthenticatedLayout header="Dashboard">
            <Head title="Dashboard" />
            <FlashMessage />

            {/* Welcome */}
            <div className="bg-gradient-to-br from-[#0f2027] via-[#203a43] to-[#2c5364] rounded-2xl p-6 lg:p-8 mb-8 text-white relative overflow-hidden">
                <div className="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 rounded-full -translate-y-1/2 translate-x-1/2" />
                <div className="relative">
                    <p className="text-emerald-400 text-sm font-medium">Selamat Datang,</p>
                    <h1 className="text-2xl lg:text-3xl font-extrabold mt-1">{user.name}</h1>
                    <p className="text-slate-300 text-sm mt-1">{user.position} — {user.department}</p>
                </div>
                <div className="relative mt-5 flex flex-wrap gap-3">
                    <Link href="/attendance" className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-emerald-500/30">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        {todayAttendance?.clock_in && !todayAttendance?.clock_out ? 'Clock Out' : todayAttendance?.clock_out ? 'Sudah Absen' : 'Absen Sekarang'}
                    </Link>
                    <Link href="/leaves/create" className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                        Ajukan Cuti
                    </Link>
                    <Link href="/overtimes/create" className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                        Ajukan Lembur
                    </Link>
                </div>
            </div>

            {/* Stats cards */}
            <div className="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
                {[
                    { label: 'Hari Hadir', value: stats.presentDays, icon: '✅', bg: 'bg-emerald-50 text-emerald-700' },
                    { label: 'Terlambat', value: stats.lateDays, icon: '⏰', bg: 'bg-amber-50 text-amber-700' },
                    { label: 'Tidak Hadir', value: stats.absentDays, icon: '❌', bg: 'bg-red-50 text-red-700' },
                    { label: 'Cuti', value: stats.leaveDays, icon: '🏖️', bg: 'bg-blue-50 text-blue-700' },
                    { label: 'Sisa Cuti', value: stats.remainingAnnualLeave ?? 0, icon: '🌿', bg: 'bg-teal-50 text-teal-700' },
                    { label: 'Perkiraan Pensiun', value: retirementDateText, icon: '🧓', bg: 'bg-indigo-50 text-indigo-700', isText: true },
                ].map((s, i) => (
                    <div key={i} className="bg-white rounded-2xl border border-slate-200/60 p-5">
                        <div className="flex items-center gap-3">
                            <span className={`text-lg p-2.5 rounded-xl ${s.bg}`}>{s.icon}</span>
                            <div>
                                <p className={`${s.isText ? 'text-sm' : 'text-2xl'} font-extrabold text-slate-800`}>{s.value}</p>
                                <p className="text-xs text-slate-500 font-medium">{s.label}</p>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Today attendance status */}
                <div className="bg-white rounded-2xl border border-slate-200/60 p-6">
                    <h3 className="font-bold text-slate-800 mb-4">Status Absensi Hari Ini</h3>
                    {todayAttendance ? (
                        <div className="space-y-4">
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg className="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 uppercase font-medium">Clock In</p>
                                    <p className="text-lg font-bold text-slate-800">{todayAttendance.clock_in || '-'}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${todayAttendance.clock_out ? 'bg-blue-100' : 'bg-slate-100'}`}>
                                    <svg className={`w-6 h-6 ${todayAttendance.clock_out ? 'text-blue-600' : 'text-slate-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                </div>
                                <div>
                                    <p className="text-xs text-slate-500 uppercase font-medium">Clock Out</p>
                                    <p className="text-lg font-bold text-slate-800">{todayAttendance.clock_out || 'Belum Clock Out'}</p>
                                </div>
                            </div>
                            <div className={`inline-flex px-3 py-1.5 rounded-full text-xs font-medium ${
                                todayAttendance.status === 'present' ? 'bg-emerald-100 text-emerald-700' :
                                todayAttendance.status === 'late' ? 'bg-amber-100 text-amber-700' :
                                'bg-slate-100 text-slate-700'
                            }`}>
                                Status: {todayAttendance.status === 'present' ? 'Hadir Tepat Waktu' : todayAttendance.status === 'late' ? 'Terlambat' : todayAttendance.status}
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-6">
                            <div className="text-4xl mb-3">📸</div>
                            <p className="text-slate-500 text-sm mb-4">Anda belum absen hari ini</p>
                            <Link href="/attendance" className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                Absen Sekarang
                            </Link>
                        </div>
                    )}
                </div>

                {/* Latest payroll */}
                <div className="bg-white rounded-2xl border border-slate-200/60 p-6">
                    <h3 className="font-bold text-slate-800 mb-4">Gaji Terakhir</h3>
                    {latestPayroll ? (
                        <div>
                            <p className="text-sm text-slate-500 mb-2">
                                Periode: {['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][latestPayroll.month]} {latestPayroll.year}
                            </p>
                            <div className="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-5 text-white mb-4">
                                <p className="text-emerald-100 text-xs uppercase font-medium">Take Home Pay</p>
                                <p className="text-2xl font-extrabold mt-1">Rp {fmt(latestPayroll.net_salary)}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-3 text-sm">
                                <div className="bg-slate-50 rounded-lg p-3">
                                    <p className="text-slate-500 text-xs">Pendapatan</p>
                                    <p className="font-bold text-slate-700">Rp {fmt(latestPayroll.gross_salary)}</p>
                                </div>
                                <div className="bg-red-50 rounded-lg p-3">
                                    <p className="text-red-500 text-xs">Potongan</p>
                                    <p className="font-bold text-red-700">Rp {fmt(latestPayroll.total_deduction)}</p>
                                </div>
                            </div>
                            <Link href={`/payroll/${latestPayroll.id}`} className="mt-4 block text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
                                Lihat Detail Slip Gaji →
                            </Link>
                        </div>
                    ) : (
                        <div className="text-center py-6">
                            <div className="text-4xl mb-3">💰</div>
                            <p className="text-slate-500 text-sm">Belum ada data penggajian</p>
                        </div>
                    )}
                </div>
            </div>

            <div className="mt-6 bg-white rounded-2xl border border-slate-200/60 p-6">
                <h3 className="font-bold text-slate-800 mb-2">Informasi Pensiun</h3>
                {retirementInfo?.retirementDate ? (
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-sm text-slate-600">
                            Perkiraan tanggal pensiun
                        </p>
                        <div className="text-right">
                            <p className="text-lg font-extrabold text-slate-800">{formatDate(retirementInfo.retirementDate)}</p>
                            <p className={`text-xs font-medium ${retirementInfo.isRetired ? 'text-red-600' : 'text-emerald-600'}`}>
                                {retirementInfo.isRetired ? 'Sudah memasuki usia pensiun' : `Sisa ${retirementInfo.remainingText}`}
                            </p>
                        </div>
                    </div>
                ) : (
                    <p className="text-sm text-slate-500">Tanggal lahir belum tersedia, perkiraan tanggal pensiun belum dapat dihitung.</p>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
