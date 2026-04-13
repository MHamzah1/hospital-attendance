import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function DepartmentIndex({ departments, managers }) {
    const [expandedDept, setExpandedDept] = useState(null);

    const handleManagerChange = (unitId, managerId) => {
        router.put(`/departments/units/${unitId}/manager`, {
            manager_id: managerId || null,
        }, { preserveState: true, preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header="Kelola Departemen & Unit">
            <Head title="Kelola Departemen & Unit" />
            <FlashMessage />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">Departemen & Unit</h2>
                    <p className="text-slate-500 mt-1">Kelola struktur organisasi dan assignment manajer per unit</p>
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
                                <p className="text-sm text-slate-500">Manajer Tersedia</p>
                                <p className="text-2xl font-bold text-slate-800">{managers.length}</p>
                            </div>
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
                                <svg className={`w-5 h-5 text-slate-400 transition-transform ${expandedDept === dept.id ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {expandedDept === dept.id && (
                                <div className="border-t border-slate-100">
                                    {dept.units?.length > 0 ? (
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="bg-slate-50/80">
                                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Unit</th>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Jumlah Karyawan</th>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Manajer Penanggung Jawab</th>
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

                {/* Info Box */}
                <div className="bg-blue-50 border border-blue-200 rounded-2xl p-5">
                    <h4 className="font-semibold text-blue-800 mb-2 flex items-center gap-2">
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Tentang Alur Approval
                    </h4>
                    <ul className="text-sm text-blue-700 space-y-1 ml-7 list-disc">
                        <li><strong>Staf</strong> mengajukan → Koordinator di unit yang sama → Manajer yang ditugaskan → Admin</li>
                        <li><strong>Koordinator</strong> mengajukan → Manajer yang ditugaskan → Admin</li>
                        <li><strong>Manajer / Direktur</strong> mengajukan → Langsung ke Admin</li>
                        <li>Assign manajer di kolom "Manajer Penanggung Jawab" agar alur approval berjalan otomatis</li>
                    </ul>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
