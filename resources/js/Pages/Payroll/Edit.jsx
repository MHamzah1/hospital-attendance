import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import FlashMessage from '@/Components/FlashMessage';

export default function PayrollEdit({ payroll }) {
    const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);

    const { data, setData, put, processing, errors } = useForm({
        // Gaji Pokok
        base_salary: payroll.base_salary || 0,
        // Tunjangan
        position_allowance: payroll.position_allowance || 0,
        functional_allowance: payroll.functional_allowance || 0,
        special_allowance: payroll.special_allowance || 0,
        meal_allowance: payroll.meal_allowance || 0,
        transport_allowance: payroll.transport_allowance || 0,
        attendance_allowance: payroll.attendance_allowance || 0,
        // Setelah BRUTO
        salary_correction: payroll.salary_correction || 0,
        other_allowance: payroll.other_allowance || 0,
        // Lembur per kategori
        overtime_hourly: payroll.overtime_hourly || 0,
        overtime_night: payroll.overtime_night || 0,
        overtime_shift: payroll.overtime_shift || 0,
        overtime_on_call: payroll.overtime_on_call || 0,
        overtime_mod: payroll.overtime_mod || 0,
        overtime_holiday: payroll.overtime_holiday || 0,
        // Potongan
        cdt_deduction: payroll.cdt_deduction || 0,
        alpha_deduction: payroll.alpha_deduction || 0,
        cashbond_deduction: payroll.cashbond_deduction || 0,
        piutang_obat_deduction: payroll.piutang_obat_deduction || 0,
        bpjs_kesehatan: payroll.bpjs_kesehatan || 0,
        bpjs_ketenagakerjaan: payroll.bpjs_ketenagakerjaan || 0,
        bpjs_pensiun: payroll.bpjs_pensiun || 0,
        bpjs_pensiun_jp: payroll.bpjs_pensiun_jp || 0,
        pph21: payroll.pph21 || 0,
        salary_correction_deduction: payroll.salary_correction_deduction || 0,
        bank_admin_deduction: payroll.bank_admin_deduction || 0,
        other_deduction: payroll.other_deduction || 0,
        notes: payroll.notes || '',
    });

    // === KALKULASI SESUAI STRUKTUR EXCEL ===
    // Total Tunjangan (untuk BRUTO)
    const totalAllowance =
        parseFloat(data.position_allowance || 0) +
        parseFloat(data.functional_allowance || 0) +
        parseFloat(data.special_allowance || 0) +
        parseFloat(data.meal_allowance || 0) +
        parseFloat(data.transport_allowance || 0) +
        parseFloat(data.attendance_allowance || 0);

    // BRUTO = Gaji Pokok + Total Tunjangan
    const grossSalary = parseFloat(data.base_salary || 0) + totalAllowance;

    // Total Lembur (semua kategori)
    const totalOvertime =
        parseFloat(data.overtime_hourly || 0) +
        parseFloat(data.overtime_night || 0) +
        parseFloat(data.overtime_shift || 0) +
        parseFloat(data.overtime_on_call || 0) +
        parseFloat(data.overtime_mod || 0) +
        parseFloat(data.overtime_holiday || 0);

    // TOTAL PENDAPATAN = BRUTO + Lembur + Koreksi Upah + Lain-lain
    const totalPendapatan = grossSalary + totalOvertime +
        parseFloat(data.salary_correction || 0) +
        parseFloat(data.other_allowance || 0);

    // Total Potongan
    const totalDeduction =
        parseFloat(data.bpjs_kesehatan || 0) +
        parseFloat(data.bpjs_ketenagakerjaan || 0) +
        parseFloat(data.bpjs_pensiun || 0) +
        parseFloat(data.bpjs_pensiun_jp || 0) +
        parseFloat(data.pph21 || 0) +
        parseFloat(data.cdt_deduction || 0) +
        parseFloat(data.alpha_deduction || 0) +
        parseFloat(data.cashbond_deduction || 0) +
        parseFloat(data.piutang_obat_deduction || 0) +
        parseFloat(data.salary_correction_deduction || 0) +
        parseFloat(data.bank_admin_deduction || 0) +
        parseFloat(data.other_deduction || 0);

    // GAJI DIBAYARKAN = TOTAL PENDAPATAN - TOTAL POTONGAN
    const netSalary = totalPendapatan - totalDeduction;

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/payroll/${payroll.id}`);
    };

    const InputField = ({ label, name, value, disabled = false }) => (
        <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">{label}</label>
            <input
                type="number"
                value={value}
                onChange={(e) => setData(name, e.target.value)}
                disabled={disabled}
                className={`w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500 ${
                    disabled ? 'bg-slate-50 text-slate-500' : ''
                }`}
            />
            {errors[name] && <p className="text-red-500 text-xs mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AuthenticatedLayout header="Edit Penggajian">
            <Head title="Edit Penggajian" />
            <FlashMessage />

            <div className="max-w-5xl mx-auto">
                {/* Header Info */}
                <div className="bg-white rounded-2xl border border-slate-200/60 p-6 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-xl font-bold text-slate-800">{payroll.user?.name}</h2>
                            <p className="text-slate-500 text-sm">NIP: {payroll.user?.nip || payroll.user?.employee_id}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-lg font-bold text-slate-800">{months[payroll.month]} {payroll.year}</p>
                            <p className="text-sm text-slate-500">{payroll.user?.position}</p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        {/* Gaji Pokok & Tunjangan (untuk BRUTO) */}
                        <div className="bg-white rounded-2xl border border-slate-200/60 p-6">
                            <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <span className="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600">+</span>
                                Gaji & Tunjangan
                            </h3>
                            <div className="space-y-4">
                                <InputField label="Gaji Pokok" name="base_salary" value={data.base_salary} />
                                <hr className="border-slate-200" />
                                <InputField label="Tunjangan Jabatan" name="position_allowance" value={data.position_allowance} />
                                <InputField label="Tunjangan Fungsional" name="functional_allowance" value={data.functional_allowance} />
                                <InputField label="Tunjangan Khusus" name="special_allowance" value={data.special_allowance} />
                                <InputField label="Tunjangan Makan" name="meal_allowance" value={data.meal_allowance} />
                                <InputField label="Tunjangan Transport" name="transport_allowance" value={data.transport_allowance} />
                                <InputField label="Tunjangan Kehadiran" name="attendance_allowance" value={data.attendance_allowance} />
                            </div>
                            <div className="mt-4 pt-4 border-t border-slate-100">
                                <div className="flex justify-between mb-2">
                                    <span className="text-sm text-slate-600">Total Tunjangan:</span>
                                    <span className="font-medium text-slate-700">Rp {fmt(totalAllowance)}</span>
                                </div>
                                <div className="flex justify-between bg-slate-50 p-2 rounded-lg">
                                    <span className="font-bold text-slate-800">BRUTO:</span>
                                    <span className="font-bold text-emerald-600">Rp {fmt(grossSalary)}</span>
                                </div>
                            </div>
                        </div>

                        {/* Lembur Per Kategori */}
                        <div className="bg-white rounded-2xl border border-slate-200/60 p-6">
                            <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <span className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">+</span>
                                Lembur Per Kategori
                            </h3>
                            <p className="text-xs text-slate-500 mb-4">Masukkan nominal total lembur per kategori</p>
                            <div className="space-y-4">
                                <InputField label="Lembur Jam (@Rp 10.000/jam)" name="overtime_hourly" value={data.overtime_hourly} />
                                <InputField label="Lembur Malam (@Rp 20.000/shift)" name="overtime_night" value={data.overtime_night} />
                                <InputField label="Lembur Shift (@Rp 80.000/shift)" name="overtime_shift" value={data.overtime_shift} />
                                <InputField label="Lembur On Call (@Rp 50.000/shift)" name="overtime_on_call" value={data.overtime_on_call} />
                                <InputField label="Lembur Hari Raya (@Rp 120.000/shift)" name="overtime_holiday" value={data.overtime_holiday} />
                            </div>
                            <div className="mt-4 pt-4 border-t border-slate-100 flex justify-between">
                                <span className="font-medium text-slate-700">Total Lembur:</span>
                                <span className="font-bold text-blue-600">Rp {fmt(totalOvertime)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Tambahan Pendapatan (Setelah BRUTO) */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 mb-6">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">+</span>
                            Tambahan Pendapatan
                        </h3>
                        <div className="grid grid-cols-2 gap-4">
                            <InputField label="Koreksi Upah (+)" name="salary_correction" value={data.salary_correction} />
                            <InputField label="Lain-lain (+)" name="other_allowance" value={data.other_allowance} />
                        </div>
                        <div className="mt-4 pt-4 border-t border-slate-100 bg-emerald-50 p-3 rounded-lg">
                            <div className="flex justify-between items-center">
                                <span className="font-bold text-emerald-800">TOTAL PENDAPATAN:</span>
                                <span className="font-bold text-xl text-emerald-600">Rp {fmt(totalPendapatan)}</span>
                            </div>
                            <p className="text-xs text-emerald-600 mt-1">BRUTO + Lembur + Koreksi Upah + Lain-lain</p>
                        </div>
                    </div>

                    {/* Potongan */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 mb-6">
                        <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span className="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center text-red-600">-</span>
                            Potongan
                        </h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <InputField label="BPJS Kesehatan" name="bpjs_kesehatan" value={data.bpjs_kesehatan} />
                            <InputField label="BPJS TK JHT" name="bpjs_ketenagakerjaan" value={data.bpjs_ketenagakerjaan} />
                            <InputField label="BPJS TK Pensiun" name="bpjs_pensiun" value={data.bpjs_pensiun} />
                            <InputField label="BPJS TK JP" name="bpjs_pensiun_jp" value={data.bpjs_pensiun_jp} />
                            <InputField label="PPh21" name="pph21" value={data.pph21} />
                            <InputField label="CDT" name="cdt_deduction" value={data.cdt_deduction} />
                            <InputField label="Alpha" name="alpha_deduction" value={data.alpha_deduction} />
                            <InputField label="Cashbond" name="cashbond_deduction" value={data.cashbond_deduction} />
                            <InputField label="Piutang Obat" name="piutang_obat_deduction" value={data.piutang_obat_deduction} />
                            <InputField label="Koreksi Upah (-)" name="salary_correction_deduction" value={data.salary_correction_deduction} />
                            <InputField label="Adm Bank" name="bank_admin_deduction" value={data.bank_admin_deduction} />
                            <InputField label="Potongan Lain" name="other_deduction" value={data.other_deduction} />
                        </div>
                        <div className="mt-4 pt-4 border-t border-slate-100 flex justify-between">
                            <span className="font-medium text-slate-700">Total Potongan:</span>
                            <span className="font-bold text-red-600">Rp {fmt(totalDeduction)}</span>
                        </div>
                    </div>

                    {/* Summary & Notes */}
                    <div className="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-6 mb-6 text-white">
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                            <div>
                                <p className="text-emerald-100 text-sm">Gaji Pokok</p>
                                <p className="text-lg font-bold">Rp {fmt(data.base_salary)}</p>
                            </div>
                            <div>
                                <p className="text-emerald-100 text-sm">+ Tunjangan</p>
                                <p className="text-lg font-bold">Rp {fmt(totalAllowance)}</p>
                            </div>
                            <div className="bg-white/10 rounded-lg p-2">
                                <p className="text-emerald-100 text-sm">= BRUTO</p>
                                <p className="text-lg font-bold">Rp {fmt(grossSalary)}</p>
                            </div>
                            <div>
                                <p className="text-emerald-100 text-sm">+ Lembur & Lainnya</p>
                                <p className="text-lg font-bold">Rp {fmt(totalOvertime + parseFloat(data.salary_correction || 0) + parseFloat(data.other_allowance || 0))}</p>
                            </div>
                            <div>
                                <p className="text-emerald-100 text-sm">- Potongan</p>
                                <p className="text-lg font-bold">Rp {fmt(totalDeduction)}</p>
                            </div>
                        </div>
                        <div className="pt-4 border-t border-emerald-400/30">
                            <div className="flex justify-between items-center mb-2">
                                <span className="text-sm text-emerald-100">Total Pendapatan:</span>
                                <span className="text-xl font-bold">Rp {fmt(totalPendapatan)}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-lg font-medium">Gaji Dibayarkan:</span>
                                <span className="text-3xl font-bold">Rp {fmt(netSalary)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Notes */}
                    <div className="bg-white rounded-2xl border border-slate-200/60 p-6 mb-6">
                        <label className="block text-sm font-medium text-slate-700 mb-2">Catatan (Opsional)</label>
                        <textarea
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            className="w-full rounded-lg border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                            rows={3}
                            placeholder="Tambahkan catatan untuk slip gaji ini..."
                        />
                    </div>

                    {/* Actions */}
                    <div className="flex gap-4">
                        <Link
                            href={`/payroll/${payroll.id}`}
                            className="flex-1 py-3 px-6 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 py-3 px-6 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold transition-colors shadow-lg shadow-emerald-500/30 disabled:opacity-50"
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
