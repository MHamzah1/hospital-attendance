import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function OvertimeIndex({ overtimes, filters, units }) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.role === 'admin_sdm';
    const [rejectModal, setRejectModal] = useState(null);
    const [adminNotes, setAdminNotes] = useState('');
    const [approveModal, setApproveModal] = useState(null);
    const [approvePay, setApprovePay] = useState('');
    const [searchInput, setSearchInput] = useState(filters.search || '');

    const openApproveModal = (ot) => {
        setApproveModal(ot.id);
        setApprovePay(Math.abs(ot.total_pay ?? 0));
    };

    const CATEGORY_LABELS = {
        jam:       'Jam',
        malam:     'Malam',
        shift:     'Shift',
        on_call:   'On Call',
        mod:       'MOD',
        hari_raya: 'Hari Raya',
    };
    const CATEGORY_COLORS = {
        jam:       'bg-blue-50 text-blue-700',
        malam:     'bg-indigo-50 text-indigo-700',
        shift:     'bg-purple-50 text-purple-700',
        on_call:   'bg-amber-50 text-amber-700',
        mod:       'bg-orange-50 text-orange-700',
        hari_raya: 'bg-red-50 text-red-700',
    };
    const formatRp = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n ?? 0);

    const buildParams = (overrides = {}) => {
        const base = {
            status: filters.status || 'all',
            date_from: filters.date_from || '',
            date_to: filters.date_to || '',
            search: filters.search || '',
            unit: filters.unit || 'all',
            ...overrides,
        };
        const params = new URLSearchParams();
        Object.entries(base).forEach(([k, v]) => { if (v) params.set(k, v); else params.set(k, ''); });
        return params.toString();
    };

    const statusColors = {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-red-100 text-red-700',
    };
    const statusLabels = { pending: 'Pending', approved: 'Disetujui', rejected: 'Ditolak' };

    const handleApprove = () => {
        router.post(`/overtimes/${approveModal}/approve`, { total_pay: approvePay }, {
            onSuccess: () => { setApproveModal(null); setApprovePay(''); },
        });
    };

    const handleReject = (id) => {
        router.post(`/overtimes/${id}/reject`, { admin_notes: adminNotes }, {
            onSuccess: () => { setRejectModal(null); setAdminNotes(''); },
        });
    };

    return (
        <AuthenticatedLayout header="Pengajuan Lembur">
            <Head title="Pengajuan Lembur" />
            <FlashMessage />

            <div className="flex flex-col gap-4 mb-6">
                {/* Status Filter */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex flex-wrap gap-2">
                        {['all', 'pending', 'approved', 'rejected'].map((s) => (
                            <Link key={s} href={`/overtimes?${buildParams({ status: s })}`}
                                className={`px-4 py-2 rounded-xl text-sm font-medium transition-colors ${
                                    filters.status === s ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
                                }`}>
                                {s === 'all' ? 'Semua' : statusLabels[s]}
                            </Link>
                        ))}
                    </div>
                    {!isAdmin && (
                        <Link href="/overtimes/create" className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-emerald-500/30">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                            Ajukan Lembur
                        </Link>
                    )}
                </div>

                {/* Search — admin only */}
                {isAdmin && (
                    <form
                        onSubmit={(e) => { e.preventDefault(); router.get(`/overtimes?${buildParams({ search: searchInput })}`); }}
                        className="bg-white rounded-xl border border-slate-200 p-4"
                    >
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="flex-1">
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Cari Karyawan / Alasan</label>
                                <div className="flex gap-2">
                                    <div className="relative flex-1">
                                        <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" /></svg>
                                        <input
                                            type="text"
                                            value={searchInput}
                                            onChange={(e) => setSearchInput(e.target.value)}
                                            placeholder="Nama, NIP, atau alasan..."
                                            className="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-emerald-500"
                                        />
                                    </div>
                                    <button type="submit" className="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium transition-colors">Cari</button>
                                    {filters.search && (
                                        <button type="button" onClick={() => { setSearchInput(''); router.get(`/overtimes?${buildParams({ search: '' })}`); }} className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors">Reset</button>
                                    )}
                                </div>
                            </div>
                            <div className="sm:w-48">
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Filter Unit</label>
                                <select
                                    value={filters.unit || 'all'}
                                    onChange={(e) => router.get(`/overtimes?${buildParams({ unit: e.target.value })}`)}
                                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-emerald-500"
                                >
                                    <option value="all">Semua Unit</option>
                                    {units?.map(u => (
                                        <option key={u} value={u}>{u}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </form>
                )}

                {/* Date Filter */}
                <div className="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-4">
                    <div className="flex-1">
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Mulai</label>
                        <input
                            type="date"
                            value={filters.date_from || ''}
                            onChange={(e) => router.get(`/overtimes?${buildParams({ date_from: e.target.value })}`)}
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    <div className="flex-1">
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Akhir</label>
                        <input
                            type="date"
                            value={filters.date_to || ''}
                            onChange={(e) => router.get(`/overtimes?${buildParams({ date_to: e.target.value })}`)}
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    {(filters.date_from || filters.date_to) && (
                        <div className="flex items-end">
                            <button
                                onClick={() => router.get(`/overtimes?${buildParams({ date_from: '', date_to: '' })}`)}
                                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-colors"
                            >
                                Reset
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-slate-50/80">
                                {isAdmin && <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Karyawan</th>}
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Waktu</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Total Jam</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Kategori</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Total Bayar</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Alasan</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                {isAdmin && <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {overtimes?.data?.length === 0 ? (
                                <tr><td colSpan={9} className="px-6 py-12 text-center text-slate-400">Tidak ada data pengajuan lembur</td></tr>
                            ) : (
                                overtimes?.data?.map((ot) => (
                                    <tr key={ot.id} className="hover:bg-slate-50/50 transition-colors">
                                        {isAdmin && <td className="px-6 py-3.5 font-medium text-slate-700">{ot.user?.name}</td>}
                                        <td className="px-6 py-3.5 text-slate-600">
                                            {new Date(ot.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                        </td>
                                        <td className="px-6 py-3.5 text-slate-600 font-mono text-xs">{ot.start_time} - {ot.end_time}</td>
                                        <td className="px-6 py-3.5 text-slate-700 font-semibold">{Math.abs(ot.total_hours)} jam</td>
                                        <td className="px-6 py-3.5">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${CATEGORY_COLORS[ot.category] ?? 'bg-slate-100 text-slate-700'}`}>
                                                {CATEGORY_LABELS[ot.category] ?? ot.category ?? '-'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-3.5 text-slate-700 font-semibold text-xs">{formatRp(Math.abs(ot.total_pay))}</td>
                                        <td className="px-6 py-3.5 text-slate-600 max-w-[200px] truncate">{ot.reason}</td>
                                        <td className="px-6 py-3.5">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[ot.status]}`}>
                                                {statusLabels[ot.status]}
                                            </span>
                                        </td>
                                        {isAdmin && (
                                            <td className="px-6 py-3.5">
                                                {ot.status === 'pending' && (
                                                    <div className="flex gap-2">
                                                    <button onClick={() => openApproveModal(ot)} className="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-medium hover:bg-emerald-200">Setujui</button>
                                                        <button onClick={() => setRejectModal(ot.id)} className="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200">Tolak</button>
                                                    </div>
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {approveModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                        <h3 className="font-bold text-slate-800 text-lg mb-1">Setujui Pengajuan Lembur</h3>
                        <p className="text-sm text-slate-500 mb-4">Periksa & ubah total bayar jika diperlukan sebelum menyetujui.</p>
                        <label className="block text-sm font-semibold text-slate-700 mb-1.5">Total Bayar (Rp)</label>
                        <input
                            type="number"
                            min="0"
                            value={approvePay}
                            onChange={e => setApprovePay(e.target.value)}
                            className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 mb-4"
                        />
                        <div className="flex gap-3">
                            <button onClick={() => { setApproveModal(null); setApprovePay(''); }} className="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200">Batal</button>
                            <button onClick={handleApprove} className="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white font-semibold text-sm hover:bg-emerald-600">Setujui</button>
                        </div>
                    </div>
                </div>
            )}

            {rejectModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                        <h3 className="font-bold text-slate-800 text-lg mb-4">Tolak Pengajuan Lembur</h3>
                        <textarea value={adminNotes} onChange={e => setAdminNotes(e.target.value)} placeholder="Alasan penolakan..." className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 mb-4" rows={3} />
                        <div className="flex gap-3">
                            <button onClick={() => { setRejectModal(null); setAdminNotes(''); }} className="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200">Batal</button>
                            <button onClick={() => handleReject(rejectModal)} className="flex-1 py-2.5 rounded-xl bg-red-500 text-white font-semibold text-sm hover:bg-red-600" disabled={!adminNotes}>Tolak</button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
