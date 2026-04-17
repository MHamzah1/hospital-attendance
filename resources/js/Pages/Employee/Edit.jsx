import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';

function InputField({ label, name, type = 'text', required = false, placeholder = '', prefix = '', hint = '', data, setData, errors }) {
    return (
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
}

export default function EmployeeEdit({ employee, departments, units, jobPositions }) {
    const { data, setData, put, processing, errors } = useForm({
        // Informasi Dasar
        name: employee.name || '',
        password: '',
        nip: employee.nip || '',
        gender: employee.gender || '',
        education: employee.education || '',
        birth_place: employee.birth_place || '',
        birth_date: employee.birth_date || '',
        
        // Informasi Kontak & Departemen
        phone: employee.phone || '',
        address: employee.address || '',
        city: employee.city || '',
        department: employee.department || '',
        department_id: employee.department_id || '',
        unit: employee.unit || '',
        unit_id: employee.unit_id || '',
        position: employee.position || '',
        job_position_id: employee.job_position_id || '',
        approval_role: employee.approval_role || 'staf',
        join_date: employee.join_date ? employee.join_date.split('T')[0] : '',
        status: employee.status || 'active',
        jatah_cuti: employee.jatah_cuti ?? 12,
        
        // Komponen Gaji (7 fields)
        base_salary: parseFloat(employee.base_salary) || '',
        position_allowance: parseFloat(employee.position_allowance) || 0,
        functional_allowance: parseFloat(employee.functional_allowance) || 0,
        special_allowance: parseFloat(employee.special_allowance) || 0,
        meal_allowance: parseFloat(employee.meal_allowance) || 0,
        transport_allowance: parseFloat(employee.transport_allowance) || 0,
        attendance_allowance: parseFloat(employee.attendance_allowance) || 0,
        
        // Dokumen & Bank
        npwp: employee.npwp || '',
        bpjs_kesehatan: employee.bpjs_kesehatan || '',
        bpjs_ketenagakerjaan: employee.bpjs_ketenagakerjaan || '',
        bank_name: employee.bank_name || '',
        bank_account: employee.bank_account || '',
    });

    const filteredUnits = units?.filter(u => u.department_id == data.department_id) || [];

    const submit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id));
    };

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
                            <InputField data={data} setData={setData} errors={errors} label="NIP" name="nip" required />
                            <InputField data={data} setData={setData} errors={errors} label="Nama Lengkap" name="name" required />
                            <InputField data={data} setData={setData} errors={errors} label="Password Baru" name="password" type="password" hint="Kosongkan jika tidak ingin mengubah password" />
                            
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Jenis Kelamin
                                </label>
                                <select
                                    value={data.gender}
                                    onChange={e => setData('gender', e.target.value)}
                                    className="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                >
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <InputField data={data} setData={setData} errors={errors} label="Pendidikan" name="education" />
                            <InputField data={data} setData={setData} errors={errors} label="Tempat Lahir" name="birth_place" />
                            <InputField data={data} setData={setData} errors={errors} label="Tanggal Lahir" name="birth_date" type="date" />
                        </div>
                    </div>

                    {/* Informasi Kontak & Departemen */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
                            </svg>
                            Informasi Kontak & Pekerjaan
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <InputField data={data} setData={setData} errors={errors} label="No. Telepon" name="phone" />
                            <InputField data={data} setData={setData} errors={errors} label="Kota" name="city" />
                            
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Departemen <span className="text-red-400">*</span>
                                </label>
                                <select
                                    value={data.department_id}
                                    onChange={e => { setData(d => ({ ...d, department_id: e.target.value, unit_id: '' })); }}
                                    className={`w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm ${errors.department_id ? 'border-red-300' : ''}`}
                                >
                                    <option value="">Pilih Departemen</option>
                                    {departments?.map(dept => (
                                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                                    ))}
                                </select>
                                {errors.department_id && <p className="text-red-500 text-xs mt-1">{errors.department_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Unit
                                </label>
                                <select
                                    value={data.unit_id}
                                    onChange={e => setData('unit_id', e.target.value)}
                                    className={`w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm ${errors.unit_id ? 'border-red-300' : ''}`}
                                    disabled={!data.department_id}
                                >
                                    <option value="">Pilih Unit</option>
                                    {filteredUnits.map(u => (
                                        <option key={u.id} value={u.id}>{u.name}</option>
                                    ))}
                                </select>
                                {errors.unit_id && <p className="text-red-500 text-xs mt-1">{errors.unit_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Jenis Jabatan <span className="text-red-400">*</span>
                                </label>
                                <select
                                    value={data.job_position_id}
                                    onChange={e => {
                                        const selectedId = e.target.value;
                                        const selectedPosition = jobPositions?.find(p => String(p.id) === String(selectedId));
                                        setData('job_position_id', selectedId);
                                        setData('position', selectedPosition?.name || '');
                                    }}
                                    className={`w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm ${errors.job_position_id || errors.position ? 'border-red-300' : ''}`}
                                >
                                    <option value="">Pilih Jenis Jabatan</option>
                                    {jobPositions?.map(position => (
                                        <option key={position.id} value={position.id}>{position.name}</option>
                                    ))}
                                </select>
                                {(errors.job_position_id || errors.position) && <p className="text-red-500 text-xs mt-1">{errors.job_position_id || errors.position}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Hak Akses Approval <span className="text-red-400">*</span>
                                </label>
                                <select
                                    value={data.approval_role}
                                    onChange={e => setData('approval_role', e.target.value)}
                                    className="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                >
                                    <option value="staf">Staf (Tidak bisa approve)</option>
                                    <option value="koordinator">Koordinator (Approve level 1)</option>
                                    <option value="manajer">Manager (Approve level 2)</option>
                                    <option value="direktur">Kantor (Langsung ke Admin)</option>
                                </select>
                                <p className="text-slate-400 text-xs mt-1">Tentukan hak approval untuk pengajuan cuti & lembur di unit yang sama</p>
                                {errors.approval_role && <p className="text-red-500 text-xs mt-1">{errors.approval_role}</p>}
                            </div>
                            <InputField data={data} setData={setData} errors={errors} label="Tanggal Bergabung" name="join_date" type="date" required />
                            <InputField data={data} setData={setData} errors={errors} label="Jatah Cuti (hari/tahun)" name="jatah_cuti" type="number" hint="Default 12 hari per tahun" />
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

                    {/* Komponen Gaji - 6 Tunjangan + Gaji Pokok */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Komponen Gaji
                        </h3>
                        
                        <div className="space-y-4">
                            {/* Gaji Pokok */}
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-3 pb-3 border-b border-slate-200">
                                    Gaji Pokok
                                </label>
                                <div className="grid grid-cols-1 gap-4">
                                    <InputField data={data} setData={setData} errors={errors} label="Gaji Pokok" name="base_salary" type="number" required prefix="Rp" />
                                </div>
                            </div>
                            
                            {/* Tunjangan */}
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-3 pb-3 border-b border-slate-200">
                                    Tunjangan (6 Jenis)
                                </label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <InputField data={data} setData={setData} errors={errors} label="1. Tunjangan Jabatan" name="position_allowance" type="number" prefix="Rp" />
                                    <InputField data={data} setData={setData} errors={errors} label="2. Tunjangan Fungsional" name="functional_allowance" type="number" prefix="Rp" />
                                    <InputField data={data} setData={setData} errors={errors} label="3. Tunjangan Khusus" name="special_allowance" type="number" prefix="Rp" />
                                    <InputField data={data} setData={setData} errors={errors} label="4. Tunjangan Makan (per bulan)" name="meal_allowance" type="number" prefix="Rp" />
                                    <InputField data={data} setData={setData} errors={errors} label="5. Tunjangan Transport (per bulan)" name="transport_allowance" type="number" prefix="Rp" />
                                    <InputField data={data} setData={setData} errors={errors} label="6. Tunjangan Kehadiran" name="attendance_allowance" type="number" prefix="Rp" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* BPJS, NPWP & Bank */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            BPJS, NPWP & Data Bank
                        </h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-3 pb-3 border-b border-slate-200">
                                    Dokumen & Asuransi
                                </label>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <InputField data={data} setData={setData} errors={errors} label="NPWP" name="npwp" />
                                    <InputField data={data} setData={setData} errors={errors} label="No. BPJS Kesehatan" name="bpjs_kesehatan" />
                                    <InputField data={data} setData={setData} errors={errors} label="No. BPJS Ketenagakerjaan" name="bpjs_ketenagakerjaan" />
                                </div>
                            </div>
                            
                            <div>
                                <label className="block text-sm font-semibold text-slate-700 mb-3 pb-3 border-b border-slate-200">
                                    Data Rekening Bank
                                </label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <InputField data={data} setData={setData} errors={errors} label="Nama Bank" name="bank_name" />
                                    <InputField data={data} setData={setData} errors={errors} label="Nomor Rekening" name="bank_account" />
                                </div>
                            </div>
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
