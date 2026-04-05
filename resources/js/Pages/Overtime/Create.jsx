import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';

export default function OvertimeCreate({ categoryRates }) {
    const CATEGORY_LABELS = {
        jam:       'Jam',
        malam:     'Malam',
        shift:     'Shift',
        on_call:   'On Call',
        mod:       'MOD',
        hari_raya: 'Hari Raya',
    };

    const formatRp = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);

    const [form, setForm] = useState({
        date: '',
        start_time: '',
        end_time: '',
        category: 'jam',
        reason: '',
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post('/overtimes', form, {
            onError: (errs) => setErrors(errs),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthenticatedLayout header="Ajukan Lembur">
            <Head title="Ajukan Lembur" />

            <div className="max-w-2xl mx-auto">
                <Link href="/overtimes" className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-6">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                    Kembali
                </Link>

                <div className="bg-white rounded-2xl border border-slate-200/60 p-8">
                    <div className="mb-6">
                        <h2 className="text-xl font-bold text-slate-800">Form Pengajuan Lembur</h2>
                        <p className="text-sm text-slate-500 mt-1">Isi form berikut untuk mengajukan lembur. Pengajuan akan diproses oleh Admin SDM.</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Lembur</label>
                            <input type="date" value={form.date} onChange={e => setForm({...form, date: e.target.value})}
                                className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                            {errors.date && <p className="text-red-500 text-xs mt-1">{errors.date}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Jam Mulai</label>
                                <input type="time" value={form.start_time} onChange={e => setForm({...form, start_time: e.target.value})}
                                    className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                                {errors.start_time && <p className="text-red-500 text-xs mt-1">{errors.start_time}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Jam Selesai</label>
                                <input type="time" value={form.end_time} onChange={e => setForm({...form, end_time: e.target.value})}
                                    className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                                {errors.end_time && <p className="text-red-500 text-xs mt-1">{errors.end_time}</p>}
                            </div>
                        </div>

                        {/* Kategori Lembur */}
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Kategori Lembur</label>
                            <div className="grid grid-cols-3 gap-2">
                                {Object.entries(CATEGORY_LABELS).map(([key, label]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => setForm({...form, category: key})}
                                        className={`flex flex-col items-center justify-center p-3 rounded-xl border-2 text-sm font-semibold transition-all ${
                                            form.category === key
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                        }`}
                                    >
                                        <span>{label}</span>
                                        <span className={`text-xs font-normal mt-0.5 ${form.category === key ? 'text-emerald-600' : 'text-slate-400'}`}>
                                            {formatRp(categoryRates?.[key] ?? 10000)}/{key === 'jam' ? 'jam' : 'shift'}
                                        </span>
                                    </button>
                                ))}
                            </div>
                            {errors.category && <p className="text-red-500 text-xs mt-1">{errors.category}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Alasan Lembur</label>
                            <textarea value={form.reason} onChange={e => setForm({...form, reason: e.target.value})} rows={4}
                                className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Tuliskan alasan pengajuan lembur..." />
                            {errors.reason && <p className="text-red-500 text-xs mt-1">{errors.reason}</p>}
                        </div>

                        <div className="flex gap-3 pt-2">
                            <Link href="/overtimes" className="flex-1 py-3 text-center rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition-colors">Batal</Link>
                            <button type="submit" disabled={processing}
                                className="flex-1 py-3 rounded-xl bg-emerald-500 text-white font-semibold text-sm hover:bg-emerald-600 transition-colors disabled:opacity-50 shadow-lg shadow-emerald-500/30">
                                {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
