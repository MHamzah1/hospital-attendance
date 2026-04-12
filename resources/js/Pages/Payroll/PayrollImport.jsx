import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import FlashMessage from '@/Components/FlashMessage';

export default function PayrollImport({ flash }) {
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [mapping, setMapping] = useState({});
    const [loading, setLoading] = useState(false);
    const [month, setMonth] = useState(new Date().getMonth() + 1);
    const [year, setYear] = useState(new Date().getFullYear());
    const [step, setStep] = useState(1);

    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    // Format number untuk display (tanpa desimal)
    const formatNumber = (value) => {
        if (value === null || value === undefined || value === '') return value;
        const num = parseFloat(value);
        if (isNaN(num)) return value;
        // Jika angka bulat atau besar (> 1000), format tanpa desimal
        if (Number.isInteger(num) || num >= 1000) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(num));
        }
        return value;
    };

    // Hanya field potongan, koreksi admin & BPJS. Gaji, tunjangan, lembur otomatis dari sistem saat Generate.
    const payrollFields = [
        { key: 'employee_id',                 label: 'NIP / Employee ID',      aliases: ['nip', 'employeeid', 'id', 'no'] },
        { key: '_nama',                       label: 'Nama (info)',            aliases: ['nama', 'name', 'namakaryawan'] },
        // Tambahan (+)
        { key: 'salary_correction',           label: 'Koreksi Upah (+)',       aliases: ['koreksiupahplus', 'koreksiupah'] },
        { key: 'other_allowance',             label: 'Lain-lain (+)',          aliases: ['lainlainplus', 'lainlain', 'lain_lain', 'other'] },
        // Potongan Admin (-)
        { key: 'cdt_deduction',               label: 'CDT',                   aliases: ['cdt', 'potongancdt'] },
        { key: 'alpha_deduction',             label: 'Alpa',                  aliases: ['alpa', 'alpha', 'potonganalpa'] },
        { key: 'cashbond_deduction',          label: 'Cashbond',              aliases: ['cashbond', 'potongancashbond'] },
        { key: 'piutang_obat_deduction',      label: 'Piutang Obat',         aliases: ['piutangobat', 'potonganobat'] },
        { key: 'salary_correction_deduction', label: 'Koreksi Upah (-)',     aliases: ['koreksiupahmin', 'potongankoreksi', 'koreksi_upah_min'] },
        { key: 'bank_admin_deduction',        label: 'Adm. Bank',            aliases: ['admbank', 'adm_bank', 'adminbank'] },
        { key: 'pph21',                       label: 'PPh 21',               aliases: ['pph21', 'pph', 'pajak'] },
        // BPJS (manual dari template)
        { key: 'bpjs_kesehatan',              label: 'BPJS Kesehatan (1%)',  aliases: ['bpjskesehatan', 'bpjskes', 'bpjs_kesehatan'] },
        { key: 'bpjs_ketenagakerjaan',        label: 'BPJS TK JHT (2%)',    aliases: ['bpjsketenagakerjaan', 'bpjstk', 'bpjsjht', 'bpjs_tk_jht', 'bpjstkjht'] },
        { key: 'bpjs_pensiun_jp',             label: 'BPJS TK JP (1%)',     aliases: ['bpjspensiunjp', 'bpjsjp', 'bpjs_tk_jp', 'bpjstkjp', 'bpjspensiun'] },
    ];

    const handleFileSelect = async (e) => {
        const selectedFile = e.target.files[0];
        if (!selectedFile) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('month', month);
        formData.append('year', year);

        try {
            const response = await fetch('/payroll-import/preview', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: formData,
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => null);
                throw new Error(errData?.message || `Server error ${response.status}`);
            }

            const data = await response.json();
            setFile(selectedFile);
            setPreview(data);

            // Auto-map columns using aliases
            const autoMapping = {};
            data.headers?.forEach((header, idx) => {
                const headerRaw = header?.toString() || '';
                const headerLower = headerRaw.toLowerCase().replace(/[^a-z0-9]/g, '');

                // Special handling: distinguish Koreksi Upah (+) vs (-)
                const hasPlus = headerRaw.includes('(+)');
                const hasMinus = headerRaw.includes('(-)');

                const matchedField = payrollFields.find(f => {
                    if (autoMapping[f.key]) return false; // already mapped

                    // Disambiguate koreksi upah plus vs minus
                    if (headerLower.includes('koreksiupah')) {
                        if (hasPlus && f.key === 'salary_correction') return true;
                        if (hasMinus && f.key === 'salary_correction_deduction') return true;
                        return false;
                    }

                    // Standard alias matching
                    if (f.aliases?.some(alias => headerLower.includes(alias))) return true;
                    if (f.label.toLowerCase().replace(/[^a-z0-9]/g, '') === headerLower) return true;
                    return false;
                });
                if (matchedField) {
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
        formData.append('month', month);
        formData.append('year', year);

        try {
            const response = await fetch('/payroll-import/process', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                setStep(4);
                setTimeout(() => {
                    router.visit('/payroll');
                }, 2000);
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const downloadTemplate = () => {
        window.location.href = '/payroll-import/template';
    };

    return (
        <AuthenticatedLayout header="Import Data Penggajian">
            <Head title="Import Data Penggajian" />
            <FlashMessage flash={flash} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Step Indicator */}
                <div className="flex items-center justify-between mb-8">
                    {[1, 2, 3, 4].map(s => (
                        <div key={s} className={`flex items-center ${s < 4 ? 'flex-1' : ''}`}>
                            <div className={`w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm ${
                                s < step ? 'bg-gray-500 text-white' : s === step ? 'bg-white text-gray-900 ring-2 ring-gray-500 border border-gray-300' : 'bg-slate-100 text-slate-600'
                            }`}>
                                {s < step ? '✓' : s}
                            </div>
                            {s < 4 && (
                                <div className={`flex-1 h-1 mx-3 ${s < step ? 'bg-gray-500' : 'bg-slate-200'}`}></div>
                            )}
                        </div>
                    ))}
                </div>

                {/* Step Labels */}
                <div className="grid grid-cols-4 gap-2 text-center text-xs font-medium text-slate-600 mb-8">
                    <div>1. Upload</div>
                    <div>2. Preview</div>
                    <div>3. Pemetaan</div>
                    <div>4. Selesai</div>
                </div>

                {/* Step 1: Upload */}
                {step === 1 && (
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-8 space-y-6">
                        <div>
                            <h2 className="text-2xl font-bold text-slate-800 mb-2">Import Data Potongan</h2>
                            <p className="text-slate-600">Upload file <strong>DATA PENGGAJIAN.xlsx</strong> untuk import data potongan & koreksi karyawan</p>
                        </div>

                        {/* Info Note */}
                        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800 space-y-1">
                            <p className="font-semibold">Alur Penggajian:</p>
                            <ol className="list-decimal list-inside space-y-0.5">
                                <li><strong>Import</strong> data potongan admin (CDT, Alpa, Cashbond, dll) dari Excel</li>
                                <li><strong>Generate</strong> payroll — sistem otomatis rekap gaji, tunjangan, lembur, absensi, cuti & BPJS</li>
                                <li><strong>Review</strong> & cetak slip gaji</li>
                            </ol>
                        </div>

                        {/* Periode Selection */}
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-2">Bulan</label>
                                <select
                                    value={month}
                                    onChange={(e) => setMonth(Number(e.target.value))}
                                    className="w-full rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    {months.map((m, i) => (
                                        <option key={i} value={i + 1}>{m}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-2">Tahun</label>
                                <select
                                    value={year}
                                    onChange={(e) => setYear(Number(e.target.value))}
                                    className="w-full rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                    {[2024, 2025, 2026, 2027].map(y => (
                                        <option key={y} value={y}>{y}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Template Download */}
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <svg className="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p className="font-semibold text-amber-900">Template DATA PENGGAJIAN</p>
                                    <p className="text-sm text-amber-700">Download template DATA PENGGAJIAN.xlsx</p>
                                </div>
                            </div>
                            <button
                                onClick={downloadTemplate}
                                className="px-5 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700 transition-colors text-sm shrink-0"
                            >
                                Download Template
                            </button>
                        </div>

                        {/* File Upload */}
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-3">Pilih File DATA PENGGAJIAN</label>
                            <div className="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer">
                                <input
                                    type="file"
                                    accept=".csv,.xlsx,.xls,.xlsm"
                                    onChange={handleFileSelect}
                                    disabled={loading}
                                    className="hidden"
                                    id="fileInput"
                                />
                                <label htmlFor="fileInput" className="cursor-pointer block">
                                    <svg className="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p className="text-slate-700 font-medium mb-1">
                                        {loading ? 'Memproses...' : 'Klik atau seret file ke sini'}
                                    </p>
                                    <p className="text-sm text-slate-500">Format: XLSX, XLSM, atau CSV</p>
                                </label>
                            </div>
                        </div>
                    </div>
                )}

                {/* Step 2: Preview */}
                {step === 2 && preview && (
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-8 space-y-6">
                        <div>
                            <h2 className="text-2xl font-bold text-slate-800 mb-2">Preview Data</h2>
                            <p className="text-slate-600">Total baris: {preview.total_rows} | Periode: {months[month - 1]} {year}</p>
                        </div>

                        {/* Preview Table */}
                        <div className="overflow-x-auto border border-slate-200 rounded-xl">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-slate-50 border-b">
                                        {preview.headers?.map((header, idx) => (
                                            <th key={idx} className="px-4 py-3 text-left font-semibold text-slate-700">
                                                {header}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {preview.preview?.map((row, rowIdx) => (
                                        <tr key={rowIdx} className="border-b hover:bg-slate-50">
                                            {row.map((cell, cellIdx) => (
                                                <td key={cellIdx} className="px-4 py-3 text-slate-700">
                                                    {formatNumber(cell)}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={() => { setStep(1); setFile(null); setPreview(null); }}
                                className="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                onClick={() => setStep(3)}
                                className="ml-auto px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors"
                            >
                                Lanjut ke Pemetaan
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 3: Field Mapping */}
                {step === 3 && preview && (
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-8 space-y-6">
                        <div>
                            <h2 className="text-2xl font-bold text-slate-800 mb-2">Pemetaan Kolom</h2>
                            <p className="text-slate-600">Sesuaikan kolom CSV dengan field payroll</p>
                        </div>

                        <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            {payrollFields.map(field => (
                                <div key={field.key}>
                                    <label className="block text-sm font-semibold text-slate-700 mb-2">
                                        {field.label}
                                    </label>
                                    <select
                                        value={mapping[field.key] || ''}
                                        onChange={(e) => {
                                            const newMapping = { ...mapping };
                                            if (e.target.value) {
                                                newMapping[field.key] = parseInt(e.target.value);
                                            } else {
                                                delete newMapping[field.key];
                                            }
                                            setMapping(newMapping);
                                        }}
                                        className="w-full rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                    >
                                        <option value="">-- Abaikan --</option>
                                        {preview.headers?.map((header, idx) => (
                                            <option key={idx} value={idx + 1}>
                                                {header}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            ))}
                        </div>

                        <div className="flex gap-3 pt-4 border-t">
                            <button
                                onClick={() => setStep(2)}
                                className="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors"
                            >
                                Kembali
                            </button>
                            <button
                                onClick={handleProcessImport}
                                disabled={loading}
                                className="ml-auto px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors disabled:opacity-50"
                            >
                                {loading ? 'Mengimpor...' : 'Impor Sekarang'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 4: Done */}
                {step === 4 && (
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-8 text-center space-y-6">
                        <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100">
                            <svg className="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="text-2xl font-bold text-slate-800">Impor Berhasil!</h2>
                            <p className="text-slate-600 mt-2">Data penggajian telah berhasil diimpor ke sistem</p>
                        </div>
                        <p className="text-sm text-slate-500">Redirecting...</p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
