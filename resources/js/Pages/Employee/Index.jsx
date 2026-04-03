import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import FlashMessage from '@/Components/FlashMessage';

export default function EmployeeIndex({ employees, departments, filters, flash }) {
    const [search, setSearch] = useState(filters.search || '');
    const [department, setDepartment] = useState(filters.department || 'all');

    const handleFilter = () => {
        router.get(route('employees.index'), { search, department }, { preserveState: true });
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter') handleFilter();
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount || 0);
    };

    return (
        <AuthenticatedLayout header="Manajemen Karyawan">
            <Head title="Manajemen Karyawan" />
            <FlashMessage flash={flash} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-800">Data Karyawan</h2>
                        <p className="text-slate-500 mt-1">Kelola data karyawan rumah sakit</p>
                    </div>
                    <div className="flex gap-3">
                        <Link
                            href={route('employees.import')}
                            className="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-semibold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all"
                        >
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33A3 3 0 0116.5 19.5H6.75z" />
                            </svg>
                            Import Massal
                        </Link>
                        <Link
                            href={route('employees.create')}
                            className="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all"
                        >
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Tambah Karyawan
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                    <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex-1">
                            <input
                                type="text"
                                placeholder="Cari nama, ID, atau email..."
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                onKeyDown={handleKeyDown}
                                className="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                            />
                        </div>
                        <select
                            value={department}
                            onChange={e => setDepartment(e.target.value)}
                            className="rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                        >
                            <option value="all">Semua Departemen</option>
                            {departments.map(dept => (
                                <option key={dept} value={dept}>{dept}</option>
                            ))}
                        </select>
                        <button
                            onClick={handleFilter}
                            className="px-5 py-2.5 bg-slate-700 text-white rounded-xl font-medium hover:bg-slate-800 transition-all text-sm"
                        >
                            Filter
                        </button>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-emerald-50 rounded-xl">
                                <svg className="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Total Karyawan</p>
                                <p className="text-2xl font-bold text-slate-800">{employees.total}</p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-blue-50 rounded-xl">
                                <svg className="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Departemen</p>
                                <p className="text-2xl font-bold text-slate-800">{departments.length}</p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-3 bg-amber-50 rounded-xl">
                                <svg className="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Aktif</p>
                                <p className="text-2xl font-bold text-slate-800">{employees.data.filter(e => e.status === 'active').length}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-slate-50 border-b border-slate-200/60">
                                    <th className="px-5 py-4 text-left font-semibold text-slate-600">Karyawan</th>
                                    <th className="px-5 py-4 text-left font-semibold text-slate-600">ID</th>
                                    <th className="px-5 py-4 text-left font-semibold text-slate-600">Departemen</th>
                                    <th className="px-5 py-4 text-left font-semibold text-slate-600">Jabatan</th>
                                    <th className="px-5 py-4 text-left font-semibold text-slate-600">Gaji Pokok</th>
                                    <th className="px-5 py-4 text-left font-semibold text-slate-600">Status</th>
                                    <th className="px-5 py-4 text-center font-semibold text-slate-600">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {employees.data.map(employee => (
                                    <tr key={employee.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                                    {employee.name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-slate-800">{employee.name}</p>
                                                    <p className="text-xs text-slate-400">{employee.email}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 font-mono text-slate-600">{employee.employee_id}</td>
                                        <td className="px-5 py-4 text-slate-600">{employee.department}</td>
                                        <td className="px-5 py-4 text-slate-600">{employee.position}</td>
                                        <td className="px-5 py-4 font-mono text-slate-700">{formatCurrency(employee.base_salary)}</td>
                                        <td className="px-5 py-4">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${
                                                employee.status === 'active'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-red-50 text-red-700'
                                            }`}>
                                                {employee.status === 'active' ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4 text-center">
                                            <Link
                                                href={route('employees.edit', employee.id)}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors text-xs font-semibold"
                                            >
                                                <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {employees.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-5 py-12 text-center text-slate-400">
                                            <svg className="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Tidak ada data karyawan ditemukan
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {employees.links && employees.links.length > 3 && (
                        <div className="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                            <p className="text-sm text-slate-500">
                                Menampilkan {employees.from}-{employees.to} dari {employees.total} karyawan
                            </p>
                            <div className="flex gap-1">
                                {employees.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url || '#'}
                                        className={`px-3 py-1.5 text-sm rounded-lg transition-colors ${
                                            link.active
                                                ? 'bg-emerald-600 text-white'
                                                : link.url
                                                    ? 'text-slate-600 hover:bg-slate-100'
                                                    : 'text-slate-300 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
