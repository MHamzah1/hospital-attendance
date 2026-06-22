import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, Link } from '@inertiajs/react';

export default function AdminDashboard({ stats, recentAttendances, recentLeaves, recentOvertimes }) {
    const statCards = [
        { label: 'Total Karyawan Aktif', value: stats.totalEmployees, icon: '👥', color: 'from-blue-500 to-blue-600', bg: 'bg-blue-50' },
        { label: 'Hadir Hari Ini', value: stats.todayPresent, icon: '✅', color: 'from-emerald-500 to-emerald-600', bg: 'bg-emerald-50' },
        { label: 'Pengajuan Cuti Pending', value: stats.pendingLeaves, icon: '📋', color: 'from-amber-500 to-amber-600', bg: 'bg-amber-50' },
        { label: 'Pengajuan Lembur Pending', value: stats.pendingOvertimes, icon: '⏰', color: 'from-purple-500 to-purple-600', bg: 'bg-purple-50' },
    ];

    const statusColors = {
        present: 'bg-emerald-100 text-emerald-700',
        late: 'bg-amber-100 text-amber-700',
        absent: 'bg-red-100 text-red-700',
        leave: 'bg-blue-100 text-blue-700',
        sick: 'bg-orange-100 text-orange-700',
    };

    const statusLabels = {
        present: 'Hadir',
        late: 'Terlambat',
        absent: 'Tidak Hadir',
        leave: 'Cuti',
        sick: 'Sakit',
    };

    const leaveTypeLabels = {
        cuti_tahunan: 'Tahunan',
        cuti_sakit: 'Sakit',
        cuti_melahirkan: 'Melahirkan',
        cuti_menikah: 'Menikah',
        cuti_duka: 'Duka',
        izin_lainnya: 'Lainnya',
    };

    return (
        <AuthenticatedLayout header="Dashboard Admin SDM">
            <Head title="Dashboard" />
            <FlashMessage />

            {/* Stats */}
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
                {statCards.map((stat, i) => (
                    <div key={i} className="bg-white rounded-2xl border border-slate-200/60 p-5 hover:shadow-lg hover:shadow-slate-200/50 transition-all duration-300">
                        <div className="flex items-start justify-between">
                            <div>
                                <p className="text-slate-500 text-xs font-medium uppercase tracking-wider">{stat.label}</p>
                                <p className="text-3xl font-extrabold text-slate-800 mt-2">{stat.value}</p>
                            </div>
                            <div className={`text-2xl p-3 rounded-xl ${stat.bg}`}>
                                {stat.icon}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {/* Recent Attendances */}
                <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 className="font-bold text-slate-800">Absensi Hari Ini</h3>
                        <Link href="/attendance" className="text-xs text-blue-600 hover:text-blue-700 font-medium">
                            Lihat Semua →
                        </Link>
                    </div>
                    <div className="divide-y divide-slate-50">
                        {recentAttendances.length === 0 ? (
                            <div className="px-6 py-8 text-center text-slate-400 text-sm">Belum ada absensi hari ini</div>
                        ) : (
                            recentAttendances.map((att) => (
                                <div key={att.id} className="px-6 py-3 flex items-center justify-between hover:bg-slate-50/50 transition-colors">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-bold text-xs">
                                            {att.user?.name?.charAt(0)}
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-slate-700">{att.user?.name}</p>
                                            <p className="text-xs text-slate-400">{att.clock_in} {att.clock_out ? `- ${att.clock_out}` : ''}</p>
                                        </div>
                                    </div>
                                    <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${statusColors[att.status]}`}>
                                        {statusLabels[att.status]}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                {/* Pending Approvals */}
                <div className="space-y-6">
                    {/* Pending Leaves */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-bold text-slate-800">Cuti Menunggu Approval</h3>
                            <Link href="/leaves?status=pending" className="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                Lihat Semua →
                            </Link>
                        </div>
                        <div className="divide-y divide-slate-50">
                            {recentLeaves.length === 0 ? (
                                <div className="px-6 py-6 text-center text-slate-400 text-sm">Tidak ada pengajuan pending</div>
                            ) : (
                                recentLeaves.map((leave) => (
                                    <div key={leave.id} className="px-6 py-3 flex items-center justify-between hover:bg-slate-50/50">
                                        <div>
                                            <p className="text-sm font-medium text-slate-700">{leave.user?.name}</p>
                                            <p className="text-xs text-slate-400">
                                                {leaveTypeLabels[leave.type]} • {leave.total_days} hari
                                            </p>
                                        </div>
                                        <span className="text-xs px-2.5 py-1 rounded-full font-medium bg-amber-100 text-amber-700">Pending</span>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Pending Overtimes */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-bold text-slate-800">Lembur Menunggu Approval</h3>
                            <Link href="/overtimes?status=pending" className="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                Lihat Semua →
                            </Link>
                        </div>
                        <div className="divide-y divide-slate-50">
                            {recentOvertimes.length === 0 ? (
                                <div className="px-6 py-6 text-center text-slate-400 text-sm">Tidak ada pengajuan pending</div>
                            ) : (
                                recentOvertimes.map((ot) => (
                                    <div key={ot.id} className="px-6 py-3 flex items-center justify-between hover:bg-slate-50/50">
                                        <div>
                                            <p className="text-sm font-medium text-slate-700">{ot.user?.name}</p>
                                            <p className="text-xs text-slate-400">
                                                {ot.date_formatted} • {ot.start_time} - {ot.end_time}
                                            </p>
                                        </div>
                                        <span className="text-xs px-2.5 py-1 rounded-full font-medium bg-amber-100 text-amber-700">Pending</span>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
