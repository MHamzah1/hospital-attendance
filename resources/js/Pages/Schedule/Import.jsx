import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import FlashMessage from '@/Components/FlashMessage';

export default function ScheduleImport({ flash }) {
    const [file, setFile] = useState(null);
    const [month, setMonth] = useState(new Date().getMonth() + 1);
    const [year, setYear] = useState(new Date().getFullYear());
    const [preview, setPreview] = useState(null);
    const [loading, setLoading] = useState(false);
    const [step, setStep] = useState(1);
    const [importResult, setImportResult] = useState(null);

    const months = [
        { value: 1, label: 'Januari' },
        { value: 2, label: 'Februari' },
        { value: 3, label: 'Maret' },
        { value: 4, label: 'April' },
        { value: 5, label: 'Mei' },
        { value: 6, label: 'Juni' },
        { value: 7, label: 'Juli' },
        { value: 8, label: 'Agustus' },
        { value: 9, label: 'September' },
        { value: 10, label: 'Oktober' },
        { value: 11, label: 'November' },
        { value: 12, label: 'Desember' },
    ];

    const years = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - 5 + i);

    const getCsrfToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            console.warn('CSRF token not found in meta tag');
        }
        return token || '';
    };

    const handleFileSelect = async (e) => {
        const selectedFile = e.target.files[0];
        if (!selectedFile) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('file', selectedFile);

        try {
            const response = await fetch('/schedule/import/preview', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                let errorMsg = 'Gagal memproses file';
                try {
                    const errData = await response.json();
                    errorMsg = errData.message || errData.errors?.file?.[0] || errorMsg;
                } catch {
                    errorMsg = `Server error (${response.status})`;
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            setFile(selectedFile);
            setPreview(data);
            setStep(2);
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const handleProcessImport = async () => {
        if (!file) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('month', month);
        formData.append('year', year);

        try {
            const response = await fetch('/schedule/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                setImportResult(data);
                setStep(4);
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header="Import Jadwal Karyawan">
            <Head title="Import Jadwal Karyawan" />
            <FlashMessage />

            <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden max-w-5xl">
                {/* Step Indicator */}
                <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-6">
                    <div className="flex items-center justify-between mb-4">
                        {[1, 2, 3, 4].map((s) => (
                            <div key={s} className="flex items-center">
                                <div className={`flex items-center justify-center w-10 h-10 rounded-full font-bold ${
                                    s <= step
                                        ? 'bg-white text-blue-600'
                                        : 'bg-blue-400 text-white'
                                }`}>
                                    {s}
                                </div>
                                {s < 4 && <div className={`h-1 w-16 mx-2 ${s < step ? 'bg-white' : 'bg-blue-400'}`} />}
                            </div>
                        ))}
                    </div>
                    <p className="text-white font-semibold">
                        {step === 1 && 'Langkah 1: Pilih File & Bulan'}
                        {step === 2 && 'Langkah 2: Preview Data'}
                        {step === 3 && 'Langkah 3: Konfirmasi'}
                        {step === 4 && 'Langkah 4: Selesai'}
                    </p>
                </div>

                <div className="p-8">
                    {/* Step 1: File Upload & Month/Year Selection */}
                    {step === 1 && (
                        <div className="space-y-6">
                            <div>
                                <h3 className="text-lg font-bold text-slate-800 mb-4">1. Pilih File Excel</h3>
                                <p className="text-slate-600 text-sm mb-4">
                                    Format file: .xlsx, .xls, atau .csv
                                    <br />
                                    Struktur: NO | NIP | NAMA KARYAWAN | JABATAN/UNIT | Tgl 1 | Tgl 2 | ... | Tgl 31
                                </p>
                                <label className="inline-block">
                                    <input
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={handleFileSelect}
                                        disabled={loading}
                                        className="sr-only"
                                    />
                                    <span className="inline-flex items-center gap-2 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold cursor-pointer transition-colors">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                        </svg>
                                        Pilih File
                                    </span>
                                </label>
                                {file && <p className="mt-2 text-sm text-emerald-600 font-semibold">✓ {file.name}</p>}
                            </div>

                            {file && (
                                <div>
                                    <h3 className="text-lg font-bold text-slate-800 mb-4">2. Pilih Bulan & Tahun</h3>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-2">Bulan</label>
                                            <select
                                                value={month}
                                                onChange={(e) => setMonth(parseInt(e.target.value))}
                                                className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-500"
                                            >
                                                {months.map(m => (
                                                    <option key={m.value} value={m.value}>{m.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-slate-700 mb-2">Tahun</label>
                                            <select
                                                value={year}
                                                onChange={(e) => setYear(parseInt(e.target.value))}
                                                className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-500"
                                            >
                                                {years.map(y => (
                                                    <option key={y} value={y}>{y}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                    <p className="mt-3 text-sm text-slate-600">
                                        Jadwal akan diimport untuk: <span className="font-bold">{months.find(m => m.value === month)?.label} {year}</span>
                                    </p>
                                </div>
                            )}

                            <a href="/schedule/import/download-template" className="block text-blue-600 hover:text-blue-700 font-semibold text-sm">
                                ↓ Unduh Template Excel
                            </a>

                            {file && (
                                <div className="flex gap-3 pt-4">
                                    <button
                                        onClick={() => { setFile(null); setPreview(null); }}
                                        className="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-semibold"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        onClick={() => setStep(2)}
                                        className="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold"
                                    >
                                        Lanjut ke Preview
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Step 2: Preview */}
                    {step === 2 && preview && (
                        <div>
                            <h3 className="text-lg font-bold text-slate-800 mb-4">Preview Data (5 baris pertama)</h3>

                            <div className="bg-slate-50 rounded-xl p-4 mb-6 overflow-x-auto">
                                <table className="w-full text-sm border-collapse">
                                    <thead>
                                        <tr>
                                            {preview.headers?.slice(0, 8).map((h, i) => (
                                                <th key={i} className="border border-slate-200 p-2 bg-slate-100 text-left font-bold whitespace-nowrap">
                                                    {h}
                                                </th>
                                            ))}
                                            {preview.headers && preview.headers.length > 8 && (
                                                <th className="border border-slate-200 p-2 bg-slate-100 text-left font-bold">... ({preview.headers.length - 8} kolom lagi)</th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {preview.preview?.slice(0, 5).map((row, i) => (
                                            <tr key={i}>
                                                {row.slice(0, 8).map((col, j) => (
                                                    <td key={j} className="border border-slate-200 p-2 bg-white text-slate-700 whitespace-nowrap truncate">
                                                        {col}
                                                    </td>
                                                ))}
                                                {row.length > 8 && (
                                                    <td className="border border-slate-200 p-2 bg-white text-slate-500 text-sm">
                                                        {row.slice(8).filter(v => v).length} kolom data
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                                <p className="text-sm text-blue-700">
                                    <span className="font-bold">Total baris data:</span> {preview.total_rows}
                                </p>
                                <p className="text-sm text-blue-700 mt-2">
                                    <span className="font-bold">Format file yang diharapkan:</span>
                                    <br />
                                    {preview.format_info}
                                </p>
                            </div>

                            <div className="flex gap-3">
                                <button
                                    onClick={() => setStep(1)}
                                    className="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-semibold"
                                >
                                    Kembali
                                </button>
                                <button
                                    onClick={() => setStep(3)}
                                    className="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold"
                                >
                                    Lanjut ke Konfirmasi
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 3: Confirmation */}
                    {step === 3 && preview && (
                        <div>
                            <h3 className="text-lg font-bold text-slate-800 mb-4">Konfirmasi Import</h3>

                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                                <p className="text-sm text-amber-700 mb-2">
                                    <span className="font-bold">⚠️ Penting:</span>
                                </p>
                                <ul className="text-sm text-amber-700 space-y-1 ml-4 list-disc">
                                    <li>Jadwal untuk {months.find(m => m.value === month)?.label} {year} akan diperbarui/ditimpor</li>
                                    <li>Total baris yang akan diproses: {preview.total_rows}</li>
                                    <li>Jadwal yang sudah ada akan di-update dengan data baru</li>
                                </ul>
                            </div>

                            <div className="flex gap-3">
                                <button
                                    onClick={() => setStep(2)}
                                    className="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-semibold"
                                >
                                    Kembali
                                </button>
                                <button
                                    onClick={handleProcessImport}
                                    disabled={loading}
                                    className="flex-1 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-60 text-white rounded-lg font-semibold"
                                >
                                    {loading ? '🔄 Mengimpor...' : '✓ Lanjutkan Import'}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 4: Result */}
                    {step === 4 && importResult && (
                        <div className="text-center py-8">
                            <div className="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                                <svg className="w-8 h-8 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <h3 className="text-lg font-bold text-slate-800 mb-2">Import Berhasil!</h3>
                            <p className="text-slate-600 mb-6">
                                <span className="font-semibold">{importResult.imported}</span> karyawan berhasil diimport jadwalnya
                            </p>

                            {importResult.errors?.length > 0 && (
                                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left mb-6 max-h-48 overflow-y-auto">
                                    <h4 className="font-bold text-amber-700 mb-2">⚠️ Catatan ({importResult.errors.length})</h4>
                                    <ul className="text-sm text-amber-700 space-y-1">
                                        {importResult.errors.slice(0, 10).map((err, i) => (
                                            <li key={i}>• {err}</li>
                                        ))}
                                        {importResult.errors.length > 10 && (
                                            <li className="font-bold mt-2">... dan {importResult.errors.length - 10} pesan lainnya</li>
                                        )}
                                    </ul>
                                </div>
                            )}

                            <div className="flex gap-3 justify-center">
                                <button
                                    onClick={() => {
                                        setStep(1);
                                        setFile(null);
                                        setPreview(null);
                                        setImportResult(null);
                                    }}
                                    className="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold"
                                >
                                    Import File Lain
                                </button>
                                <a
                                    href="/schedule"
                                    className="px-6 py-2 bg-slate-500 hover:bg-slate-600 text-white rounded-lg font-semibold"
                                >
                                    Kembali ke Jadwal
                                </a>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
