import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, Link, router } from '@inertiajs/react';

export default function PayrollIndex({ payrolls, employees, filters, summary }) {
    const [month, setMonth] = useState(filters.month);
    const [year, setYear] = useState(filters.year);
    const [search, setSearch] = useState(filters.search || '');
    const [generating, setGenerating] = useState(false);

    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);

    const handleFilter = () => {
        router.get('/payroll', { month, year, search: search || undefined }, { preserveState: true });
    };

    const handleGenerate = () => {
        if (confirm(`Generate penggajian untuk ${months[month - 1]} ${year}?`)) {
            setGenerating(true);
            router.post('/payroll/generate', { month, year }, {
                onFinish: () => setGenerating(false),
            });
        }
    };

    const statusColors = {
        draft: 'bg-slate-100 text-slate-600',
        finalized: 'bg-blue-100 text-blue-700',
        paid: 'bg-emerald-100 text-emerald-700',
    };
    const statusLabels = { draft: 'Draft', finalized: 'Final', paid: 'Dibayar' };

    return (
        <AuthenticatedLayout header="Penggajian">
            <Head title="Penggajian" />
            <FlashMessage />

            {/* Controls */}
            <div className="bg-white rounded-2xl border border-slate-200/60 p-4 mb-6">
                <div className="flex flex-wrap items-center gap-3">
                    <select value={month} onChange={e => setMonth(Number(e.target.value))} className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        {months.map((m, i) => <option key={i} value={i + 1}>{m}</option>)}
                    </select>
                    <select value={year} onChange={e => setYear(Number(e.target.value))} className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        {[2024, 2025, 2026].map(y => <option key={y} value={y}>{y}</option>)}
                    </select>
                    <button onClick={handleFilter} className="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2 rounded-xl text-sm font-semibold transition-colors">
                        Filter
                    </button>
                    <input
                        type="text"
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && handleFilter()}
                        placeholder="Cari nama / NIP / departemen..."
                        className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 w-64"
                    />
                    <div className="flex-1" />
                    <button onClick={handleGenerate} disabled={generating}
                        className="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-emerald-500/30 disabled:opacity-50">
                        {generating ? 'Generating...' : '⚡ Generate Payroll'}
                    </button>
                    <a href={`/payroll-bulk-export?month=${month}&year=${year}`}
                        className="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors">
                        📊 Export Rekap
                    </a>
                    <Link href="/payroll-import"
                        className="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors">
                        📥 Import Potongan
                    </Link>
                </div>
            </div>

            {/* Info Message */}
            <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6">
                <p className="text-sm text-amber-800">
                    <strong>⚠️ Wajib import data potongan (Excel) terlebih dahulu</strong>, lalu klik <strong>Generate Payroll</strong> untuk merekap keseluruhan gaji, tunjangan, absensi, lembur, cuti & BPJS secara otomatis.
                </p>
            </div>

            {/* Summary cards */}
            {summary && summary.total_employees > 0 && (
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5">
                        <p className="text-xs text-slate-500 font-medium uppercase">Total Karyawan</p>
                        <p className="text-2xl font-extrabold text-slate-800 mt-1">{summary.total_employees}</p>
                    </div>
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5">
                        <p className="text-xs text-slate-500 font-medium uppercase">Total Gaji Bruto</p>
                        <p className="text-2xl font-extrabold text-slate-800 mt-1">Rp {fmt(summary.total_bruto)}</p>
                    </div>
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5">
                        <p className="text-xs text-slate-500 font-medium uppercase">Total Gaji Netto</p>
                        <p className="text-2xl font-extrabold text-emerald-600 mt-1">Rp {fmt(summary.total_netto)}</p>
                    </div>
                </div>
            )}

            {/* Table */}
            <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-slate-50/80">
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Karyawan</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Departemen</th>
                                <th className="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase">Gaji Bruto</th>
                                <th className="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase">Potongan</th>
                                <th className="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase">Gaji Netto</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {payrolls?.length === 0 ? (
                                <tr><td colSpan={7} className="px-6 py-12 text-center text-slate-400">
                                    Belum ada data penggajian untuk periode ini. Klik "Generate Payroll" untuk membuat.
                                </td></tr>
                            ) : (
                                payrolls?.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="px-6 py-3.5">
                                            <div>
                                                <p className="font-medium text-slate-700">{p.user?.name}</p>
                                                <p className="text-xs text-slate-400">{p.user?.employee_id}</p>
                                            </div>
                                        </td>
                                        <td className="px-6 py-3.5 text-slate-600 text-xs">{p.user?.department}</td>
                                        <td className="px-6 py-3.5 text-right text-slate-700 font-mono">Rp {fmt(p.gross_salary)}</td>
                                        <td className="px-6 py-3.5 text-right text-red-600 font-mono">Rp {fmt(p.total_deduction)}</td>
                                        <td className="px-6 py-3.5 text-right font-bold text-emerald-700 font-mono">Rp {fmt(p.net_salary)}</td>
                                        <td className="px-6 py-3.5">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[p.status]}`}>
                                                {statusLabels[p.status]}
                                            </span>
                                        </td>
                                        <td className="px-6 py-3.5">
                                            <div className="flex gap-1.5">
                                                <Link href={`/payroll/${p.id}`} className="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-medium hover:bg-slate-200">Detail</Link>
                                                {p.status === 'draft' && (
                                                    <button onClick={() => router.post(`/payroll/${p.id}/finalize`)} className="px-2.5 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium hover:bg-blue-200">Finalisasi</button>
                                                )}
                                                {p.status === 'finalized' && (
                                                    <button onClick={() => router.post(`/payroll/${p.id}/mark-paid`)} className="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-medium hover:bg-emerald-200">Bayar</button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
