import React from 'react';
import { Document, Page, Text, View, Image, StyleSheet } from '@react-pdf/renderer';

// ─── Constants ───
const PRIMARY = '#0f3460';
const SUB_BG = '#e8eef7';
const BORDER = '#ccc';
const BORDER_DARK = '#0f3460';
const WHITE = '#ffffff';
const TEXT_DARK = '#1a1a2e';
const TEXT_GRAY = '#333';
const TEXT_LIGHT = '#555';
const TEXT_MUTED = '#999';
const RED = '#e74c3c';
const GREEN = '#059669';
const ATT_LABEL_BG = '#f0f4f8';

const months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// ─── Styles (compact for single page) ───
const s = StyleSheet.create({
    page: {
        paddingTop: 12,
        paddingBottom: 12,
        paddingHorizontal: 24,
        fontFamily: 'Helvetica',
        fontSize: 7,
        color: TEXT_DARK,
    },

    // ── Header ──
    header: {
        borderBottom: `2px solid ${PRIMARY}`,
        paddingBottom: 5,
        marginBottom: 4,
        alignItems: 'center',
    },
    headerInner: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
    },
    headerLogo: {
        width: 44,
        height: 44,
        marginRight: 10,
    },
    headerTextBlock: {},
    hospitalName: {
        fontSize: 14,
        fontFamily: 'Helvetica-Bold',
        color: PRIMARY,
        letterSpacing: 0.5,
        marginBottom: 1,
    },
    hospitalAddr: {
        fontSize: 7,
        color: TEXT_GRAY,
    },

    // ── Info Table ──
    infoTable: {
        border: `1px solid ${BORDER_DARK}`,
        marginBottom: 4,
    },
    infoRow: {
        flexDirection: 'row',
        borderBottom: `0.5px solid ${BORDER}`,
    },
    infoRowLast: {
        flexDirection: 'row',
    },
    infoLabel: {
        width: 75,
        paddingVertical: 1.5,
        paddingHorizontal: 4,
        fontFamily: 'Helvetica-Bold',
        fontSize: 6.5,
        color: PRIMARY,
        textTransform: 'uppercase',
        backgroundColor: '#fafbfd',
    },
    infoSep: {
        width: 8,
        paddingVertical: 1.5,
        textAlign: 'center',
        color: TEXT_GRAY,
        fontSize: 6.5,
    },
    infoVal: {
        flex: 1,
        paddingVertical: 1.5,
        paddingHorizontal: 3,
        fontSize: 7,
        color: TEXT_DARK,
        borderRight: `0.5px solid ${BORDER}`,
    },
    infoValLast: {
        flex: 1,
        paddingVertical: 1.5,
        paddingHorizontal: 3,
        fontSize: 7,
        color: TEXT_DARK,
    },

    // ── Attendance ──
    attBox: {
        border: `1px solid ${BORDER_DARK}`,
        marginBottom: 4,
    },
    attRow: {
        flexDirection: 'row',
        borderBottom: `0.5px solid ${BORDER}`,
    },
    attRowLast: {
        flexDirection: 'row',
    },
    attLabel: {
        flex: 1,
        paddingVertical: 1.5,
        paddingHorizontal: 4,
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        color: TEXT_DARK,
        backgroundColor: ATT_LABEL_BG,
        borderRight: `0.5px solid ${BORDER}`,
    },
    attVal: {
        flex: 1,
        paddingVertical: 1.5,
        paddingHorizontal: 4,
        fontSize: 7,
        textAlign: 'center',
        borderRight: `0.5px solid ${BORDER}`,
    },
    attValLast: {
        flex: 1,
        paddingVertical: 1.5,
        paddingHorizontal: 4,
        fontSize: 7,
        textAlign: 'center',
    },
    attLabelBlue: {
        flex: 1,
        paddingVertical: 1.5,
        paddingHorizontal: 4,
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        color: WHITE,
        backgroundColor: PRIMARY,
        borderRight: `0.5px solid ${BORDER}`,
    },

    // ── Section / Detail ──
    sectionBox: {
        border: `1px solid ${BORDER_DARK}`,
        marginBottom: 3,
    },
    sectionTitle: {
        backgroundColor: PRIMARY,
        paddingVertical: 2,
        paddingHorizontal: 6,
    },
    sectionTitleText: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 7.5,
        color: WHITE,
    },
    subSection: {
        backgroundColor: SUB_BG,
        paddingVertical: 1.5,
        paddingHorizontal: 6,
        borderBottom: `0.5px solid ${BORDER}`,
    },
    subSectionText: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        color: PRIMARY,
    },
    detailRow: {
        flexDirection: 'row',
        borderBottom: `0.5px solid #eee`,
        paddingVertical: 1.5,
        paddingHorizontal: 6,
    },
    detailLabel: {
        flex: 1,
        fontSize: 7,
        color: TEXT_DARK,
    },
    detailRp: {
        width: 16,
        fontSize: 7,
        textAlign: 'right',
        color: TEXT_DARK,
    },
    detailAmount: {
        width: 72,
        fontSize: 7,
        textAlign: 'right',
        color: TEXT_DARK,
    },
    totalRow: {
        flexDirection: 'row',
        backgroundColor: SUB_BG,
        borderTop: `1px solid ${BORDER_DARK}`,
        borderBottom: `1px solid ${BORDER_DARK}`,
        paddingVertical: 2,
        paddingHorizontal: 6,
    },
    totalLabel: {
        flex: 1,
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        color: TEXT_DARK,
    },
    totalRp: {
        width: 16,
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        textAlign: 'right',
        color: TEXT_DARK,
    },
    totalAmount: {
        width: 72,
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        textAlign: 'right',
        color: TEXT_DARK,
    },

    // ── Net Salary ──
    netRow: {
        flexDirection: 'row',
        backgroundColor: PRIMARY,
        marginTop: 3,
        paddingVertical: 4,
        paddingHorizontal: 8,
    },
    netLabel: {
        flex: 1,
        fontFamily: 'Helvetica-Bold',
        fontSize: 10,
        color: WHITE,
    },
    netAmount: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 10,
        color: WHITE,
        textAlign: 'right',
    },

    // ── Signatures ──
    sigContainer: {
        flexDirection: 'row',
        marginTop: 6,
    },
    sigLeft: {
        flex: 1,
        alignItems: 'center',
    },
    sigRight: {
        flex: 1,
        alignItems: 'center',
    },
    sigText: {
        fontSize: 7.5,
        color: TEXT_DARK,
        marginBottom: 1,
    },
    sigDate: {
        fontSize: 6.5,
        color: TEXT_DARK,
        marginBottom: 2,
    },
    sigSpace: {
        height: 60,
    },
    sigQr: {
        width: 60,
        height: 60,
        marginVertical: 3,
    },
    sigDateOffset: {
        height: 10,
    },
    sigName: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 7.5,
        color: TEXT_DARK,
        borderBottom: `1px solid ${TEXT_DARK}`,
        paddingBottom: 1,
        paddingHorizontal: 8,
        minWidth: 100,
        textAlign: 'center',
    },
    sigTitle: {
        fontSize: 6.5,
        color: TEXT_LIGHT,
        marginTop: 1,
    },

    // ── Footer ──
    confidential: {
        textAlign: 'center',
        fontSize: 6,
        color: RED,
        fontStyle: 'italic',
        marginTop: 30,
    },
    footer: {
        textAlign: 'center',
        fontSize: 6,
        color: TEXT_MUTED,
        marginTop: 2,
        borderTop: `0.5px solid #ddd`,
        paddingTop: 2,
    },
});

// ─── Helpers ───
const fmt = (n) => {
    const num = Number(n) || 0;
    return num.toLocaleString('id-ID');
};

const fmtHours = (hours) => {
    const totalSec = Math.abs(Math.round((hours || 0) * 3600));
    const h = Math.floor(totalSec / 3600);
    const m = Math.floor((totalSec % 3600) / 60);
    const sec = totalSec % 60;
    return `${h} jam ${m} menit ${sec} detik`;
};

const fmtMinutes = (minutes) => {
    const totalSec = Math.abs(Math.round((minutes || 0) * 60));
    const h = Math.floor(totalSec / 3600);
    const m = Math.floor((totalSec % 3600) / 60);
    const sec = totalSec % 60;
    return `${h} jam ${m} menit ${sec} detik`;
};

// ─── Sub-components ───
const InfoRowComp = ({ leftLabel, leftVal, rightLabel, rightVal, isLast = false }) => (
    <View style={isLast ? s.infoRowLast : s.infoRow}>
        <Text style={s.infoLabel}>{leftLabel}</Text>
        <Text style={s.infoSep}>:</Text>
        <Text style={s.infoVal}>{leftVal}</Text>
        <Text style={s.infoLabel}>{rightLabel}</Text>
        <Text style={s.infoSep}>:</Text>
        <Text style={s.infoValLast}>{rightVal}</Text>
    </View>
);

const DetailRow = ({ label, value }) => (
    <View style={s.detailRow}>
        <Text style={s.detailLabel}>{label}</Text>
        <Text style={s.detailRp}>Rp</Text>
        <Text style={s.detailAmount}>{fmt(value)}</Text>
    </View>
);

const TotalRowComp = ({ label, value }) => (
    <View style={s.totalRow}>
        <Text style={s.totalLabel}>{label}</Text>
        <Text style={s.totalRp}>Rp</Text>
        <Text style={s.totalAmount}>{fmt(value)}</Text>
    </View>
);

// ─── Main Component ───
const SlipGajiPDF = ({ payroll, cutiInfo, qrDataUrl }) => {
    const p = payroll;
    const u = p.user || {};
    const ci = cutiInfo || { jatah_cuti: 12, cuti_terpakai: 0, sisa_cuti: 12 };

    const totalLembur = Number(p.overtime_hourly || 0) + Number(p.overtime_on_call || 0)
        + Number(p.overtime_mod || 0) + Number(p.overtime_holiday || 0);
    const totalPendapatan = Number(p.gross_salary || 0) + totalLembur
        + Number(p.salary_correction || 0) + Number(p.other_allowance || 0);

    const now = new Date();
    const printDate = `${now.getDate()}/${now.getMonth()+1}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

    return (
        <Document title={`Slip Gaji - ${u.name} - ${months[p.month]} ${p.year}`} author="RS Kartika Husada Setu">
            <Page size="A4" style={s.page}>

                {/* ══════ HEADER ══════ */}
                <View style={s.header}>
                    <View style={s.headerInner}>
                        <Image style={s.headerLogo} src="/logo.png" />
                        <View style={s.headerTextBlock}>
                            <Text style={s.hospitalName}>RUMAH SAKIT KARTIKA HUSADA SETU</Text>
                            <Text style={s.hospitalAddr}>
                                Jl. MT. Haryono, Burangkeng, Kec. Setu, Kabupaten Bekasi, Jawa Barat 17320 | Telp: (021) 1234567
                            </Text>
                        </View>
                    </View>
                </View>

                {/* ══════ EMPLOYEE INFO ══════ */}
                <View style={s.infoTable}>
                    <InfoRowComp
                        leftLabel="NAMA PEGAWAI" leftVal={u.name || '-'}
                        rightLabel="PERIODE" rightVal={`${months[p.month]} ${p.year}`}
                    />
                    <InfoRowComp
                        leftLabel="NIP" leftVal={u.nip || u.employee_id || '-'}
                        rightLabel="NO. BPJS KES" rightVal={u.bpjs_kesehatan || '-'}
                    />
                    <InfoRowComp
                        leftLabel="JABATAN" leftVal={u.position || '-'}
                        rightLabel="NO. BPJS TK" rightVal={u.bpjs_ketenagakerjaan || '-'}
                    />
                    <InfoRowComp
                        leftLabel="TGL MASUK" leftVal={u.join_date ? new Date(u.join_date).toLocaleDateString('id-ID') : '-'}
                        rightLabel="NO. NPWP" rightVal={u.npwp || '-'}
                        isLast
                    />
                </View>

                {/* ══════ REKAP KEHADIRAN ══════ */}
                <View style={s.attBox}>
                    <View style={s.sectionTitle}>
                        <Text style={s.sectionTitleText}>REKAP KEHADIRAN</Text>
                    </View>
                    {/* Row 1: Hadir, Terlambat, Tidak Hadir */}
                    <View style={s.attRow}>
                        <Text style={s.attLabel}>Hadir</Text>
                        <Text style={s.attVal}>{p.present_days || 0} hari</Text>
                        <Text style={s.attLabel}>Terlambat</Text>
                        <Text style={s.attVal}>{fmtMinutes(p.late_minutes)}</Text>
                        <Text style={s.attLabel}>Tidak Hadir</Text>
                        <Text style={s.attValLast}>{p.absent_days || 0} hari</Text>
                    </View>
                    {/* Row 2: Lembur, Cuti, Sakit */}
                    <View style={s.attRow}>
                        <Text style={s.attLabel}>Lembur</Text>
                        <Text style={s.attVal}>{fmtHours(p.overtime_hours)}</Text>
                        <Text style={s.attLabel}>Cuti</Text>
                        <Text style={s.attVal}>{p.leave_days || 0} hari</Text>
                        <Text style={s.attLabel}>Sakit</Text>
                        <Text style={s.attValLast}>{p.sick_days || 0} hari</Text>
                    </View>
                    {/* Row 3: Jatah Cuti, Sisa Cuti */}
                    <View style={s.attRowLast}>
                        <Text style={s.attLabel}>Jatah Cuti</Text>
                        <Text style={s.attVal}>{ci.jatah_cuti} hari</Text>
                        <Text style={s.attLabel}>Sisa Cuti</Text>
                        <Text style={s.attVal}>{ci.sisa_cuti} hari</Text>
                        <Text style={[s.attLabel, { backgroundColor: 'transparent', borderRight: 0 }]}></Text>
                        <Text style={s.attValLast}></Text>
                    </View>
                </View>

                {/* ══════ PENDAPATAN ══════ */}
                <View style={s.sectionBox}>
                    <View style={s.sectionTitle}>
                        <Text style={s.sectionTitleText}>PENDAPATAN</Text>
                    </View>

                    <View style={s.subSection}>
                        <Text style={s.subSectionText}>Gaji & Tunjangan</Text>
                    </View>
                    <DetailRow label="Gaji Pokok" value={p.base_salary} />
                    <DetailRow label="Tunjangan Jabatan" value={p.position_allowance} />
                    <DetailRow label="Tunjangan Fungsional" value={p.functional_allowance} />
                    <DetailRow label="Tunjangan Khusus" value={p.special_allowance} />
                    <DetailRow label="Tunjangan Makan" value={p.meal_allowance} />
                    <DetailRow label="Tunjangan Transport" value={p.transport_allowance} />
                    <DetailRow label="Tunjangan Kehadiran" value={p.attendance_allowance} />
                    <TotalRowComp label="BRUTO" value={p.gross_salary} />

                    <View style={s.subSection}>
                        <Text style={s.subSectionText}>Lembur</Text>
                    </View>
                    <DetailRow label="Lembur" value={p.overtime_hourly} />
                    <DetailRow label="On Call" value={p.overtime_on_call} />
                    <DetailRow label="MOD" value={p.overtime_mod} />
                    <DetailRow label="Hari Raya" value={p.overtime_holiday} />
                    <TotalRowComp label="Total Lembur" value={totalLembur} />

                    <View style={s.subSection}>
                        <Text style={s.subSectionText}>Tambahan Lainnya</Text>
                    </View>
                    <DetailRow label="Koreksi Upah (+)" value={p.salary_correction} />
                    <DetailRow label="Lain-lain (+)" value={p.other_allowance} />
                    <TotalRowComp label="TOTAL PENDAPATAN" value={totalPendapatan} />
                </View>

                {/* ══════ POTONGAN ══════ */}
                <View style={s.sectionBox}>
                    <View style={s.sectionTitle}>
                        <Text style={s.sectionTitleText}>POTONGAN</Text>
                    </View>

                    <View style={s.subSection}>
                        <Text style={s.subSectionText}>BPJS & Pajak</Text>
                    </View>
                    <DetailRow label="BPJS Kesehatan (1%)" value={p.bpjs_kesehatan} />
                    <DetailRow label="BPJS TK - JHT (2%)" value={p.bpjs_ketenagakerjaan} />
                    <DetailRow label="BPJS TK - JP (1%)" value={p.bpjs_pensiun_jp || p.bpjs_pensiun} />
                    <DetailRow label="PPh 21" value={p.pph21} />

                    <View style={s.subSection}>
                        <Text style={s.subSectionText}>Potongan Admin</Text>
                    </View>
                    <DetailRow label="CDT" value={p.cdt_deduction} />
                    <DetailRow label="Alpha / Ketidakhadiran" value={p.alpha_deduction} />
                    <DetailRow label="Cashbond" value={p.cashbond_deduction} />
                    <DetailRow label="Piutang Obat" value={p.piutang_obat_deduction} />
                    <DetailRow label="Koreksi Upah" value={p.salary_correction_deduction} />
                    <DetailRow label="Adm. Bank" value={p.bank_admin_deduction} />
                    <DetailRow label="Potongan Lainnya" value={p.other_deduction} />
                    <TotalRowComp label="TOTAL POTONGAN" value={p.total_deduction} />
                </View>

                {/* ══════ GAJI BERSIH ══════ */}
                <View style={s.netRow}>
                    <Text style={s.netLabel}>GAJI BERSIH (Take Home Pay)</Text>
                    <Text style={s.netAmount}>Rp {fmt(p.net_salary)}</Text>
                </View>

                {/* ══════ SIGNATURES ══════ */}
                <View style={s.sigContainer}>
                    <View style={s.sigLeft}>
                        {/* Offset matches the date line height on the right */}
                        <View style={s.sigDateOffset} />
                        <Text style={s.sigText}>Diterima oleh,</Text>
                        {/* Space matches QR height on the right */}
                        <View style={s.sigSpace} />
                        <Text style={s.sigName}>{u.name}</Text>
                        <Text style={s.sigTitle}>Karyawan</Text>
                    </View>
                    <View style={s.sigRight}>
                        <Text style={s.sigDate}>Bekasi, {now.getDate()} {months[p.month]} {p.year}</Text>
                        <Text style={s.sigText}>Disetujui oleh,</Text>
                        {qrDataUrl ? (
                            <Image style={s.sigQr} src={qrDataUrl} />
                        ) : (
                            <View style={s.sigSpace} />
                        )}
                        <Text style={s.sigName}>Yanuwar Syawaludin, S.I.A.P</Text>
                        <Text style={s.sigTitle}>HRD / Admin SDM</Text>
                    </View>
                </View>

                {/* ══════ FOOTER ══════ */}
                <Text style={s.confidential}>
                    Dokumen ini bersifat rahasia dan hanya untuk penerima yang dituju.
                </Text>
                <Text style={s.footer}>
                    Dicetak {printDate} | RS Kartika Husada Setu by: {u.name}
                </Text>

            </Page>
        </Document>
    );
};

export default SlipGajiPDF;
