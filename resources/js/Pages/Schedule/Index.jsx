import React, { useState, useRef, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import ScheduleHistoryModal from '@/Components/ScheduleHistoryModal';
import { Calendar, ChevronLeft, ChevronRight, History, Download, Upload, FileSpreadsheet } from 'lucide-react';

export default function ScheduleIndex() {
    const { auth } = usePage().props;
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [employees, setEmployees] = useState([]);
    const [schedules, setSchedules] = useState({});
    const [shifts, setShifts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [daysInMonth, setDaysInMonth] = useState(0);
    const [searchTerm, setSearchTerm] = useState('');
    const [activeCell, setActiveCell] = useState(null);
    const [successMessage, setSuccessMessage] = useState('');
    const [errorMessage, setErrorMessage] = useState('');
    const [historyOpen, setHistoryOpen] = useState(false);
    const [selectedEmployeeHistory, setSelectedEmployeeHistory] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const importFileRef = useRef(null);
    const pasteAreaRef = useRef(null);
    const searchTimerRef = useRef(null);
    const msgTimerRef = useRef(null);

    const parseJsonOrThrow = async (response, fallbackMessage) => {
        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');

        if (isJson) {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data?.message || fallbackMessage);
            }
            return data;
        }

        const text = await response.text();
        const statusHint = response.status === 419
            ? 'Sesi/CSRF kadaluarsa. Refresh halaman lalu login ulang jika perlu.'
            : `Server mengembalikan format tidak valid (HTTP ${response.status}).`;

        console.error('Unexpected non-JSON response:', text.slice(0, 500));
        throw new Error(`${fallbackMessage}. ${statusHint}`);
    };

    // Load schedules on mount or when month/year changes
    React.useEffect(() => {
        loadSchedules();
    }, [selectedMonth, selectedYear]);

    const loadSchedules = async () => {
        setLoading(true);
        try {
            const response = await fetch(
                `/schedules/${selectedMonth}/${selectedYear}?search=${encodeURIComponent(searchTerm)}`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );
            const data = await parseJsonOrThrow(response, 'Gagal memuat data jadwal');
            setEmployees(data.employees);
            setShifts(data.shifts);
            setDaysInMonth(data.daysInMonth);

            // Build schedules map: { employeeId: { '2026-03-01': { shift_id, shift, modified }, ... } }
            const schedulesMap = {};
            data.employees.forEach(emp => {
                schedulesMap[emp.id] = {};
                emp.schedules.forEach(sched => {
                    schedulesMap[emp.id][sched.date] = {
                        shift_id: sched.shift?.id || null,
                        shift: sched.shift,
                        modified: false,
                    };
                });
            });
            setSchedules(schedulesMap);
        } catch (error) {
            console.error('Error loading schedules:', error);
            setErrorMessage('Gagal memuat jadwal. Silahkan refresh halaman.');
        } finally {
            setLoading(false);
        }
    };

    const handleShiftChange = (employeeId, date, shiftId) => {
        const newShift = shiftId ? shifts.find(s => s.id === parseInt(shiftId)) : null;
        setSchedules(prev => ({
            ...prev,
            [employeeId]: {
                ...prev[employeeId],
                [date]: {
                    shift_id: newShift?.id || null,
                    shift: newShift,
                    modified: true,
                }
            }
        }));
    };

    const handlePaste = (e) => {
        const text = e.target.value;
        if (!text.trim()) return;

        // Parse pasted data: tab-separated shifts, newline-separated rows
        const lines = text.trim().split('\n');
        const data = lines.map(line =>
            line.split('\t').map(cell => cell.trim())
        );

        // Map employee rows to our employees
        const sortedEmployees = [...employees].sort((a, b) => a.id - b.id);
        let dayIndex = 0;
        let employeeIndex = 0;

        data.forEach(row => {
            if (employeeIndex >= sortedEmployees.length) return;

            const employee = sortedEmployees[employeeIndex];
            dayIndex = 0;

            row.forEach(cell => {
                if (dayIndex > daysInMonth) {
                    employeeIndex++;
                    if (employeeIndex >= sortedEmployees.length) return;
                    dayIndex = 0;
                }

                if (dayIndex < daysInMonth) {
                    const dateStr = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(dayIndex + 1).padStart(2, '0')}`;

                    if (!cell || cell === '-' || cell === '') {
                        // Clear shift for empty/"-" cells
                        setSchedules(prev => ({
                            ...prev,
                            [employee.id]: {
                                ...prev[employee.id],
                                [dateStr]: {
                                    shift_id: null,
                                    shift: null,
                                    modified: true,
                                }
                            }
                        }));
                    } else {
                        // Try to match shift name or code
                        const shift = shifts.find(s => {
                            const cellLower = cell.toLowerCase();

                            // Extract shift type (e.g., "Pagi" from "Shift Pagi")
                            const parts = s.name.split(' ');
                            const shiftType = parts.length > 1 ? parts[1] : null;

                            // For multi-word shifts (e.g., "Shift Pagi")
                            if (shiftType) {
                                const shiftTypeLower = shiftType.toLowerCase();

                                // Match full shift type name (case-insensitive)
                                if (shiftTypeLower === cellLower) return true;

                                // Special handling for multi-character codes
                                if (shiftTypeLower === 'middle' && cellLower === 'md') return true;

                                // Match first letter of shift type
                                if (shiftType.charAt(0).toUpperCase() === cell.toUpperCase()) {
                                    // Avoid "M" matching both Malam and Middle
                                    if (shiftTypeLower === 'middle') return false;
                                    return true;
                                }
                            } else {
                                // For single-word shifts (e.g., "Libur")
                                if (s.name.toLowerCase() === cellLower) return true;
                                if (s.name.charAt(0).toUpperCase() === cell.toUpperCase()) return true;
                            }

                            return false;
                        });

                        if (shift) {
                            setSchedules(prev => ({
                                ...prev,
                                [employee.id]: {
                                    ...prev[employee.id],
                                    [dateStr]: {
                                        shift_id: shift.id,
                                        shift,
                                        modified: true,
                                    }
                                }
                            }));
                        }
                    }
                }
                dayIndex++;
            });

            employeeIndex++;
        });

        e.target.value = '';
        setSuccessMessage('Data paste berhasil dimuat!');
        if (msgTimerRef.current) clearTimeout(msgTimerRef.current);
        msgTimerRef.current = setTimeout(() => setSuccessMessage(''), 3000);
    };

    const handleSaveAll = async () => {
        // Collect all modified schedules
        const allSchedules = [];

        Object.entries(schedules).forEach(([empId, empSchedules]) => {
            Object.entries(empSchedules).forEach(([date, schedule]) => {
                if (schedule.modified) {
                    allSchedules.push({
                        user_id: parseInt(empId),
                        date,
                        shift_id: schedule.shift_id,
                    });
                }
            });
        });

        if (allSchedules.length === 0) {
            setErrorMessage('Tidak ada perubahan untuk disimpan.');
            if (msgTimerRef.current) clearTimeout(msgTimerRef.current);
            msgTimerRef.current = setTimeout(() => setErrorMessage(''), 3000);
            return;
        }

        // DEBUG: Log data sebelum send
        console.log('DEBUG: Total schedules to save:', allSchedules.length);
        console.log('DEBUG: First 5 schedules:', allSchedules.slice(0, 5));
        console.log('DEBUG: All schedules:', JSON.stringify(allSchedules, null, 2));

        setSaving(true);
        try {
            // Use larger batches to reduce request overhead on massive pasted updates.
            const batchSize = 200;
            for (let i = 0; i < allSchedules.length; i += batchSize) {
                const batch = allSchedules.slice(i, i + batchSize);

                console.log(`DEBUG: Sending batch ${Math.floor(i/batchSize) + 1}, size: ${batch.length}`);

                const response = await fetch('/schedules/bulk', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        month: selectedMonth,
                        year: selectedYear,
                        schedules: batch,
                    }),
                });

                await parseJsonOrThrow(response, 'Gagal menyimpan jadwal');
            }

            // Mark all as not modified
            setSchedules(prev => {
                const updated = {};
                Object.entries(prev).forEach(([empId, empSchedules]) => {
                    updated[empId] = {};
                    Object.entries(empSchedules).forEach(([date, schedule]) => {
                        updated[empId][date] = { ...schedule, modified: false };
                    });
                });
                return updated;
            });

            setErrorMessage('');
            setSuccessMessage(`Berhasil menyimpan ${allSchedules.length} jadwal!`);
            if (msgTimerRef.current) clearTimeout(msgTimerRef.current);
            msgTimerRef.current = setTimeout(() => setSuccessMessage(''), 3000);
        } catch (error) {
            console.error('Error saving schedules:', error);
            setErrorMessage(error.message || 'Gagal menyimpan jadwal. Silahkan coba lagi.');
        } finally {
            setSaving(false);
        }
    };

    const handleImport = async () => {
        if (!importFile) return;
        setImporting(true);
        setImportResult(null);
        try {
            const formData = new FormData();
            formData.append('file', importFile);
            formData.append('month', selectedMonth);
            formData.append('year', selectedYear);

            const response = await fetch('/schedules/import', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
                body: formData,
            });
            const data = await response.json();
            setImportResult(data);
            if (data.success) {
                await loadSchedules();
            }
        } catch (error) {
            setImportResult({ success: false, message: error.message });
        } finally {
            setImporting(false);
        }
    };

    const GetDateStr = (day) => {
        return `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    };

    const monthName = new Date(selectedYear, selectedMonth - 1).toLocaleString('id-ID', { month: 'long', year: 'numeric' });
    const modifiedCount = Object.values(schedules).reduce(
        (count, emp) => count + Object.values(emp).filter(s => s.modified).length,
        0
    );

    const shiftCodeMap = {
        'Shift Pagi': 'P',
        'Shift Siang': 'S',
        'Shift Malam': 'M',
    };

    return (
        <AuthenticatedLayout>
            <Head title="Manajemen Jadwal Kerja" />

            <div className="py-12">
                <div className="max-w-full mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex items-center justify-between mb-6">
                                <h1 className="text-3xl font-bold flex items-center gap-2">
                                    <Calendar className="w-8 h-8" />
                                    Manajemen Jadwal Kerja Bulanan
                                </h1>
                                <div className="flex items-center gap-3">
                                    {modifiedCount > 0 && (
                                        <span className="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                                            {modifiedCount} perubahan
                                        </span>
                                    )}
                                    <a
                                        href={route('schedule.import.download')}
                                        className="flex items-center gap-2 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium"
                                    >
                                        <Download className="w-4 h-4" />
                                        Download Template
                                    </a>
                                    <button
                                        onClick={() => { setImportOpen(true); setImportResult(null); setImportFile(null); if (importFileRef.current) importFileRef.current.value = ''; }}
                                        className="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
                                    >
                                        <Upload className="w-4 h-4" />
                                        Import File
                                    </button>
                                </div>
                            </div>

                            {/* Success Message */}
                            {successMessage && (
                                <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                                    ✓ {successMessage}
                                </div>
                            )}

                            {/* Error Message */}
                            {errorMessage && (
                                <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                                    ✕ {errorMessage}
                                </div>
                            )}

                            {/* Month Navigation */}
                            <div className="mb-6 flex items-center gap-4">
                                <button
                                    onClick={() => {
                                        const newDate = new Date(selectedYear, selectedMonth - 2);
                                        setSelectedMonth(newDate.getMonth() + 1);
                                        setSelectedYear(newDate.getFullYear());
                                    }}
                                    className="p-2 hover:bg-gray-200 rounded"
                                >
                                    <ChevronLeft className="w-5 h-5" />
                                </button>
                                <span className="text-lg font-semibold min-w-[200px]">{monthName}</span>
                                <button
                                    onClick={() => {
                                        const newDate = new Date(selectedYear, selectedMonth);
                                        setSelectedMonth(newDate.getMonth() + 1);
                                        setSelectedYear(newDate.getFullYear());
                                    }}
                                    className="p-2 hover:bg-gray-200 rounded"
                                >
                                    <ChevronRight className="w-5 h-5" />
                                </button>
                            </div>

                            {/* Search */}
                            <div className="mb-6">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Cari Karyawan
                                </label>
                                <TextInput
                                    placeholder="Ketik nama atau ID karyawan..."
                                    value={searchTerm}
                                    onChange={(e) => {
                                        setSearchTerm(e.target.value);
                                        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
                                        searchTimerRef.current = setTimeout(() => loadSchedules(), 500);
                                    }}
                                />
                            </div>

                            {/* Bulk Paste Area */}
                            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <label className="block text-sm font-medium text-blue-900 mb-2">
                                    📋 Paste data dari Excel/Spreadsheet
                                </label>
                                <textarea
                                    ref={pasteAreaRef}
                                    placeholder="Paste data tab-separated dari Excel di sini. Format: Shift names atau codes (P/S/M) dipisah tab per column, setiap row untuk employee)"
                                    onChange={handlePaste}
                                    className="w-full h-20 p-3 border border-gray-300 rounded-lg font-mono text-sm"
                                />
                                <p className="text-xs text-blue-700 mt-2">
                                    💡 Contoh: Paste dari Excel → [Pagi Tab Siang Tab Malam] → Enter → [Siang Tab Malam Tab Pagi]
                                </p>
                            </div>

                            {/* Spreadsheet Grid */}
                            {loading ? (
                                <div className="flex justify-center py-12">
                                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto mb-6 border border-gray-300 rounded-lg">
                                        <table className="w-full border-collapse">
                                            <thead>
                                                <tr className="bg-gray-200">
                                                    <th className="border border-gray-300 p-2 text-left font-semibold min-w-[150px]">
                                                        Nama
                                                    </th>
                                                    <th className="border border-gray-300 p-2 text-left font-semibold min-w-[120px]">
                                                        NIP
                                                    </th>
                                                    {Array.from({ length: daysInMonth }, (_, i) => {
                                                        const date = new Date(selectedYear, selectedMonth - 1, i + 1);
                                                        const dayName = date.toLocaleString('id-ID', { weekday: 'short' }).substring(0, 2);
                                                        return (
                                                            <th
                                                                key={i}
                                                                className={`border border-gray-300 p-1 text-center font-semibold text-xs min-w-[60px] ${
                                                                    date.getDay() === 0 || date.getDay() === 6
                                                                        ? 'bg-orange-100'
                                                                        : 'bg-gray-50'
                                                                }`}
                                                            >
                                                                <div>{i + 1}</div>
                                                                <div className="text-gray-600">{dayName}</div>
                                                            </th>
                                                        );
                                                    })}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {employees.map((emp) => (
                                                    <tr key={emp.id} className="hover:bg-blue-50 transition">
                                                        <td className="border border-gray-300 p-2 font-semibold text-sm">
                                                            {emp.name}
                                                        </td>
                                                        <td className="border border-gray-300 p-2 text-xs text-gray-600">
                                                            {emp.employee_id || '-'}
                                                        </td>
                                                        {Array.from({ length: daysInMonth }, (_, i) => {
                                                            const dateStr = GetDateStr(i + 1);
                                                            const schedule = schedules[emp.id]?.[dateStr];
                                                            const date = new Date(selectedYear, selectedMonth - 1, i + 1);
                                                            const isWeekend = date.getDay() === 0 || date.getDay() === 6;

                                                            return (
                                                                <td
                                                                    key={`${emp.id}-${dateStr}`}
                                                                    className={`border border-gray-300 p-1 ${
                                                                        isWeekend ? 'bg-orange-50' : ''
                                                                    } ${schedule?.modified ? 'bg-yellow-100' : ''}`}
                                                                    onClick={() => setActiveCell({ empId: emp.id, date: dateStr })}
                                                                >
                                                                    <select
                                                                        value={schedule?.shift_id || ''}
                                                                        onChange={(e) =>
                                                                            handleShiftChange(emp.id, dateStr, e.target.value)
                                                                        }
                                                                        className={`w-full p-1 text-xs border rounded ${
                                                                            schedule?.modified
                                                                                ? 'border-yellow-400 bg-yellow-50'
                                                                                : 'border-gray-300'
                                                                        } ${
                                                                            activeCell?.empId === emp.id &&
                                                                            activeCell?.date === dateStr
                                                                                ? 'ring-2 ring-blue-500'
                                                                                : ''
                                                                        }`}
                                                                    >
                                                                        <option value="">-</option>
                                                                        {shifts.map((shift) => (
                                                                            <option key={shift.id} value={shift.id}>
                                                                                {shiftCodeMap[shift.name] || shift.name}
                                                                            </option>
                                                                        ))}
                                                                    </select>
                                                                </td>
                                                            );
                                                        })}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Save Button */}
                                    <div className="flex gap-3 justify-end">
                                        <button
                                            onClick={() => window.location.reload()}
                                            className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                                            disabled={saving}
                                        >
                                            Reset
                                        </button>
                                        <PrimaryButton
                                            onClick={handleSaveAll}
                                            disabled={saving || modifiedCount === 0}
                                        >
                                            {saving ? 'Menyimpan...' : `Simpan Jadwal (${modifiedCount})`}
                                        </PrimaryButton>
                                    </div>
                                </>
                            )}

                            {/* Legend */}
                            <div className="mt-6 p-4 bg-gray-50 rounded-lg text-sm text-gray-700">
                                <p className="font-semibold mb-2">📚 Keterangan:</p>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>🟨 <span className="font-semibold">Kuning:</span> Data diubah, belum disimpan</div>
                                    <div>🟠 <span className="font-semibold">Orange:</span> Weekend</div>
                                    <div><span className="font-semibold">P:</span> Shift Pagi (07:00-14:00)</div>
                                    <div><span className="font-semibold">S:</span> Shift Siang (14:00-21:00)</div>
                                    <div><span className="font-semibold">M:</span> Shift Malam (21:00-07:00)</div>
                                    <div><span className="font-semibold">-:</span> Tidak dijadwalkan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* History Modal */}
            <ScheduleHistoryModal
                open={historyOpen}
                onClose={() => setHistoryOpen(false)}
                userId={selectedEmployeeHistory?.id}
                month={selectedMonth}
                year={selectedYear}
            />

            {/* Import Modal */}
            {importOpen && (
                <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-lg">
                        <div className="flex items-center justify-between p-6 border-b">
                            <h2 className="text-lg font-bold flex items-center gap-2">
                                <FileSpreadsheet className="w-5 h-5 text-blue-600" />
                                Import Jadwal dari File
                            </h2>
                            <button
                                onClick={() => setImportOpen(false)}
                                className="text-gray-400 hover:text-gray-600 text-2xl leading-none w-8 h-8 flex items-center justify-center"
                            >
                                &times;
                            </button>
                        </div>

                        <div className="p-6 space-y-4">
                            <div className="p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
                                <p className="font-semibold">Periode: {monthName}</p>
                                <p className="mt-1 text-xs text-blue-600">
                                    Gunakan template <strong>(OK JADWAL)</strong> yang sudah diisi kode shift (P=Pagi, S=Siang, M=Malam, L=Libur, dll).
                                    Data yang sudah ada akan diupdate, yang belum ada akan dibuat baru.
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2">
                                    Pilih File (OK JADWAL)
                                </label>
                                <input
                                    ref={importFileRef}
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={(e) => setImportFile(e.target.files?.[0] || null)}
                                    className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium hover:file:bg-blue-100"
                                />
                                {importFile && (
                                    <p className="mt-1 text-xs text-green-600 font-medium">{importFile.name}</p>
                                )}
                            </div>

                            {importResult && (
                                <div className={`p-4 rounded-lg text-sm ${importResult.success ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'}`}>
                                    <p className="font-semibold">{importResult.message}</p>
                                    {importResult.success && (
                                        <p className="mt-1 text-xs">
                                            Baru: {importResult.created} | Diupdate: {importResult.updated}
                                            {importResult.skipped > 0 && ` | Dilewati: ${importResult.skipped}`}
                                        </p>
                                    )}
                                    {importResult.errors?.length > 0 && (
                                        <ul className="mt-2 list-disc ml-4 text-xs space-y-1">
                                            {importResult.errors.slice(0, 10).map((err, i) => (
                                                <li key={i}>{err}</li>
                                            ))}
                                            {importResult.errors.length > 10 && (
                                                <li>... dan {importResult.errors.length - 10} baris lainnya</li>
                                            )}
                                        </ul>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="flex gap-3 justify-end p-6 pt-0">
                            <button
                                onClick={() => setImportOpen(false)}
                                className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium"
                            >
                                {importResult?.success ? 'Tutup' : 'Batal'}
                            </button>
                            {!importResult?.success && (
                                <button
                                    onClick={handleImport}
                                    disabled={!importFile || importing}
                                    className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm font-medium"
                                >
                                    <Upload className="w-4 h-4" />
                                    {importing ? 'Mengimport...' : 'Import Sekarang'}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
