import { useState, useRef, useCallback } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FlashMessage from '@/Components/FlashMessage';
import { Head, router, usePage } from '@inertiajs/react';

export default function AttendanceIndex({ attendances, todayAttendance, todaySchedule, filters }) {
    const { auth } = usePage().props;
    const [cameraOpen, setCameraOpen] = useState(false);
    const [cameraMode, setCameraMode] = useState('in'); // 'in' or 'out'
    const [capturedPhoto, setCapturedPhoto] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const streamRef = useRef(null);

    const [month, setMonth] = useState(filters.month);
    const [year, setYear] = useState(filters.year);

    const isLibur = todaySchedule?.shift?.name === 'Libur';

    const startCamera = useCallback(async (mode) => {
        setCameraMode(mode);
        setCameraOpen(true);
        setCapturedPhoto(null);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 480 }
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
            }
        } catch (err) {
            alert('Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan.');
            setCameraOpen(false);
        }
    }, []);

    const takePhoto = useCallback(() => {
        const video = videoRef.current;
        const canvas = canvasRef.current;
        if (video && canvas) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            const photo = canvas.toDataURL('image/jpeg', 0.8);
            setCapturedPhoto(photo);
        }
    }, []);

    const stopCamera = useCallback(() => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach(t => t.stop());
        }
        setCameraOpen(false);
        setCapturedPhoto(null);
    }, []);

    const submitAttendance = useCallback(() => {
        if (!capturedPhoto) return;
        setIsSubmitting(true);
        const url = cameraMode === 'in' ? '/attendance/clock-in' : '/attendance/clock-out';
        router.post(url, { photo: capturedPhoto }, {
            onFinish: () => {
                setIsSubmitting(false);
                stopCamera();
            },
        });
    }, [capturedPhoto, cameraMode, stopCamera]);

    const filterData = () => {
        router.get('/attendance', { month, year }, { preserveState: true });
    };

    const statusColors = {
        present: 'bg-emerald-100 text-emerald-700',
        late: 'bg-amber-100 text-amber-700',
        absent: 'bg-red-100 text-red-700',
        leave: 'bg-blue-100 text-blue-700',
        sick: 'bg-orange-100 text-orange-700',
    };

    const statusLabels = {
        present: 'Hadir',
        late: 'Terlambat',
        absent: 'Tidak Hadir',
        leave: 'Cuti',
        sick: 'Sakit',
    };

    const canClockIn = !isLibur && (!todayAttendance || !todayAttendance.clock_in);
    const canClockOut = todayAttendance && todayAttendance.clock_in && !todayAttendance.clock_out;

    return (
        <AuthenticatedLayout header="Absensi">
            <Head title="Absensi" />
            <FlashMessage />

            {/* Clock In/Out buttons */}
            {!auth.user.role?.includes('admin') && (
                <div className="bg-white rounded-2xl border border-slate-200/60 p-6 mb-6">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <h3 className="font-bold text-slate-800 text-lg">Absensi Hari Ini</h3>
                            <p className="text-slate-500 text-sm mt-1">
                                {isLibur ? (
                                    <span className="text-orange-600 font-semibold">📅 Anda sedang libur hari ini</span>
                                ) : todayAttendance?.clock_in
                                    ? `Clock In: ${todayAttendance.clock_in}${todayAttendance.clock_out ? ` | Clock Out: ${todayAttendance.clock_out}` : ''}`
                                    : 'Anda belum absen hari ini'
                                }
                            </p>
                        </div>
                        <div className="flex gap-3">
                            {isLibur ? (
                                <div className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-orange-100 text-orange-700">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 015.646 5.646 9 9 0 0120.354 15.354Z" /></svg>
                                    Tidak Ada Jadwal Kerja Karena Anda Sedang Libur
                                </div>
                            ) : (
                                <>
                                    <button
                                        onClick={() => startCamera('in')}
                                        disabled={!canClockIn}
                                        className={`inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                                            canClockIn
                                                ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/30'
                                                : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                        }`}
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        Clock In
                                    </button>
                                    <button
                                        onClick={() => startCamera('out')}
                                        disabled={!canClockOut}
                                        className={`inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                                            canClockOut
                                                ? 'bg-blue-500 hover:bg-blue-600 text-white shadow-lg shadow-blue-500/30'
                                                : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                        }`}
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                        Clock Out
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Camera Modal */}
            {cameraOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-bold text-slate-800">
                                📸 {cameraMode === 'in' ? 'Clock In' : 'Clock Out'} - Foto Absensi
                            </h3>
                            <button onClick={stopCamera} className="p-1 rounded-lg hover:bg-slate-100 text-slate-400">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div className="p-6">
                            {!capturedPhoto ? (
                                <div className="relative">
                                    <video ref={videoRef} autoPlay playsInline muted className="w-full rounded-xl bg-slate-900" style={{ transform: 'scaleX(-1)' }} />
                                    <canvas ref={canvasRef} className="hidden" />
                                    <button
                                        onClick={takePhoto}
                                        className="mt-4 w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
                                    >
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        Ambil Foto
                                    </button>
                                </div>
                            ) : (
                                <div>
                                    <img src={capturedPhoto} alt="Preview" className="w-full rounded-xl" style={{ transform: 'scaleX(-1)' }} />
                                    <div className="flex gap-3 mt-4">
                                        <button
                                            onClick={() => setCapturedPhoto(null)}
                                            className="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-3 rounded-xl font-semibold transition-colors"
                                        >
                                            Ulangi
                                        </button>
                                        <button
                                            onClick={submitAttendance}
                                            disabled={isSubmitting}
                                            className="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-xl font-semibold transition-colors disabled:opacity-50"
                                        >
                                            {isSubmitting ? 'Menyimpan...' : `Kirim ${cameraMode === 'in' ? 'Clock In' : 'Clock Out'}`}
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Filter */}
            <div className="bg-white rounded-2xl border border-slate-200/60 p-4 mb-6">
                <div className="flex flex-wrap items-center gap-3">
                    <select value={month} onChange={e => setMonth(e.target.value)} className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        {['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'].map((m, i) => (
                            <option key={i} value={i + 1}>{m}</option>
                        ))}
                    </select>
                    <select value={year} onChange={e => setYear(e.target.value)} className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        {[2024, 2025, 2026].map(y => (
                            <option key={y} value={y}>{y}</option>
                        ))}
                    </select>
                    <button onClick={filterData} className="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors">
                        Filter
                    </button>
                </div>
            </div>

            {/* Table */}
            <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-slate-50/80">
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                                {auth.user.role === 'admin_sdm' && <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan</th>}
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Clock In</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Clock Out</th>
                                <th className="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {attendances?.data?.length === 0 ? (
                                <tr><td colSpan={5} className="px-6 py-12 text-center text-slate-400">Tidak ada data absensi</td></tr>
                            ) : (
                                attendances?.data?.map((att) => (
                                    <tr key={att.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="px-6 py-3.5 font-medium text-slate-700">
                                            {new Date(att.date).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}
                                        </td>
                                        {auth.user.role === 'admin_sdm' && (
                                            <td className="px-6 py-3.5 text-slate-600">{att.user?.name}</td>
                                        )}
                                        <td className="px-6 py-3.5 text-slate-600 font-mono">{att.clock_in || '-'}</td>
                                        <td className="px-6 py-3.5 text-slate-600 font-mono">{att.clock_out || '-'}</td>
                                        <td className="px-6 py-3.5">
                                            <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[att.status]}`}>
                                                {statusLabels[att.status]}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {attendances?.links && (
                    <div className="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                        <p className="text-xs text-slate-500">
                            Menampilkan {attendances.from}-{attendances.to} dari {attendances.total}
                        </p>
                        <div className="flex gap-1">
                            {attendances.links.map((link, i) => (
                                <button
                                    key={i}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    disabled={!link.url}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
                                        link.active
                                            ? 'bg-emerald-500 text-white'
                                            : link.url
                                                ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                                : 'text-slate-300 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
