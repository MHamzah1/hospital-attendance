import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';

export default function EmployeeCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        employee_id: '',
        department: '',
        position: '',
        base_salary: '',
        position_allowance: '0',
        meal_allowance: '0',
        transport_allowance: '0',
        phone: '',
        address: '',
        join_date: '',
        npwp: '',
        bpjs_kesehatan: '',
        bpjs_ketenagakerjaan: '',
    });

    const departments = [
        'Rawat Inap', 'Rawat Jalan', 'IGD', 'Poliklinik', 'Farmasi',
        'Laboratorium', 'Radiologi', 'Administrasi', 'Keuangan', 'IT',
        'Kebersihan', 'Keamanan', 'Gizi', 'Rekam Medis', 'CSSD',
    ];

    const submit = (e) => {
        e.preventDefault();
        post(route('employees.store'));
    };

    const InputField = ({ label, name, type = 'text', required = false, placeholder = '', prefix = '' }) => (
        <div>
            <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                {label} {required && <span className="text-red-400">*</span>}
            </label>
            <div className="relative">
                {prefix && (
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">{prefix}</span>
                )}
                <input
                    type={type}
                    value={data[name]}
                    onChange={e => setData(name, e.target.value)}
                    className={`w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm ${prefix ? 'pl-10' : ''} ${errors[name] ? 'border-red-300' : ''}`}
                    placeholder={placeholder}
                />
            </div>
            {errors[name] && <p className="text-red-500 text-xs mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AuthenticatedLayout header="Tambah Karyawan">
            <Head title="Tambah Karyawan" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center gap-3">
                    <Link href={route('employees.index')} className="p-2 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                        <svg className="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 className="text-2xl font-bold text-slate-800">Tambah Karyawan Baru</h2>
                        <p className="text-slate-500 mt-0.5">Lengkapi data karyawan di bawah ini</p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Informasi Dasar */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Informasi Dasar
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <InputField label="Nama Lengkap" name="name" required placeholder="Masukkan nama lengkap" />
                            <InputField label="Email" name="email" type="email" required placeholder="email@hospital.com" />
                            <InputField label="Password" name="password" type="password" required placeholder="Minimal 8 karakter" />
                            <InputField label="ID Karyawan" name="employee_id" required placeholder="EMP-001" />
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Departemen <span className="text-red-400">*</span>
                                </label>
                                <select
                                    value={data.department}
                                    onChange={e => setData('department', e.target.value)}
                                    className={`w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm ${errors.department ? 'border-red-300' : ''}`}
                                >
                                    <option value="">Pilih Departemen</option>
                                    {departments.map(dept => (
                                        <option key={dept} value={dept}>{dept}</option>
                                    ))}
                                </select>
                                {errors.department && <p className="text-red-500 text-xs mt-1">{errors.department}</p>}
                            </div>
                            <InputField label="Jabatan" name="position" required placeholder="Perawat, Dokter, dll" />
                            <InputField label="No. Telepon" name="phone" placeholder="08xxxxxxxxxx" />
                            <InputField label="Tanggal Bergabung" name="join_date" type="date" required />
                        </div>
                        <div className="mt-4">
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Alamat</label>
                            <textarea
                                value={data.address}
                                onChange={e => setData('address', e.target.value)}
                                rows={2}
                                className="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                placeholder="Alamat lengkap"
                            />
                        </div>
                    </div>

                    {/* Komponen Gaji */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Komponen Gaji
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <InputField label="Gaji Pokok" name="base_salary" type="number" required placeholder="5000000" prefix="Rp" />
                            <InputField label="Tunjangan Jabatan" name="position_allowance" type="number" placeholder="0" prefix="Rp" />
                            <InputField label="Tunjangan Makan (per hari)" name="meal_allowance" type="number" placeholder="0" prefix="Rp" />
                            <InputField label="Tunjangan Transport (per hari)" name="transport_allowance" type="number" placeholder="0" prefix="Rp" />
                        </div>
                    </div>

                    {/* BPJS & NPWP */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            BPJS & NPWP
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <InputField label="NPWP" name="npwp" placeholder="XX.XXX.XXX.X-XXX.XXX" />
                            <InputField label="No. BPJS Kesehatan" name="bpjs_kesehatan" placeholder="000xxxxxxxxx" />
                            <InputField label="No. BPJS Ketenagakerjaan" name="bpjs_ketenagakerjaan" placeholder="000xxxxxxxxx" />
                        </div>
                    </div>

                    {/* Submit */}
                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('employees.index')}
                            className="px-6 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 font-semibold hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all disabled:opacity-50"
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Karyawan'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
