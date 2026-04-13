import React from 'react';
import { Document, Page, Text, View, Image, StyleSheet } from '@react-pdf/renderer';

const PRIMARY = '#0f3460';
const BORDER = '#ccc';
const TEXT_DARK = '#1a1a2e';
const HEADER_BG = '#e8eef7';

const s = StyleSheet.create({
    page: {
        paddingTop: 20,
        paddingBottom: 20,
        paddingHorizontal: 30,
        fontFamily: 'Helvetica',
        fontSize: 8,
        color: TEXT_DARK,
    },
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
    hospitalName: {
        fontSize: 14,
        fontFamily: 'Helvetica-Bold',
        color: PRIMARY,
        letterSpacing: 0.3,
    },
    hospitalAddr: {
        fontSize: 7,
        color: '#555',
        marginTop: 2,
    },
    title: {
        fontSize: 12,
        fontFamily: 'Helvetica-Bold',
        color: PRIMARY,
        textAlign: 'center',
        marginBottom: 4,
    },
    subtitle: {
        fontSize: 8,
        color: '#555',
        textAlign: 'center',
        marginBottom: 10,
    },
    table: {
        border: `1px solid ${BORDER}`,
    },
    tableHeader: {
        flexDirection: 'row',
        backgroundColor: HEADER_BG,
        borderBottom: `1px solid ${PRIMARY}`,
    },
    tableRow: {
        flexDirection: 'row',
        borderBottom: `0.5px solid ${BORDER}`,
    },
    tableRowAlt: {
        flexDirection: 'row',
        borderBottom: `0.5px solid ${BORDER}`,
        backgroundColor: '#f9fafb',
    },
    cellNo: { width: '4%', paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cellNama: { width: '14%', paddingVertical: 3, paddingHorizontal: 4 },
    cellUnit: { width: '10%', paddingVertical: 3, paddingHorizontal: 4 },
    cellTanggal: { width: '10%', paddingVertical: 3, paddingHorizontal: 4 },
    cellWaktu: { width: '10%', paddingVertical: 3, paddingHorizontal: 4 },
    cellJam: { width: '6%', paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cellKategori: { width: '8%', paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cellBayar: { width: '10%', paddingVertical: 3, paddingHorizontal: 4, textAlign: 'right' },
    cellAlasan: { width: '16%', paddingVertical: 3, paddingHorizontal: 4 },
    cellStatus: { width: '8%', paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cellApproval: { width: '6%', paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    headerText: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 7,
        color: PRIMARY,
        textTransform: 'uppercase',
    },
    cellText: {
        fontSize: 7,
        color: TEXT_DARK,
    },
    statusApproved: { color: '#059669', fontFamily: 'Helvetica-Bold', fontSize: 7 },
    statusRejected: { color: '#e74c3c', fontFamily: 'Helvetica-Bold', fontSize: 7 },
    statusPending: { color: '#d97706', fontFamily: 'Helvetica-Bold', fontSize: 7 },
    totalRow: {
        flexDirection: 'row',
        borderTop: `1px solid ${PRIMARY}`,
        backgroundColor: HEADER_BG,
    },
    totalLabel: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 8,
        color: PRIMARY,
    },
    totalValue: {
        fontFamily: 'Helvetica-Bold',
        fontSize: 8,
        color: '#059669',
    },
    footer: {
        marginTop: 10,
        fontSize: 7,
        color: '#999',
        textAlign: 'right',
    },
});

const CATEGORY_LABELS = {
    lembur: 'Lembur',
    on_call: 'On Call',
    mod: 'MOD',
    hari_raya: 'Hari Raya',
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatRp = (val) => {
    const num = Math.abs(Number(val) || 0);
    return 'Rp ' + num.toLocaleString('id-ID');
};

export default function RekapLemburPDF({ overtimes, dateFrom, dateTo, isAdmin }) {
    const statusLabels = { pending: 'Pending', approved: 'Disetujui', rejected: 'Ditolak' };

    const approvalLabel = (level) => {
        const map = { 1: 'Koord', 2: 'Mgr', 3: 'Admin' };
        return map[level] || '-';
    };

    const getStatusStyle = (status) => {
        if (status === 'approved') return s.statusApproved;
        if (status === 'rejected') return s.statusRejected;
        return s.statusPending;
    };

    const totalBayar = overtimes
        .filter(ot => ot.status === 'approved')
        .reduce((sum, ot) => sum + Math.abs(Number(ot.total_pay) || 0), 0);

    const totalJam = overtimes
        .filter(ot => ot.status === 'approved')
        .reduce((sum, ot) => sum + Math.abs(Number(ot.total_hours) || 0), 0);

    return (
        <Document>
            <Page size="A4" orientation="landscape" style={s.page}>
                {/* Kop Surat */}
                <View style={s.kop}>
                    <Image style={s.kopLogo} src="/logo.png" />
                    <View style={s.kopText}>
                        <Text style={s.hospitalName}>RUMAH SAKIT KARTIKA HUSADA SETU</Text>
                        <Text style={s.hospitalAddr}>Jl. MT. Haryono, Burangkeng, Kec. Setu, Kabupaten Bekasi, Jawa Barat 17320  |  Telp: (021) 1234567</Text>
                    </View>
                </View>

                <Text style={s.title}>REKAP PENGAJUAN LEMBUR</Text>
                <Text style={s.subtitle}>Periode: {dateFrom} s/d {dateTo}</Text>

                {/* Table */}
                <View style={s.table}>
                    {/* Header Row */}
                    <View style={s.tableHeader}>
                        <View style={s.cellNo}><Text style={s.headerText}>No</Text></View>
                        {isAdmin && <View style={s.cellNama}><Text style={s.headerText}>Nama</Text></View>}
                        {isAdmin && <View style={s.cellUnit}><Text style={s.headerText}>Unit</Text></View>}
                        <View style={s.cellTanggal}><Text style={s.headerText}>Tanggal</Text></View>
                        <View style={s.cellWaktu}><Text style={s.headerText}>Waktu</Text></View>
                        <View style={s.cellJam}><Text style={s.headerText}>Jam</Text></View>
                        <View style={s.cellKategori}><Text style={s.headerText}>Kategori</Text></View>
                        {isAdmin && <View style={s.cellBayar}><Text style={s.headerText}>Total Bayar</Text></View>}
                        <View style={s.cellAlasan}><Text style={s.headerText}>Alasan</Text></View>
                        <View style={s.cellStatus}><Text style={s.headerText}>Status</Text></View>
                        <View style={s.cellApproval}><Text style={s.headerText}>Tahap</Text></View>
                    </View>

                    {/* Data Rows */}
                    {overtimes.map((ot, idx) => (
                        <View key={ot.id} style={idx % 2 === 0 ? s.tableRow : s.tableRowAlt}>
                            <View style={s.cellNo}><Text style={s.cellText}>{idx + 1}</Text></View>
                            {isAdmin && <View style={s.cellNama}><Text style={s.cellText}>{ot.user?.name || '-'}</Text></View>}
                            {isAdmin && <View style={s.cellUnit}><Text style={s.cellText}>{ot.user?.unit_model?.name || ot.user?.unit || '-'}</Text></View>}
                            <View style={s.cellTanggal}><Text style={s.cellText}>{formatDate(ot.date)}</Text></View>
                            <View style={s.cellWaktu}><Text style={s.cellText}>{ot.start_time} - {ot.end_time}</Text></View>
                            <View style={s.cellJam}><Text style={s.cellText}>{Math.abs(ot.total_hours)}</Text></View>
                            <View style={s.cellKategori}><Text style={s.cellText}>{CATEGORY_LABELS[ot.category] || ot.category || '-'}</Text></View>
                            {isAdmin && (
                                <View style={s.cellBayar}>
                                    <Text style={ot.status === 'approved' ? s.statusApproved : s.cellText}>
                                        {ot.status === 'approved' ? formatRp(ot.total_pay) : ot.status === 'rejected' ? formatRp(0) : 'Menunggu'}
                                    </Text>
                                </View>
                            )}
                            <View style={s.cellAlasan}><Text style={s.cellText}>{ot.reason || '-'}</Text></View>
                            <View style={s.cellStatus}><Text style={getStatusStyle(ot.status)}>{statusLabels[ot.status]}</Text></View>
                            <View style={s.cellApproval}><Text style={s.cellText}>{ot.status === 'pending' ? approvalLabel(ot.current_approval_level) : '-'}</Text></View>
                        </View>
                    ))}

                    {overtimes.length === 0 && (
                        <View style={s.tableRow}>
                            <View style={{ width: '100%', paddingVertical: 10, alignItems: 'center' }}>
                                <Text style={s.cellText}>Tidak ada data</Text>
                            </View>
                        </View>
                    )}

                    {/* Totals Row (admin only) */}
                    {isAdmin && overtimes.length > 0 && (
                        <View style={s.totalRow}>
                            <View style={{ width: '54%', paddingVertical: 4, paddingHorizontal: 4 }}>
                                <Text style={s.totalLabel}>TOTAL (Disetujui)</Text>
                            </View>
                            <View style={{ width: '10%', paddingVertical: 4, paddingHorizontal: 4, textAlign: 'right' }}>
                                <Text style={s.totalValue}>{formatRp(totalBayar)}</Text>
                            </View>
                            <View style={{ width: '36%', paddingVertical: 4, paddingHorizontal: 4 }}>
                                <Text style={s.totalLabel}>{totalJam} jam</Text>
                            </View>
                        </View>
                    )}
                </View>

                <Text style={s.footer}>Dicetak pada: {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</Text>
            </Page>
        </Document>
    );
}
