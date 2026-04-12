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

    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

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
        router.get('/attendance', { date_from: dateFrom, date_to: dateTo }, { preserveState: true });
    };

    const buildExportUrl = (type) => {
        const params = new URLSearchParams({ date_from: dateFrom, date_to: dateTo });
        return `/attendance/export-${type}?${params.toString()}`;
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

            {/* Filter & Export */}
            <div className="bg-white rounded-2xl border border-slate-200/60 p-4 mb-6">
                <div className="flex flex-wrap items-end gap-3">
                    <div className="flex flex-col gap-1">
                        <label className="text-xs font-medium text-slate-500">Dari Tanggal</label>
                        <input
                            type="date"
                            value={dateFrom}
                            onChange={e => setDateFrom(e.target.value)}
                            className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-xs font-medium text-slate-500">Sampai Tanggal</label>
                        <input
                            type="date"
                            value={dateTo}
                            onChange={e => setDateTo(e.target.value)}
                            className="rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        />
                    </div>
                    <button
                        onClick={filterData}
                        className="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm3 4a1 1 0 011-1h10a1 1 0 010 2H7a1 1 0 01-1-1zm4 4a1 1 0 011-1h2a1 1 0 010 2h-2a1 1 0 01-1-1z" /></svg>
                        Filter
                    </button>

                    <div className="flex gap-2 ml-auto">
                        <a
                            href={buildExportUrl('excel')}
                            className="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export Excel
                        </a>
                        <a
                            href={buildExportUrl('pdf')}
                            className="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            Export PDF
                        </a>
                    </div>
                </div>
            </div>

            {/* Attendance List */}
            <div className="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">

                {attendances?.data?.length === 0 ? (
                    <p className="px-6 py-12 text-center text-slate-400">Tidak ada data absensi</p>
                ) : (<>
                    {/* ── Mobile cards (hidden on md+) ── */}
                    <div className="md:hidden divide-y divide-slate-100">
                        {attendances?.data?.map((att) => (
                            <div key={att.id} className="p-4">
                                {/* Row 1: date + status */}
                                <div className="flex items-start justify-between mb-3">
                                    <div>
                                        <p className="font-semibold text-slate-800 text-sm">
                                            {new Date(att.date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' })}
                                        </p>
                                        {auth.user.role === 'admin_sdm' && (
                                            <p className="text-xs text-slate-500 mt-0.5">{att.user?.name}</p>
                                        )}
                                        {att.shift && (
                                            <p className="text-xs text-indigo-600 font-medium mt-0.5">
                                                {att.shift.name}
                                                {att.shift.start_time && att.shift.end_time && (
                                                    <span className="text-slate-400 font-normal ml-1">
                                                        ({att.shift.start_time.slice(0,5)} – {att.shift.end_time.slice(0,5)})
                                                    </span>
                                                )}
                                            </p>
                                        )}
                                    </div>
                                    <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0 ml-2 ${statusColors[att.status]}`}>
                                        {statusLabels[att.status]}
                                    </span>
                                </div>

                                {/* Row 2: clock in + clock out side by side */}
                                <div className="flex gap-3">
                                    {/* Clock In */}
                                    <div className="flex-1 bg-emerald-50 border border-emerald-100 rounded-xl p-3 flex items-center gap-3">
                                        {att.photo_in_url ? (
                                            <button
                                                onClick={() => { const m=document.createElement('div'); m.innerHTML=`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="this.parentElement.remove()"><div class="bg-white rounded-2xl max-w-sm w-full overflow-hidden shadow-2xl" onclick="event.stopPropagation()"><div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between"><h3 class="font-bold text-slate-800 text-sm">Foto Clock In</h3><button onclick="this.closest('.fixed').parentElement.remove()" class="text-slate-400 text-lg leading-none">✕</button></div><div class="p-4"><img src="${att.photo_in_url}" alt="Clock In" class="w-full rounded-lg"/></div></div></div>`; document.body.appendChild(m); }}
                                                className="flex-shrink-0 rounded-lg overflow-hidden border-2 border-emerald-400 hover:opacity-80 transition-opacity"
                                            >
                                                <img src={att.photo_in_url} alt="Clock In" className="w-12 h-12 object-cover" />
                                            </button>
                                        ) : (
                                            <div className="flex-shrink-0 w-12 h-12 rounded-lg border-2 border-slate-200 bg-white flex items-center justify-center">
                                                <svg className="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                                            </div>
                                        )}
                                        <div className="min-w-0">
                                            <p className="text-xs text-emerald-700 font-semibold">Clock In</p>
                                            <p className="text-sm font-mono font-bold text-slate-800">{att.clock_in || '-'}</p>
                                        </div>
                                    </div>

                                    {/* Clock Out */}
                                    <div className="flex-1 bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center gap-3">
                                        {att.photo_out_url ? (
                                            <button
                                                onClick={() => { const m=document.createElement('div'); m.innerHTML=`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="this.parentElement.remove()"><div class="bg-white rounded-2xl max-w-sm w-full overflow-hidden shadow-2xl" onclick="event.stopPropagation()"><div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between"><h3 class="font-bold text-slate-800 text-sm">Foto Clock Out</h3><button onclick="this.closest('.fixed').parentElement.remove()" class="text-slate-400 text-lg leading-none">✕</button></div><div class="p-4"><img src="${att.photo_out_url}" alt="Clock Out" class="w-full rounded-lg"/></div></div></div>`; document.body.appendChild(m); }}
                                                className="flex-shrink-0 rounded-lg overflow-hidden border-2 border-blue-400 hover:opacity-80 transition-opacity"
                                            >
                                                <img src={att.photo_out_url} alt="Clock Out" className="w-12 h-12 object-cover" />
                                            </button>
                                        ) : (
                                            <div className="flex-shrink-0 w-12 h-12 rounded-lg border-2 border-slate-200 bg-white flex items-center justify-center">
                                                <svg className="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                            </div>
                                        )}
                                        <div className="min-w-0">
                                            <p className="text-xs text-blue-700 font-semibold">Clock Out</p>
                                            <p className="text-sm font-mono font-bold text-slate-800">{att.clock_out || '-'}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Late duration */}
                                {att.status === 'late' && (
                                    <div className="mt-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                                        <p className="text-xs font-semibold text-red-700 whitespace-nowrap">
                                            {att.late_duration || 'Jadwal tidak ditemukan'}
                                        </p>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    {/* ── Desktop table (hidden on mobile) ── */}
                    <div className="hidden md:block overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-slate-50/80">
                                    <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap w-36">Tanggal</th>
                                    {auth.user.role === 'admin_sdm' && <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-40">Karyawan</th>}
                                    <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-28">Shift</th>
                                    <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-32">Jam</th>
                                    <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Clock In</th>
                                    <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Clock Out</th>
                                    <th className="px-4 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-32">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {attendances?.data?.map((att) => (
                                    <tr key={att.id} className="hover:bg-slate-50/50 transition-colors">
                                        <td className="px-4 py-3.5 font-medium text-slate-700 whitespace-nowrap">
                                            {new Date(att.date).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}
                                        </td>
                                        {auth.user.role === 'admin_sdm' && (
                                            <td className="px-4 py-3.5 text-slate-700 font-medium">{att.user?.name}</td>
                                        )}
                                        <td className="px-4 py-3.5">
                                            <span className="text-slate-700 font-medium">{att.shift?.name || <span className="text-slate-300">-</span>}</span>
                                        </td>
                                        <td className="px-4 py-3.5 whitespace-nowrap">
                                            {att.shift?.start_time && att.shift?.end_time
                                                ? <span className="text-slate-600 font-mono text-xs">{att.shift.start_time.slice(0,5)} – {att.shift.end_time.slice(0,5)}</span>
                                                : <span className="text-slate-300">-</span>
                                            }
                                        </td>
                                        <td className="px-4 py-3.5">
                                            <div className="flex items-center gap-2">
                                                <span className="text-slate-600 font-mono whitespace-nowrap">{att.clock_in || '-'}</span>
                                                {att.photo_in_url ? (
                                                    <button onClick={() => { const m=document.createElement('div'); m.innerHTML=`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="this.parentElement.remove()"><div class="bg-white rounded-2xl max-w-2xl w-full overflow-hidden shadow-2xl" onclick="event.stopPropagation()"><div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-bold text-slate-800">Foto Clock In</h3><button onclick="this.closest('.fixed').parentElement.remove()" class="text-slate-400 hover:text-slate-600">✕</button></div><div class="p-6"><img src="${att.photo_in_url}" alt="Clock In Photo" class="w-full rounded-lg"/></div></div></div>`; document.body.appendChild(m); }} className="flex-shrink-0 border-2 border-red-500 rounded-lg overflow-hidden hover:opacity-80 transition-opacity cursor-pointer" title="Klik untuk memperbesar">
                                                        <img src={att.photo_in_url} alt="Clock In" className="w-16 h-16 object-cover" />
                                                    </button>
                                                ) : (
                                                    <div className="flex items-center justify-center w-16 h-16 border-2 border-slate-200 rounded-lg bg-slate-50 flex-shrink-0">
                                                        <svg className="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3.5">
                                            <div className="flex items-center gap-2">
                                                <span className="text-slate-600 font-mono whitespace-nowrap">{att.clock_out || '-'}</span>
                                                {att.photo_out_url ? (
                                                    <button onClick={() => { const m=document.createElement('div'); m.innerHTML=`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="this.parentElement.remove()"><div class="bg-white rounded-2xl max-w-2xl w-full overflow-hidden shadow-2xl" onclick="event.stopPropagation()"><div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-bold text-slate-800">Foto Clock Out</h3><button onclick="this.closest('.fixed').parentElement.remove()" class="text-slate-400 hover:text-slate-600">✕</button></div><div class="p-6"><img src="${att.photo_out_url}" alt="Clock Out Photo" class="w-full rounded-lg"/></div></div></div>`; document.body.appendChild(m); }} className="flex-shrink-0 border-2 border-blue-500 rounded-lg overflow-hidden hover:opacity-80 transition-opacity cursor-pointer" title="Klik untuk memperbesar">
                                                        <img src={att.photo_out_url} alt="Clock Out" className="w-16 h-16 object-cover" />
                                                    </button>
                                                ) : (
                                                    <div className="flex items-center justify-center w-16 h-16 border-2 border-slate-200 rounded-lg bg-slate-50 flex-shrink-0">
                                                        <svg className="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3.5">
                                            <div>
                                                <span className={`inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[att.status]}`}>
                                                    {statusLabels[att.status]}
                                                </span>
                                                {att.status === 'late' && (
                                                    <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded-lg">
                                                        {att.late_duration ? (
                                                            <p className="text-xs font-semibold text-red-700 whitespace-nowrap">{att.late_duration}</p>
                                                        ) : (
                                                            <p className="text-xs text-red-600">Jadwal tidak ditemukan untuk hari ini</p>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>)}

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
