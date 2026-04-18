import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function DepartmentIndex({ departments, managers, jobPositions }) {
    const [expandedDept, setExpandedDept] = useState(null);
    const [newDepartmentName, setNewDepartmentName] = useState('');
    const [newJobPositionName, setNewJobPositionName] = useState('');
    const [newUnitNames, setNewUnitNames] = useState({});

    const handleManagerChange = (unitId, managerId) => {
        router.put(`/departments/units/${unitId}/manager`, {
            manager_id: managerId || null,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSyncFromExcel = () => {
        router.post(route('departments.syncFromExcel'), {}, { preserveScroll: true });
    };

    const handleAddDepartment = () => {
        if (!newDepartmentName.trim()) return;

        router.post(route('departments.store'), {
            name: newDepartmentName,
        }, {
            preserveScroll: true,
            onSuccess: () => setNewDepartmentName(''),
        });
    };

    const handleDeleteDepartment = (departmentId, departmentName) => {
        if (!confirm(`Hapus departemen ${departmentName}?`)) return;
        router.delete(route('departments.destroy', departmentId), { preserveScroll: true });
    };

    const handleAddUnit = (departmentId) => {
        const name = (newUnitNames[departmentId] || '').trim();
        if (!name) return;

        router.post(route('departments.units.store'), {
            department_id: departmentId,
            name,
        }, {
            preserveScroll: true,
            onSuccess: () => setNewUnitNames(prev => ({ ...prev, [departmentId]: '' })),
        });
    };

    const handleDeleteUnit = (unitId, unitName) => {
        if (!confirm(`Hapus unit ${unitName}?`)) return;
        router.delete(route('departments.units.destroy', unitId), { preserveScroll: true });
    };

    const handleAddJobPosition = () => {
        if (!newJobPositionName.trim()) return;

        router.post(route('departments.jobPositions.store'), {
            name: newJobPositionName,
        }, {
            preserveScroll: true,
            onSuccess: () => setNewJobPositionName(''),
        });
    };

    const handleDeleteJobPosition = (jobPositionId, jobPositionName) => {
        if (!confirm(`Hapus jenis jabatan ${jobPositionName}?`)) return;
        router.delete(route('departments.jobPositions.destroy', jobPositionId), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header="Kelola Departemen & Unit">
            <Head title="Kelola Departemen & Unit" />
            <FlashMessage />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">Departemen & Unit</h2>
                    <p className="text-slate-500 mt-1">Kelola struktur organisasi dan assignment manager per unit</p>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <div>
                        <p className="font-semibold text-slate-800">Sinkron Master dari Excel</p>
                        <p className="text-sm text-slate-500">Baca data Departemen, Unit, dan Jabatan dari file DEPARTEMEN.xlsx</p>
                    </div>
                    <button
                        type="button"
                        onClick={handleSyncFromExcel}
                        className="px-4 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Sinkron dari DEPARTEMEN.xlsx
                    </button>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-emerald-50 rounded-xl">
                                <svg className="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Total Departemen</p>
                                <p className="text-2xl font-bold text-slate-800">{departments.length}</p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-blue-50 rounded-xl">
                                <svg className="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Total Unit</p>
                                <p className="text-2xl font-bold text-slate-800">{departments.reduce((sum, d) => sum + (d.units?.length || 0), 0)}</p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-purple-50 rounded-xl">
                                <svg className="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Manager Tersedia</p>
                                <p className="text-2xl font-bold text-slate-800">{managers.length}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800">Tambah Departemen</h3>
                        <p className="text-sm text-slate-500 mt-1">Tambahkan departemen baru jika belum ada.</p>
                        <div className="mt-4 flex gap-2">
                            <input
                                type="text"
                                value={newDepartmentName}
                                onChange={(e) => setNewDepartmentName(e.target.value)}
                                placeholder="Contoh: PENUNJANG MEDIS"
                                className="flex-1 rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                            />
                            <button
                                type="button"
                                onClick={handleAddDepartment}
                                className="px-4 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700"
                            >
                                Tambah
                            </button>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <h3 className="text-lg font-bold text-slate-800">Tambah Jenis Jabatan</h3>
                        <p className="text-sm text-slate-500 mt-1">Kelola master jabatan yang dipakai di data karyawan.</p>
                        <div className="mt-4 flex gap-2">
                            <input
                                type="text"
                                value={newJobPositionName}
                                onChange={(e) => setNewJobPositionName(e.target.value.toUpperCase())}
                                placeholder="Contoh: KOORDINATOR RAWAT INAP"
                                className="flex-1 rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                            />
                            <button
                                type="button"
                                onClick={handleAddJobPosition}
                                className="px-4 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700"
                            >
                                Tambah
                            </button>
                        </div>
                    </div>
                </div>

                {/* Department Cards */}
                <div className="space-y-4">
                    {departments.map(dept => (
                        <div key={dept.id} className="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
                            <button
                                onClick={() => setExpandedDept(expandedDept === dept.id ? null : dept.id)}
                                className="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50/50 transition-colors"
                            >
                                <div className="flex items-center gap-4">
                                    <div className="p-2.5 bg-emerald-50 rounded-xl">
                                        <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div className="text-left">
                                        <h3 className="text-lg font-bold text-slate-800">{dept.name}</h3>
                                        <p className="text-sm text-slate-500">
                                            {dept.units?.length || 0} unit · {dept.users_count} karyawan
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleDeleteDepartment(dept.id, dept.name);
                                        }}
                                        className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-700 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                    <svg className={`w-5 h-5 text-slate-400 transition-transform ${expandedDept === dept.id ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            {expandedDept === dept.id && (
                                <div className="border-t border-slate-100">
                                    <div className="px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                                        <div className="flex flex-col sm:flex-row gap-2">
                                            <input
                                                type="text"
                                                value={newUnitNames[dept.id] || ''}
                                                onChange={(e) => setNewUnitNames(prev => ({ ...prev, [dept.id]: e.target.value }))}
                                                placeholder={`Tambah unit baru untuk ${dept.name}`}
                                                className="flex-1 rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => handleAddUnit(dept.id)}
                                                className="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"
                                            >
                                                Tambah Unit
                                            </button>
                                        </div>
                                    </div>

                                    {dept.units?.length > 0 ? (
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="bg-slate-50/80">
                                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Unit</th>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Jumlah Karyawan</th>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Manager Penanggung Jawab</th>
                                                    <th className="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {dept.units.map(unit => (
                                                    <tr key={unit.id} className="hover:bg-slate-50/50">
                                                        <td className="px-6 py-3.5 font-medium text-slate-800">{unit.name}</td>
                                                        <td className="px-6 py-3.5 text-slate-600">{unit.users_count} orang</td>
                                                        <td className="px-6 py-3.5">
                                                            <select
                                                                value={unit.manager_id || ''}
                                                                onChange={(e) => handleManagerChange(unit.id, e.target.value)}
                                                                className="w-full max-w-xs rounded-lg border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                                            >
                                                                <option value="">— Belum ditentukan —</option>
                                                                {managers.map(m => (
                                                                    <option key={m.id} value={m.id}>
                                                                        {m.name} ({m.position})
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </td>
                                                        <td className="px-6 py-3.5 text-center">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteUnit(unit.id, unit.name)}
                                                                className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-700 hover:bg-red-100"
                                                            >
                                                                Hapus
                                                            </button>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <div className="px-6 py-8 text-center text-slate-400">
                                            Departemen ini tidak memiliki unit
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                <div className="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-800">Master Jenis Jabatan</h3>
                        <p className="text-sm text-slate-500 mt-1">Admin dapat menambah atau menghapus jenis jabatan yang sudah/belum ada.</p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-slate-50/80">
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nama Jabatan</th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Dipakai Karyawan</th>
                                    <th className="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {jobPositions?.map((position) => (
                                    <tr key={position.id} className="hover:bg-slate-50/50">
                                        <td className="px-6 py-3.5 font-medium text-slate-800">{position.name?.toUpperCase()}</td>
                                        <td className="px-6 py-3.5 text-slate-600">{position.users_count} orang</td>
                                        <td className="px-6 py-3.5 text-center">
                                            <button
                                                type="button"
                                                onClick={() => handleDeleteJobPosition(position.id, position.name)}
                                                className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-700 hover:bg-red-100"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                ))}

                                {(!jobPositions || jobPositions.length === 0) && (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-8 text-center text-slate-400">Belum ada jenis jabatan.</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Info Box */}
                <div className="bg-blue-50 border border-blue-200 rounded-2xl p-5">
                    <h4 className="font-semibold text-blue-800 mb-2 flex items-center gap-2">
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Tentang Alur Approval
                    </h4>
                    <ul className="text-sm text-blue-700 space-y-1 ml-7 list-disc">
                        <li><strong>Staf</strong> mengajukan → Koordinator di unit yang sama → Manager yang ditugaskan → Admin</li>
                        <li><strong>Staf Unit KANTOR</strong> mengajukan → langsung ke Admin (Approval Level 3)</li>
                        <li><strong>Koordinator</strong> mengajukan → Manager yang ditugaskan → Admin</li>
                        <li><strong>Manager / Direktur</strong> mengajukan → Langsung ke Admin</li>
                        <li>Assign manager di kolom "Manager Penanggung Jawab" agar alur approval berjalan otomatis</li>
                    </ul>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
