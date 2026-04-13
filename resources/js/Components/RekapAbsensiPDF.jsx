import React from 'react';
import { Document, Page, Text, View, Image, StyleSheet } from '@react-pdf/renderer';

const PRIMARY = '#0f3460';
const BORDER = '#ccc';
const TEXT_DARK = '#1a1a2e';
const HEADER_BG = '#e8eef7';

const s = StyleSheet.create({
    page: {
        paddingTop: 18,
        paddingBottom: 18,
        paddingHorizontal: 28,
        fontFamily: 'Helvetica',
        fontSize: 8,
        color: TEXT_DARK,
    },

    // ── Kop surat ──
    kop: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingBottom: 8,
        borderBottom: `2px solid ${PRIMARY}`,
        marginBottom: 10,
    },
    kopLogo: { width: 52, height: 52, marginRight: 12 },
    kopText: { flexDirection: 'column' },
    kopName: {
        fontSize: 14,
        fontFamily: 'Helvetica-Bold',
        color: PRIMARY,
        letterSpacing: 0.3,
    },
    kopAddr: { fontSize: 7, color: '#555', marginTop: 2 },

    title: {
        fontSize: 11,
        fontFamily: 'Helvetica-Bold',
        color: PRIMARY,
        textAlign: 'center',
        marginBottom: 3,
    },
    subtitle: {
        fontSize: 8,
        color: '#555',
        textAlign: 'center',
        marginBottom: 10,
    },

    // ── Table ──
    table: { border: `1px solid ${BORDER}` },
    thead: {
        flexDirection: 'row',
        backgroundColor: HEADER_BG,
        borderBottom: `1px solid ${PRIMARY}`,
    },
    tr: {
        flexDirection: 'row',
        borderBottom: `0.5px solid ${BORDER}`,
    },
    trAlt: {
        flexDirection: 'row',
        borderBottom: `0.5px solid ${BORDER}`,
        backgroundColor: '#f9fafb',
    },

    // Admin columns
    cNo:     { width: '4%',  paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cTgl:    { width: '10%', paddingVertical: 3, paddingHorizontal: 4 },
    cHari:   { width: '8%',  paddingVertical: 3, paddingHorizontal: 4 },
    cNama:   { width: '14%', paddingVertical: 3, paddingHorizontal: 4 },
    cShift:  { width: '9%',  paddingVertical: 3, paddingHorizontal: 4 },
    cJam:    { width: '9%',  paddingVertical: 3, paddingHorizontal: 4 },
    cIn:     { width: '8%',  paddingVertical: 3, paddingHorizontal: 4, textAlign: 'center' },
    cOut:    { width: '8%',  paddingVertical: 3, paddingHorizontal: 4, textAlign: 'center' },
    cStatus: { width: '9%',  paddingVertical: 3, paddingHorizontal: 4, textAlign: 'center' },
    cKet:    { flex: 1,      paddingVertical: 3, paddingHorizontal: 4 },

    // Non-admin (no Karyawan)
    cNo2:     { width: '4%',  paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cTgl2:    { width: '13%', paddingVertical: 3, paddingHorizontal: 4 },
    cHari2:   { width: '11%', paddingVertical: 3, paddingHorizontal: 4 },
    cShift2:  { width: '13%', paddingVertical: 3, paddingHorizontal: 4 },
    cJam2:    { width: '12%', paddingVertical: 3, paddingHorizontal: 4 },
    cIn2:     { width: '10%', paddingVertical: 3, paddingHorizontal: 4, textAlign: 'center' },
    cOut2:    { width: '10%', paddingVertical: 3, paddingHorizontal: 4, textAlign: 'center' },
    cStatus2: { width: '10%', paddingVertical: 3, paddingHorizontal: 4, textAlign: 'center' },
    cKet2:    { flex: 1,      paddingVertical: 3, paddingHorizontal: 4 },

    th: { fontFamily: 'Helvetica-Bold', fontSize: 7, color: PRIMARY, textTransform: 'uppercase' },
    td: { fontSize: 7, color: TEXT_DARK },

    statusHadir:     { fontSize: 7, color: '#059669', fontFamily: 'Helvetica-Bold' },
    statusTerlambat: { fontSize: 7, color: '#d97706', fontFamily: 'Helvetica-Bold' },
    statusTidakHadir:{ fontSize: 7, color: '#e74c3c', fontFamily: 'Helvetica-Bold' },
    statusCuti:      { fontSize: 7, color: '#3b82f6', fontFamily: 'Helvetica-Bold' },
    statusSakit:     { fontSize: 7, color: '#8b5cf6', fontFamily: 'Helvetica-Bold' },

    // ── Summary ──
    summaryBox: {
        flexDirection: 'row',
        marginTop: 8,
        borderTop: `1px solid ${PRIMARY}`,
        paddingTop: 6,
        gap: 8,
    },
    summaryItem: {
        flex: 1,
        border: `0.5px solid ${BORDER}`,
        paddingVertical: 4,
        paddingHorizontal: 6,
        alignItems: 'center',
        backgroundColor: HEADER_BG,
    },
    summaryNum:   { fontSize: 11, fontFamily: 'Helvetica-Bold', color: PRIMARY },
    summaryLabel: { fontSize: 6.5, color: '#555', marginTop: 1 },

    footer: { marginTop: 8, fontSize: 7, color: '#999', textAlign: 'right' },
});

const STATUS_LABELS = {
    present: 'Hadir',
    late:    'Terlambat',
    absent:  'Tidak Hadir',
    leave:   'Cuti',
    sick:    'Sakit',
};

const HARI_ID = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

const fmtDate = (d) => {
    if (!d) return '-';
    const dt = new Date(d);
    return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getDay = (d) => {
    if (!d) return '-';
    return HARI_ID[new Date(d).getDay()];
};

const statusStyle = (status) => {
    const map = {
        present: s.statusHadir,
        late:    s.statusTerlambat,
        absent:  s.statusTidakHadir,
        leave:   s.statusCuti,
        sick:    s.statusSakit,
    };
    return map[status] || s.td;
};

export default function RekapAbsensiPDF({ attendances, dateFrom, dateTo, isAdmin, summary }) {
    const c  = (key) => s[key];

    // Decide column set
    const cols = isAdmin ? {
        no: 'cNo', tgl: 'cTgl', hari: 'cHari', nama: 'cNama', shift: 'cShift',
        jam: 'cJam', cin: 'cIn', cout: 'cOut', status: 'cStatus', ket: 'cKet',
    } : {
        no: 'cNo2', tgl: 'cTgl2', hari: 'cHari2', shift: 'cShift2',
        jam: 'cJam2', cin: 'cIn2', cout: 'cOut2', status: 'cStatus2', ket: 'cKet2',
    };

    return (
        <Document>
            <Page size="A4" orientation="landscape" style={s.page}>

                {/* Kop Surat */}
                <View style={s.kop}>
                    <Image style={s.kopLogo} src="/logo.png" />
                    <View style={s.kopText}>
                        <Text style={s.kopName}>RUMAH SAKIT KARTIKA HUSADA SETU</Text>
                        <Text style={s.kopAddr}>Jl. MT. Haryono, Burangkeng, Kec. Setu, Kabupaten Bekasi, Jawa Barat 17320  |  Telp: (021) 1234567</Text>
                    </View>
                </View>

                <Text style={s.title}>REKAP ABSENSI KARYAWAN</Text>
                <Text style={s.subtitle}>Periode: {dateFrom} s/d {dateTo}</Text>

                {/* Table */}
                <View style={s.table}>
                    <View style={s.thead}>
                        <View style={c(cols.no)}><Text style={s.th}>No</Text></View>
                        <View style={c(cols.tgl)}><Text style={s.th}>Tanggal</Text></View>
                        <View style={c(cols.hari)}><Text style={s.th}>Hari</Text></View>
                        {isAdmin && <View style={c(cols.nama)}><Text style={s.th}>Karyawan</Text></View>}
                        <View style={c(cols.shift)}><Text style={s.th}>Shift</Text></View>
                        <View style={c(cols.jam)}><Text style={s.th}>Jam</Text></View>
                        <View style={c(cols.cin)}><Text style={s.th}>Clock In</Text></View>
                        <View style={c(cols.cout)}><Text style={s.th}>Clock Out</Text></View>
                        <View style={c(cols.status)}><Text style={s.th}>Status</Text></View>
                        <View style={c(cols.ket)}><Text style={s.th}>Keterangan</Text></View>
                    </View>

                    {attendances.map((att, idx) => (
                        <View key={att.id} style={idx % 2 === 0 ? s.tr : s.trAlt}>
                            <View style={c(cols.no)}><Text style={s.td}>{idx + 1}</Text></View>
                            <View style={c(cols.tgl)}><Text style={s.td}>{fmtDate(att.date)}</Text></View>
                            <View style={c(cols.hari)}><Text style={s.td}>{getDay(att.date)}</Text></View>
                            {isAdmin && <View style={c(cols.nama)}><Text style={s.td}>{att.user?.name || '-'}</Text></View>}
                            <View style={c(cols.shift)}><Text style={s.td}>{att.shift?.name || '-'}</Text></View>
                            <View style={c(cols.jam)}>
                                <Text style={s.td}>
                                    {att.shift?.start_time && att.shift?.end_time
                                        ? `${att.shift.start_time.slice(0,5)} - ${att.shift.end_time.slice(0,5)}`
                                        : '-'}
                                </Text>
                            </View>
                            <View style={c(cols.cin)}><Text style={s.td}>{att.clock_in || '-'}</Text></View>
                            <View style={c(cols.cout)}><Text style={s.td}>{att.clock_out || '-'}</Text></View>
                            <View style={c(cols.status)}><Text style={statusStyle(att.status)}>{STATUS_LABELS[att.status] || att.status}</Text></View>
                            <View style={c(cols.ket)}>
                                <Text style={s.td}>
                                    {att.status === 'late' && att.late_duration ? att.late_duration : '-'}
                                </Text>
                            </View>
                        </View>
                    ))}

                    {attendances.length === 0 && (
                        <View style={s.tr}>
                            <View style={{ width: '100%', paddingVertical: 10, alignItems: 'center' }}>
                                <Text style={s.td}>Tidak ada data</Text>
                            </View>
                        </View>
                    )}
                </View>

                {/* Summary */}
                {summary && (
                    <View style={s.summaryBox}>
                        {[
                            { label: 'Total', val: summary.total },
                            { label: 'Hadir', val: summary.present },
                            { label: 'Terlambat', val: summary.late },
                            { label: 'Tidak Hadir', val: summary.absent },
                            { label: 'Cuti', val: summary.leave },
                            { label: 'Sakit', val: summary.sick },
                        ].map(item => (
                            <View key={item.label} style={s.summaryItem}>
                                <Text style={s.summaryNum}>{item.val}</Text>
                                <Text style={s.summaryLabel}>{item.label}</Text>
                            </View>
                        ))}
                    </View>
                )}

                <Text style={s.footer}>
                    Dicetak pada: {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                </Text>
            </Page>
        </Document>
    );
}
