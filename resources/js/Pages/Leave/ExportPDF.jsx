import React, { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import RekapCutiPDFEager from '@/Components/RekapCutiPDF';

export default function ExportPDF({ leaves, dateFrom, dateTo, isAdmin, typeLabels }) {
    const fileName = `Rekap_Cuti_${dateFrom.replace(/\s+/g, '_')}_sd_${dateTo.replace(/\s+/g, '_')}.pdf`;
    const [phase, setPhase] = useState('loading');
    const [errMsg, setErrMsg] = useState('');

    useEffect(() => {
        import('@react-pdf/renderer')
            .then(({ pdf }) => pdf(<RekapCutiPDFEager
                leaves={leaves}
                dateFrom={dateFrom}
                dateTo={dateTo}
                isAdmin={isAdmin}
                typeLabels={typeLabels}
            />).toBlob())
            .then(blob => {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                a.click();
                URL.revokeObjectURL(url);
                setPhase('done');
                setTimeout(() => router.visit('/leaves'), 1500);
            })
            .catch(err => {
                setErrMsg(err.message || 'Terjadi kesalahan.');
                setPhase('error');
            });
    }, []);

    return (
        <AuthenticatedLayout header="Mengunduh PDF Rekap Cuti">
            <Head title="Mengunduh PDF" />
            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="text-center">
                    {phase === 'loading' && (
                        <>
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600 mx-auto mb-4"></div>
                            <p className="text-slate-700 font-semibold">Memproses PDF...</p>
                            <p className="text-slate-400 text-sm mt-1">Mohon tunggu sebentar</p>
                        </>
                    )}
                    {phase === 'done' && (
                        <>
                            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <p className="text-slate-700 font-semibold">PDF berhasil diunduh!</p>
                            <p className="text-slate-400 text-sm mt-1">Mengalihkan kembali...</p>
                        </>
                    )}
                    {phase === 'error' && (
                        <>
                            <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                            </div>
                            <p className="text-red-700 font-semibold">Gagal memproses PDF</p>
                            <p className="text-slate-400 text-sm mt-2">{errMsg}</p>
                            <button onClick={() => router.visit('/leaves')} className="mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm transition-colors">
                                Kembali
                            </button>
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
