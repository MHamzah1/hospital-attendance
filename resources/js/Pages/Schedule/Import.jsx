import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import FlashMessage from '@/Components/FlashMessage';

export default function ScheduleImport({ flash }) {
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [mapping, setMapping] = useState({});
    const [loading, setLoading] = useState(false);
    const [step, setStep] = useState(1);
    const [importResult, setImportResult] = useState(null);

    const databaseFields = [
        { key: 'nip', label: 'NIP', aliases: ['nip', 'nipkaryawan', 'nipegawai'] },
        { key: 'date', label: 'Tanggal', aliases: ['tanggal', 'date', 'tgl'] },
        { key: 'shift', label: 'Nama Shift', aliases: ['shift', 'namashift', 'shiftname'] },
    ];

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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
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

            const autoMapping = {};
            data.headers?.forEach((header, idx) => {
                const headerLower = header?.toString().toLowerCase().replace(/[^a-z0-9]/g, '');
                const matchedField = databaseFields.find(f =>
                    f.aliases?.some(alias => headerLower.includes(alias)) ||
                    f.label.toLowerCase().replace(/[^a-z0-9]/g, '') === headerLower
                );
                if (matchedField && !autoMapping[matchedField.key]) {
                    autoMapping[matchedField.key] = idx + 1;
                }
            });
            setMapping(autoMapping);
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
        formData.append('mapping', JSON.stringify(mapping));

        try {
            const response = await fetch('/schedule/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
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

    const handleMappingChange = (field, columnIndex) => {
        setMapping(prev => ({
            ...prev,
            [field]: columnIndex || null
        }));
    };

    return (
        <AuthenticatedLayout header="Import Jadwal Karyawan">
            <Head title="Import Jadwal Karyawan" />
            <FlashMessage />

            <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden max-w-4xl">
                {/* Step Indicator */}
                <div className="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-6">
                    <div className="flex items-center justify-between">
                        {[1, 2, 3, 4].map((s) => (
                            <div key={s} className="flex items-center">
                                <div className={`flex items-center justify-center w-10 h-10 rounded-full font-bold ${
                                    s <= step
                                        ? 'bg-white text-emerald-600'
                                        : 'bg-emerald-400 text-white'
                                }`}>
                                    {s}
                                </div>
                                {s < 4 && <div className={`h-1 w-16 mx-2 ${s < step ? 'bg-white' : 'bg-emerald-400'}`} />}
                            </div>
                        ))}
                    </div>
                    <div className="mt-4 text-white">
                        <p className="font-semibold">
                            {step === 1 && 'Pilih File'}
                            {step === 2 && 'Preview & Mapping'}
                            {step === 3 && 'Konfirmasi'}
                            {step === 4 && 'Selesai'}
                        </p>
                    </div>
                </div>

                <div className="p-6">
                    {/* Step 1: File Upload */}
                    {step === 1 && (
                        <div className="text-center py-8">
                            <svg className="w-16 h-16 mx-auto text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z" />
                            </svg>
                            <h3 className="text-lg font-bold text-slate-800 mb-2">Upload File Excel</h3>
                            <p className="text-slate-500 text-sm mb-6">Format: .xlsx, .xls, atau .csv</p>

                            <label className="inline-block">
                                <input
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={handleFileSelect}
                                    disabled={loading}
                                    className="sr-only"
                                />
                                <span className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold cursor-pointer transition-colors">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Pilih File
                                </span>
                            </label>

                            <a href="/schedule/import/download-template" className="block mt-6 text-emerald-600 hover:text-emerald-700 font-semibold text-sm">
                                ↓ Unduh Template Excel
                            </a>
                        </div>
                    )}

                    {/* Step 2: Preview & Mapping */}
                    {step === 2 && preview && (
                        <div>
                            <h3 className="font-bold text-slate-800 mb-4">Preview Data & Mapping Kolom</h3>

                            <div className="bg-slate-50 rounded-xl p-4 mb-6 overflow-x-auto">
                                <table className="w-full text-sm border-collapse">
                                    <thead>
                                        <tr>
                                            {preview.headers?.map((h, i) => (
                                                <th key={i} className="border border-slate-200 p-2 bg-slate-100 text-left font-bold">
                                                    {h}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {preview.preview?.slice(0, 3).map((row, i) => (
                                            <tr key={i}>
                                                {row.map((col, j) => (
                                                    <td key={j} className="border border-slate-200 p-2 bg-white">
                                                        {col}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <h4 className="font-bold text-slate-800 mb-4">Mapping Kolom Database</h4>
                            <div className="grid grid-cols-1 gap-4 mb-6">
                                {databaseFields.map((field) => (
                                    <div key={field.key} className="flex items-center gap-3">
                                        <label className="min-w-max font-semibold text-slate-700 w-32">
                                            {field.label}
                                        </label>
                                        <select
                                            value={mapping[field.key] || ''}
                                            onChange={(e) => handleMappingChange(field.key, e.target.value ? parseInt(e.target.value) : null)}
                                            className="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-500"
                                        >
                                            <option value="">-- Abaikan Kolom Ini --</option>
                                            {preview.headers?.map((h, i) => (
                                                <option key={i} value={i + 1}>
                                                    Kolom {i + 1}: {h}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                ))}
                            </div>

                            <div className="flex gap-3">
                                <button
                                    onClick={() => { setStep(1); setFile(null); setPreview(null); }}
                                    className="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-semibold"
                                >
                                    Kembali
                                </button>
                                <button
                                    onClick={() => setStep(3)}
                                    className="flex-1 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-semibold"
                                >
                                    Lanjut
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 3: Confirmation */}
                    {step === 3 && preview && (
                        <div>
                            <h3 className="font-bold text-slate-800 mb-4">Konfirmasi Import</h3>

                            <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                                <p className="text-sm text-blue-700">
                                    <span className="font-bold">Total baris data:</span> {preview.total_rows}
                                </p>
                                <p className="text-sm text-blue-700 mt-1">
                                    Silakan klik "Import" untuk memulai proses import
                                </p>
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
                                    {loading ? 'Mengimpor...' : 'Import'}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 4: Result */}
                    {step === 4 && importResult && (
                        <div className="text-center py-4">
                            <div className="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                                <svg className="w-8 h-8 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <h3 className="text-lg font-bold text-slate-800 mb-2">Import Berhasil!</h3>
                            <p className="text-slate-600 mb-4">
                                {importResult.imported} jadwal berhasil diimport
                            </p>

                            {importResult.errors?.length > 0 && (
                                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left mb-6 max-h-48 overflow-y-auto">
                                    <h4 className="font-bold text-amber-700 mb-2">Pesan ({importResult.errors.length})</h4>
                                    <ul className="text-sm text-amber-700 space-y-1">
                                        {importResult.errors.map((err, i) => (
                                            <li key={i}>• {err}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="flex gap-3">
                                <button
                                    onClick={() => {
                                        setStep(1);
                                        setFile(null);
                                        setPreview(null);
                                        setImportResult(null);
                                    }}
                                    className="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-semibold"
                                >
                                    Import File Baru
                                </button>
                                <a href="/schedule" className="flex-1 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-semibold text-center">
                                    Lihat Jadwal
                                </a>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
