import React, { lazy, Suspense } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const PDFDownloadLink = lazy(() => import('@react-pdf/renderer').then(m => ({ default: m.PDFDownloadLink })));
const PDFViewer = lazy(() => import('@react-pdf/renderer').then(m => ({ default: m.PDFViewer })));

import RekapLemburPDFEager from '@/Components/RekapLemburPDF';

export default function ExportPDF({ overtimes, dateFrom, dateTo, isAdmin }) {
    const fromFile = dateFrom.replace(/\s+/g, '-');
    const toFile = dateTo.replace(/\s+/g, '-');
    const fileName = `Rekap_Lembur_${fromFile}_sd_${toFile}.pdf`;

    const LoadingSpinner = () => (
        <div className="flex items-center justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-violet-600"></div>
            <span className="ml-3 text-sm text-slate-500">Memuat PDF viewer...</span>
        </div>
    );

    return (
        <AuthenticatedLayout header="Export Rekap Lembur PDF">
            <Head title="Export Rekap Lembur" />

            <div className="max-w-7xl mx-auto">
                {/* Top Bar */}
                <div className="flex items-center justify-between mb-4">
                    <Link href="/overtimes" className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 transition-colors">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
                        Kembali ke Daftar Lembur
                    </Link>

                    <Suspense fallback={<div className="px-5 py-2.5 rounded-xl bg-red-300 text-white text-sm">Memuat...</div>}>
                        <PDFDownloadLink
                            document={<RekapLemburPDFEager overtimes={overtimes} dateFrom={dateFrom} dateTo={dateTo} isAdmin={isAdmin} />}
                            fileName={fileName}
                            className="inline-flex items-center gap-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-lg shadow-red-500/30"
                        >
                            {({ loading }) => (
                                <>
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {loading ? 'Memproses...' : 'Download PDF'}
                                </>
                            )}
                        </PDFDownloadLink>
                    </Suspense>
                </div>

                {/* Info Banner */}
                <div className="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/60 rounded-xl p-4 mb-4 flex items-center gap-3">
                    <div className="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-blue-800">
                            Rekap Lembur — Periode {dateFrom} s/d {dateTo}
                        </p>
                        <p className="text-xs text-blue-600 mt-0.5">
                            Preview dokumen PDF di bawah. Klik tombol Download PDF untuk mengunduh.
                        </p>
                    </div>
                </div>

                {/* PDF Preview */}
                <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden shadow-sm">
                    <div className="bg-slate-800 px-4 py-2 flex items-center gap-2">
                        <div className="flex gap-1.5">
                            <div className="w-3 h-3 rounded-full bg-red-500"></div>
                            <div className="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div className="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <span className="text-xs text-slate-400 ml-2">{fileName}</span>
                    </div>
                    <div style={{ height: '80vh' }}>
                        <Suspense fallback={<LoadingSpinner />}>
                            <PDFViewer width="100%" height="100%" showToolbar={false}>
                                <RekapLemburPDFEager overtimes={overtimes} dateFrom={dateFrom} dateTo={dateTo} isAdmin={isAdmin} />
                            </PDFViewer>
                        </Suspense>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
