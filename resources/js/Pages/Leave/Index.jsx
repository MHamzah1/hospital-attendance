import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function LeaveIndex({ leaves, filters, typeLabels }) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.role === 'admin_sdm';
    const [rejectModal, setRejectModal] = useState(null);
    const [adminNotes, setAdminNotes] = useState('');

    const statusColors = {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-red-100 text-red-700',
    };

    const statusLabels = { pending: 'Pending', approved: 'Disetujui', rejected: 'Ditolak' };

    const handleApprove = (id) => {
        if (confirm('Yakin ingin menyetujui pengajuan cuti ini?')) {
            router.post(`/leaves/${id}/approve`, {});
        }
    };

    const handleReject = (id) => {
        router.post(`/leaves/${id}/reject`, { admin_notes: adminNotes }, {
            onSuccess: () => { setRejectModal(null); setAdminNotes(''); },
        });
    };

    return (
        <AuthenticatedLayout header="Pengajuan Cuti">
            <Head title="Pengajuan Cuti" />
            <FlashMessage />

            <div className="flex flex-col gap-4 mb-6">
                {/* Status Filter */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex gap-2">
                        {['all', 'pending', 'approved', 'rejected'].map((s) => (
                            <Link
                                key={s}
                                href={`/leaves?status=${s}&date_from=${filters.date_from || ''}&date_to=${filters.date_to || ''}`}
                                className={`px-4 py-2 rounded-xl text-sm font-medium transition-colors ${
                                    filters.status === s
                                        ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30'
                                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
                                }`}
                            >
                                {s === 'all' ? 'Semua' : statusLabels[s]}
                            </Link>
                        ))}
                    </div>
                    {!isAdmin && (
                        <Link href="/leaves/create" className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-emerald-500/30">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                            Ajukan Cuti
                        </Link>
                    )}
                </div>

                {/* Date Filter */}
                <div className="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-4">
                    <div className="flex-1">
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Mulai</label>
                        <input
                            type="date"
                            value={filters.date_from || ''}
                            onChange={(e) => {
                                const params = new URLSearchParams();
                                params.set('status', filters.status || 'all');
                                params.set('date_from', e.target.value);
                                params.set('date_to', filters.date_to || '');
                                router.get(`/leaves?${params.toString()}`);
                            }}
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    <div className="flex-1">
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Akhir</label>
                        <input
                            type="date"
                            value={filters.date_to || ''}
                            onChange={(e) => {
                                const params = new URLSearchParams();
                                params.set('status', filters.status || 'all');
                                params.set('date_from', filters.date_from || '');
                                params.set('date_to', e.target.value);
                                router.get(`/leaves?${params.toString()}`);
                            }}
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    {(filters.date_from || filters.date_to) && (
                        <div className="flex items-end">
                            <button
                                onClick={() => {
                                    const params = new URLSearchParams();
                                    params.set('status', filters.status || 'all');
                                    router.get(`/leaves?${params.toString()}`);
                                }}
                                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors"
                            >
                                Reset
                            </button>
                        </div>
                    )}
                </div>

            <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-slate-50/80">
                                {isAdmin && <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Karyawan</th>}
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Jenis Cuti</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Durasi</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Alasan</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                {isAdmin && <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {leaves?.data?.length === 0 ? (
                                <tr><td colSpan={7} className="px-6 py-12 text-center text-slate-400">Tidak ada data pengajuan cuti</td></tr>
                            ) : (
                                leaves?.data?.map((leave) => (
                                    <tr key={leave.id} className="hover:bg-slate-50/50 transition-colors">
                                        {isAdmin && <td className="px-6 py-3.5 font-medium text-slate-700">{leave.user?.name}</td>}
                                        <td className="px-6 py-3.5 text-slate-600">{typeLabels[leave.type]}</td>
                                        <td className="px-6 py-3.5 text-slate-600 text-xs">
                                            {new Date(leave.start_date).toLocaleDateString('id-ID')} - {new Date(leave.end_date).toLocaleDateString('id-ID')}
                                        </td>
                                        <td className="px-6 py-3.5 text-slate-600">{leave.total_days} hari</td>
                                        <td className="px-6 py-3.5 text-slate-600 max-w-[200px] truncate">{leave.reason}</td>
                                        <td className="px-6 py-3.5">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[leave.status]}`}>
                                                {statusLabels[leave.status]}
                                            </span>
                                        </td>
                                        {isAdmin && (
                                            <td className="px-6 py-3.5">
                                                {leave.status === 'pending' && (
                                                    <div className="flex gap-2">
                                                        <button
                                                            onClick={() => handleApprove(leave.id)}
                                                            className="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-medium hover:bg-emerald-200 transition-colors"
                                                        >
                                                            Setujui
                                                        </button>
                                                        <button
                                                            onClick={() => setRejectModal(leave.id)}
                                                            className="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200 transition-colors"
                                                        >
                                                            Tolak
                                                        </button>
                                                    </div>
                                                )}
                                                {leave.status !== 'pending' && (
                                                    <span className="text-xs text-slate-400">
                                                        {leave.approver?.name && `oleh ${leave.approver.name}`}
                                                    </span>
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {leaves?.links && (
                    <div className="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                        <p className="text-xs text-slate-500">Menampilkan {leaves.from}-{leaves.to} dari {leaves.total}</p>
                        <div className="flex gap-1">
                            {leaves.links.map((link, i) => (
                                <button key={i} onClick={() => link.url && router.get(link.url, {}, { preserveState: true })} disabled={!link.url}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${link.active ? 'bg-emerald-500 text-white' : link.url ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'text-slate-300'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Reject Modal */}
            {rejectModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                        <h3 className="font-bold text-slate-800 text-lg mb-4">Tolak Pengajuan Cuti</h3>
                        <textarea
                            value={adminNotes}
                            onChange={e => setAdminNotes(e.target.value)}
                            placeholder="Alasan penolakan..."
                            className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 mb-4"
                            rows={3}
                        />
                        <div className="flex gap-3">
                            <button onClick={() => { setRejectModal(null); setAdminNotes(''); }} className="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200">
                                Batal
                            </button>
                            <button onClick={() => handleReject(rejectModal)} className="flex-1 py-2.5 rounded-xl bg-red-500 text-white font-semibold text-sm hover:bg-red-600" disabled={!adminNotes}>
                                Tolak
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
