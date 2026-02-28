import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, Link } from '@inertiajs/react';

export default function EmployeePayroll({ payrolls }) {
    const months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);

    const statusColors = {
        draft: 'bg-slate-100 text-slate-600',
        finalized: 'bg-blue-100 text-blue-700',
        paid: 'bg-emerald-100 text-emerald-700',
    };
    const statusLabels = { draft: 'Draft', finalized: 'Final', paid: 'Dibayar' };

    return (
        <AuthenticatedLayout header="Slip Gaji">
            <Head title="Slip Gaji" />
            <FlashMessage />

            <div className="grid gap-4">
                {payrolls?.data?.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-12 text-center">
                        <div className="text-4xl mb-3">💰</div>
                        <p className="text-slate-500">Belum ada data penggajian</p>
                    </div>
                ) : (
                    payrolls?.data?.map((p) => (
                        <div key={p.id} className="bg-white rounded-2xl border border-slate-200/60 p-6 hover:shadow-lg hover:shadow-slate-200/50 transition-all">
                            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div className="flex items-center gap-4">
                                    <div className="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 font-bold">
                                        {months[p.month]?.substring(0, 3)}
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-slate-800">{months[p.month]} {p.year}</h3>
                                        <div className="flex gap-3 mt-1 text-xs text-slate-500">
                                            <span>Hadir: {p.present_days} hari</span>
                                            <span>Cuti: {p.leave_days} hari</span>
                                            <span>Lembur: {p.overtime_hours} jam</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="text-right">
                                        <p className="text-xs text-slate-500">Take Home Pay</p>
                                        <p className="text-lg font-extrabold text-emerald-600">Rp {fmt(p.net_salary)}</p>
                                    </div>
                                    <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[p.status]}`}>
                                        {statusLabels[p.status]}
                                    </span>
                                    <Link href={`/payroll/${p.id}`} className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium transition-colors">
                                        Detail
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </AuthenticatedLayout>
    );
}
