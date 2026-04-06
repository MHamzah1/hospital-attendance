import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import FlashMessage from '@/Components/FlashMessage';

export default function BulkImport({ flash }) {
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [mapping, setMapping] = useState({});
    const [loading, setLoading] = useState(false);
    const [step, setStep] = useState(1); // 1: Upload, 2: Preview, 3: Mapping, 4: Done
    const [importResult, setImportResult] = useState(null); // { imported, errors, message }

    const databaseFields = [
        { key: 'nip', label: 'NIP', aliases: ['nip', 'nipkaryawan', 'nipegawai', 'no_induk', 'nomorinduk'] },
        { key: 'name', label: 'Nama', aliases: ['nama', 'name', 'namakaryawan', 'namalengkap', 'namakaryawan', 'nama_karyawan', 'nama_lengkap'] },
        { key: 'gender', label: 'Jenis Kelamin', aliases: ['jeniskelamin', 'gender', 'jk', 'kelamin', 'jenis_kelamin'] },
        { key: 'education', label: 'Pendidikan', aliases: ['pendidikan', 'education', 'pend', 'tingkatpendidikan', 'tingkat_pendidikan'] },
        { key: 'birth_place', label: 'Tempat Lahir', aliases: ['tempatlahir', 'birthplace', 'tmptlahir', 'tempatl ahir', 'tempat_lahir', 'ttl'] },
        { key: 'birth_date', label: 'Tanggal Lahir', aliases: ['tanggallahir', 'tgllahir', 'birthdate', 'tanggal_lahir', 'tgl_lahir', 'dob'] },
        { key: 'address', label: 'Alamat', aliases: ['alamat', 'address', 'alamatdomisili', 'alamat_domisili'] },
        { key: 'city', label: 'Kota', aliases: ['kota', 'city', 'kabupaten', 'kota_kabupaten'] },
        { key: 'phone', label: 'No. HP', aliases: ['nohp', 'phone', 'telepon', 'hp', 'notelepon', 'handphone', 'nomorhp', 'no_hp', 'no_telp', 'nomor_telp'] },
        { key: 'department', label: 'Departemen', aliases: ['departemen', 'department', 'bagian', 'unit', 'departmen'] },
        { key: 'position', label: 'Jabatan', aliases: ['jabatan', 'position', 'posisi', 'job_title', 'jobtitle'] },
        { key: 'base_salary', label: 'Gaji Pokok', aliases: ['gajipokok', 'gaji', 'base_salary', 'basesalary', 'basegaji', 'gajibas', 'gajidasar', 'gaji_pokok'] },
        { key: 'position_allowance', label: 'Tunjangan Jabatan', aliases: ['tunjanganjabatan', 'tunjanganposisi', 'positionallowance', 'jabatanallowance', 'tunjangan_jabatan', 'tunjangan_posisi'] },
        { key: 'functional_allowance', label: 'Tunjangan Fungsional', aliases: ['tunjanganfungsional', 'functionalallowance', 'tunjangan_fungsional', 'fungsi'] },
        { key: 'special_allowance', label: 'Tunjangan Khusus', aliases: ['tunjangankhusus', 'specialallowance', 'tunjangan_khusus', 'khusus'] },
        { key: 'meal_allowance', label: 'Tunjangan Makan', aliases: ['tunjanganmakan', 'tunjanganmakanan', 'mealallowance', 'makanallowance', 'tunjangan_makan'] },
        { key: 'transport_allowance', label: 'Tunjangan Transportasi', aliases: ['tunjangantransportasi', 'tunjanganansportasi', 'transportallowance', 'transportasi', 'tunjangan_transportasi'] },
        { key: 'attendance_allowance', label: 'Tunjangan Kehadiran', aliases: ['tunjangankehadiran', 'attendanceallowance', 'tunjangan_kehadiran', 'kehadiran'] },
        { key: 'join_date', label: 'Tanggal Masuk', aliases: ['tanggalmasuk', 'joindate', 'tglmasuk', 'mulaikerja', 'tglmasukkerja', 'tanggal_masuk', 'tgl_masuk'] },
        { key: 'npwp', label: 'NPWP', aliases: ['npwp', 'no_npwp', 'nonpwp'] },
        { key: 'bpjs_kesehatan', label: 'BPJS Kesehatan', aliases: ['bpjskesehatan', 'bpjskes', 'bpjs_kesehatan', 'bpjs_kes', 'no_bpjs_kesehatan'] },
        { key: 'bpjs_ketenagakerjaan', label: 'BPJS TK', aliases: ['bpjstk', 'bpjsketenagakerjaan', 'bpjstenagakerja', 'bpjs_ketenagakerjaan', 'bpjs_tk', 'no_bpjs_tk'] },
        { key: 'bank_name', label: 'Nama Rekening', aliases: ['namarekening', 'bankname', 'namabank', 'atasnama', 'nama_rekening', 'atas_nama', 'nama_pemilik'] },
        { key: 'bank_account', label: 'Nomor Rekening', aliases: ['nomorrekening', 'norekening', 'norek', 'bankaccount', 'rekening', 'nomor_rekening', 'no_rekening'] },
        { key: 'status', label: 'Status Karyawan', aliases: ['status', 'statuskaryawan', 'sts', 'status_karyawan'] },
        { key: 'jatah_cuti', label: 'Jatah Cuti', aliases: ['jatahcuti', 'jatah_cuti', 'cutipertahun', 'cuti_per_tahun', 'leaveallowance', 'cuti'] },
    ];

    const getCsrfToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            console.warn('CSRF token not found in meta tag');
        }
        return token ||'';
    };

    const handleFileSelect = async (e) => {
        const selectedFile = e.target.files[0];
        if (!selectedFile) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('file', selectedFile);

        try {
            const response = await fetch('/bulk-import/preview', {
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

            // Auto-map columns by name matching using aliases
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
            const response = await fetch('/bulk-import/process', {
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

    const downloadTemplate = () => {
        window.location.href = route('bulk-import.download');
    };

    return (
        <AuthenticatedLayout header="Import Data Karyawan">
            <Head title="Import Data Karyawan" />
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
                            <h2 className="text-2xl font-bold text-slate-800 mb-2">Import Data Karyawan</h2>
                            <p className="text-slate-600">Upload file <strong>DATA KARYAWAN.xlsx</strong> untuk import data karyawan secara massal</p>
                        </div>

                        {/* Template Download */}
                        <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p className="font-semibold text-emerald-900">Template DATA KARYAWAN</p>
                                    <p className="text-sm text-emerald-700">Download template DATA KARYAWAN.xlsx</p>
                                </div>
                            </div>
                            <button
                                onClick={downloadTemplate}
                                className="px-5 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors text-sm shrink-0"
                            >
                                Download Template
                            </button>
                        </div>

                        {/* File Upload */}
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-3">Pilih File DATA KARYAWAN</label>
                            <div className="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer">
                                <input
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
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
                                    <p className="text-sm text-slate-500">Format: Excel (.xlsx) atau CSV</p>
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
                            <p className="text-slate-600">Total baris: {preview.total_rows}</p>
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
                                                    {cell}
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
                                className="ml-auto px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors shadow-lg shadow-emerald-500/30"
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
                            <p className="text-slate-600">Sesuaikan kolom Excel dengan field database</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {databaseFields.map(field => (
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

                        <div className="flex gap-3">
                            <button
                                onClick={() => setStep(2)}
                                className="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors"
                            >
                                Kembali
                            </button>
                            <button
                                onClick={handleProcessImport}
                                disabled={loading}
                                className="ml-auto px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors disabled:opacity-50 shadow-lg shadow-emerald-500/30"
                            >
                                {loading ? 'Mengimpor...' : 'Impor Sekarang'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 4: Done */}
                {step === 4 && importResult && (
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-8 space-y-6">
                        {/* Success Icon */}
                        <div className="text-center">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
                                <svg className="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-slate-800">Import Selesai!</h2>
                        </div>

                        {/* Result Summary */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                                <p className="text-3xl font-bold text-emerald-700">{importResult.imported}</p>
                                <p className="text-sm text-emerald-600 mt-1">Berhasil Diimport</p>
                            </div>
                            <div className={`${importResult.errors?.length > 0 ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200'} border rounded-xl p-4 text-center`}>
                                <p className={`text-3xl font-bold ${importResult.errors?.length > 0 ? 'text-red-700' : 'text-slate-400'}`}>
                                    {importResult.errors?.length || 0}
                                </p>
                                <p className={`text-sm mt-1 ${importResult.errors?.length > 0 ? 'text-red-600' : 'text-slate-500'}`}>Gagal</p>
                            </div>
                        </div>

                        {/* Error Details */}
                        {importResult.errors?.length > 0 && (
                            <div className="bg-red-50 border border-red-200 rounded-xl p-4">
                                <h3 className="font-semibold text-red-800 mb-2">Detail Baris Gagal:</h3>
                                <div className="max-h-48 overflow-y-auto space-y-1">
                                    {importResult.errors.map((err, idx) => (
                                        <p key={idx} className="text-sm text-red-700">{err}</p>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Action Button */}
                        <div className="flex justify-center gap-3 pt-2">
                            <button
                                onClick={() => {
                                    setStep(1);
                                    setFile(null);
                                    setPreview(null);
                                    setImportResult(null);
                                }}
                                className="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors"
                            >
                                Import Lagi
                            </button>
                            <button
                                onClick={() => router.visit('/employees')}
                                className="px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors shadow-lg shadow-emerald-500/30"
                            >
                                Lihat Data Karyawan
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
