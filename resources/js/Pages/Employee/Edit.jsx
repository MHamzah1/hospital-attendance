import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';

export default function EmployeeEdit({ employee }) {
    const { data, setData, put, processing, errors } = useForm({
        name: employee.name || '',
        email: employee.email || '',
        password: '',
        employee_id: employee.employee_id || '',
        department: employee.department || '',
        position: employee.position || '',
        base_salary: employee.base_salary || '',
        position_allowance: employee.position_allowance || '0',
        meal_allowance: employee.meal_allowance || '0',
        transport_allowance: employee.transport_allowance || '0',
        phone: employee.phone || '',
        address: employee.address || '',
        join_date: employee.join_date || '',
        npwp: employee.npwp || '',
        bpjs_kesehatan: employee.bpjs_kesehatan || '',
        bpjs_ketenagakerjaan: employee.bpjs_ketenagakerjaan || '',
        status: employee.status || 'active',
    });

    const departments = [
        'Rawat Inap', 'Rawat Jalan', 'IGD', 'Poliklinik', 'Farmasi',
        'Laboratorium', 'Radiologi', 'Administrasi', 'Keuangan', 'IT',
        'Kebersihan', 'Keamanan', 'Gizi', 'Rekam Medis', 'CSSD',
    ];

    const submit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id));
    };

    const InputField = ({ label, name, type = 'text', required = false, placeholder = '', prefix = '', hint = '' }) => (
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
            {hint && <p className="text-slate-400 text-xs mt-1">{hint}</p>}
            {errors[name] && <p className="text-red-500 text-xs mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AuthenticatedLayout header="Edit Karyawan">
            <Head title="Edit Karyawan" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center gap-3">
                    <Link href={route('employees.index')} className="p-2 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                        <svg className="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 className="text-2xl font-bold text-slate-800">Edit Data Karyawan</h2>
                        <p className="text-slate-500 mt-0.5">{employee.name} — {employee.employee_id}</p>
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
                            <InputField label="Nama Lengkap" name="name" required />
                            <InputField label="Email" name="email" type="email" required />
                            <InputField label="Password Baru" name="password" type="password" hint="Kosongkan jika tidak ingin mengubah password" />
                            <InputField label="ID Karyawan" name="employee_id" required />
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
                            <InputField label="Jabatan" name="position" required />
                            <InputField label="No. Telepon" name="phone" />
                            <InputField label="Tanggal Bergabung" name="join_date" type="date" required />
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Status</label>
                                <select
                                    value={data.status}
                                    onChange={e => setData('status', e.target.value)}
                                    className="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                >
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div className="mt-4">
                            <label className="block text-sm font-semibold text-slate-700 mb-1.5">Alamat</label>
                            <textarea
                                value={data.address}
                                onChange={e => setData('address', e.target.value)}
                                rows={2}
                                className="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
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
                            <InputField label="Gaji Pokok" name="base_salary" type="number" required prefix="Rp" />
                            <InputField label="Tunjangan Jabatan" name="position_allowance" type="number" prefix="Rp" />
                            <InputField label="Tunjangan Makan (per hari)" name="meal_allowance" type="number" prefix="Rp" />
                            <InputField label="Tunjangan Transport (per hari)" name="transport_allowance" type="number" prefix="Rp" />
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
                            <InputField label="NPWP" name="npwp" />
                            <InputField label="No. BPJS Kesehatan" name="bpjs_kesehatan" />
                            <InputField label="No. BPJS Ketenagakerjaan" name="bpjs_ketenagakerjaan" />
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
                            {processing ? 'Menyimpan...' : 'Update Karyawan'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
