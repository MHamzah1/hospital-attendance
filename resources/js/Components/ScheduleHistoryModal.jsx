import React, { useState, useEffect } from 'react';
import { X } from 'lucide-react';

export default function ScheduleHistoryModal({ open, onClose, userId, month, year }) {
    const [histories, setHistories] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (open && userId) {
            loadHistory();
        }
    }, [open, userId, month, year]);

    const loadHistory = async () => {
        setLoading(true);
        try {
            const response = await fetch(
                `/schedules/${userId}/history?month=${month}&year=${year}`
            );
            const data = await response.json();
            setHistories(data.histories);
        } catch (error) {
            console.error('Error loading history:', error);
        } finally {
            setLoading(false);
        }
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-auto">
                {/* Header */}
                <div className="sticky top-0 bg-gray-100 p-4 flex justify-between items-center border-b">
                    <h2 className="text-xl font-bold">Riwayat Perubahan Jadwal</h2>
                    <button
                        onClick={onClose}
                        className="p-1 hover:bg-gray-200 rounded"
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Content */}
                <div className="p-4">
                    {loading ? (
                        <div className="flex justify-center py-8">
                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                        </div>
                    ) : histories.length === 0 ? (
                        <p className="text-gray-600 text-center py-8">Tidak ada riwayat perubahan</p>
                    ) : (
                        <div className="space-y-4">
                            {histories.map(history => (
                                <div
                                    key={history.id}
                                    className="border rounded-lg p-4 hover:bg-gray-50 transition"
                                >
                                    <div className="flex justify-between items-start gap-4">
                                        <div className="flex-1">
                                            <p className="font-semibold text-gray-900">
                                                Tanggal: <span className="text-blue-600">{history.date}</span>
                                            </p>
                                            <div className="mt-2 grid grid-cols-2 gap-4">
                                                <div>
                                                    <p className="text-sm text-gray-600">Shift Sebelumnya</p>
                                                    <p className="font-semibold text-red-600">
                                                        {history.shift_old ? `${history.shift_old.name} (${history.shift_old.time_range})` : 'Tidak ada'}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p className="text-sm text-gray-600">Shift Baru</p>
                                                    <p className="font-semibold text-green-600">
                                                        {history.shift_new ? `${history.shift_new.name} (${history.shift_new.time_range})` : 'Tidak ada'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="mt-3 pt-3 border-t text-sm text-gray-600 text-right">
                                        <p>Diubah oleh: <span className="font-semibold text-gray-900">{history.changed_by}</span></p>
                                        <p>Waktu: <span className="font-semibold text-gray-900">{history.changed_at}</span></p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
