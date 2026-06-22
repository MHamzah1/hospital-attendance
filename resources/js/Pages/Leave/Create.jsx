import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';

export default function LeaveCreate({ typeLabels }) {
    const [form, setForm] = useState({
        type: 'cuti_tahunan',
        start_date: '',
        end_date: '',
        reason: '',
    });
    const [attachment, setAttachment] = useState(null);
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const isSickLeave = form.type === 'cuti_sakit';

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData();
        Object.entries(form).forEach(([key, value]) => formData.append(key, value));
        if (attachment) {
            formData.append('attachment', attachment);
        }

        router.post('/leaves', formData, {
            forceFormData: true,
            onError: (errs) => setErrors(errs),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthenticatedLayout header="Ajukan Cuti">
            <Head title="Ajukan Cuti" />

            <div className="max-w-2xl mx-auto">
                <Link href="/leaves" className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-6">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                    Kembali
                </Link>

                <div className="bg-white rounded-2xl border border-slate-200/60 p-8">
                    <div className="mb-6">
                        <h2 className="text-xl font-bold text-slate-800">Form Pengajuan Cuti</h2>
                        <p className="text-sm text-slate-500 mt-1">Isi form berikut untuk mengajukan cuti. Pengajuan akan diproses oleh Admin SDM.</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Jenis Cuti</label>
                            <select
                                value={form.type}
                                onChange={e => setForm({...form, type: e.target.value})}
                                className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                            >
                                {Object.entries(typeLabels).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                            {errors.type && <p className="text-red-500 text-xs mt-1">{errors.type}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Mulai</label>
                                <input
                                    type="date"
                                    value={form.start_date}
                                    onChange={e => setForm({...form, start_date: e.target.value})}
                                    className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                />
                                {errors.start_date && <p className="text-red-500 text-xs mt-1">{errors.start_date}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Selesai</label>
                                <input
                                    type="date"
                                    value={form.end_date}
                                    onChange={e => setForm({...form, end_date: e.target.value})}
                                    className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                />
                                {errors.end_date && <p className="text-red-500 text-xs mt-1">{errors.end_date}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Alasan</label>
                            <textarea
                                value={form.reason}
                                onChange={e => setForm({...form, reason: e.target.value})}
                                rows={4}
                                className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Tuliskan alasan pengajuan cuti..."
                            />
                            {errors.reason && <p className="text-red-500 text-xs mt-1">{errors.reason}</p>}
                        </div>

                        {isSickLeave && (
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Lampiran Surat Sakit <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={e => setAttachment(e.target.files[0] || null)}
                                    className="w-full rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:text-sm file:font-medium"
                                />
                                <p className="text-xs text-slate-500 mt-1">Format PDF, JPG, atau PNG. Maks. 5 MB.</p>
                                {errors.attachment && <p className="text-red-500 text-xs mt-1">{errors.attachment}</p>}
                            </div>
                        )}

                        <div className="flex gap-3 pt-2">
                            <Link href="/leaves" className="flex-1 py-3 text-center rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition-colors">
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="flex-1 py-3 rounded-xl bg-emerald-500 text-white font-semibold text-sm hover:bg-emerald-600 transition-colors disabled:opacity-50 shadow-lg shadow-emerald-500/30"
                            >
                                {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
