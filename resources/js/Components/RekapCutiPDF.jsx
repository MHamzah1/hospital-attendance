import React from 'react';
import { Document, Page, Text, View, StyleSheet } from '@react-pdf/renderer';

const PRIMARY = '#0f3460';
const BORDER = '#ccc';
const WHITE = '#ffffff';
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
        orientation: 'landscape',
    },
    header: {
        borderBottom: `2px solid ${PRIMARY}`,
        paddingBottom: 8,
        marginBottom: 10,
        alignItems: 'center',
    },
    hospitalName: {
        fontSize: 14,
        fontFamily: 'Helvetica-Bold',
        color: PRIMARY,
        letterSpacing: 0.5,
        marginBottom: 2,
    },
    hospitalAddr: {
        fontSize: 7,
        color: '#555',
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
    cellNama: { width: '16%', paddingVertical: 3, paddingHorizontal: 4 },
    cellUnit: { width: '12%', paddingVertical: 3, paddingHorizontal: 4 },
    cellJenis: { width: '12%', paddingVertical: 3, paddingHorizontal: 4 },
    cellTanggal: { width: '16%', paddingVertical: 3, paddingHorizontal: 4 },
    cellHari: { width: '6%', paddingVertical: 3, paddingHorizontal: 3, textAlign: 'center' },
    cellAlasan: { width: '20%', paddingVertical: 3, paddingHorizontal: 4 },
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
    footer: {
        marginTop: 10,
        fontSize: 7,
        color: '#999',
        textAlign: 'right',
    },
});

const typeLabelsDefault = {
    cuti_tahunan: 'Cuti Tahunan',
    sakit: 'Sakit',
    izin: 'Izin',
    cuti_melahirkan: 'Cuti Melahirkan',
    cuti_khusus: 'Cuti Khusus',
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

export default function RekapCutiPDF({ leaves, dateFrom, dateTo, isAdmin, typeLabels: customTypeLabels }) {
    const labels = customTypeLabels || typeLabelsDefault;
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

    return (
        <Document>
            <Page size="A4" orientation="landscape" style={s.page}>
                {/* Header */}
                <View style={s.header}>
                    <Text style={s.hospitalName}>RS KARTIKA HUSADA SETU</Text>
                    <Text style={s.hospitalAddr}>Jl. Raya Setu No.1, Setu, Bekasi</Text>
                </View>

                <Text style={s.title}>REKAP PENGAJUAN CUTI</Text>
                <Text style={s.subtitle}>Periode: {dateFrom} s/d {dateTo}</Text>

                {/* Table */}
                <View style={s.table}>
                    {/* Header Row */}
                    <View style={s.tableHeader}>
                        <View style={s.cellNo}><Text style={s.headerText}>No</Text></View>
                        {isAdmin && <View style={s.cellNama}><Text style={s.headerText}>Nama</Text></View>}
                        {isAdmin && <View style={s.cellUnit}><Text style={s.headerText}>Unit</Text></View>}
                        <View style={s.cellJenis}><Text style={s.headerText}>Jenis Cuti</Text></View>
                        <View style={s.cellTanggal}><Text style={s.headerText}>Tanggal</Text></View>
                        <View style={s.cellHari}><Text style={s.headerText}>Hari</Text></View>
                        <View style={s.cellAlasan}><Text style={s.headerText}>Alasan</Text></View>
                        <View style={s.cellStatus}><Text style={s.headerText}>Status</Text></View>
                        <View style={s.cellApproval}><Text style={s.headerText}>Tahap</Text></View>
                    </View>

                    {/* Data Rows */}
                    {leaves.map((leave, idx) => (
                        <View key={leave.id} style={idx % 2 === 0 ? s.tableRow : s.tableRowAlt}>
                            <View style={s.cellNo}><Text style={s.cellText}>{idx + 1}</Text></View>
                            {isAdmin && <View style={s.cellNama}><Text style={s.cellText}>{leave.user?.name || '-'}</Text></View>}
                            {isAdmin && <View style={s.cellUnit}><Text style={s.cellText}>{leave.user?.unit_model?.name || leave.user?.unit || '-'}</Text></View>}
                            <View style={s.cellJenis}><Text style={s.cellText}>{labels[leave.type] || leave.type}</Text></View>
                            <View style={s.cellTanggal}><Text style={s.cellText}>{formatDate(leave.start_date)} - {formatDate(leave.end_date)}</Text></View>
                            <View style={s.cellHari}><Text style={s.cellText}>{leave.total_days}</Text></View>
                            <View style={s.cellAlasan}><Text style={s.cellText}>{leave.reason || '-'}</Text></View>
                            <View style={s.cellStatus}><Text style={getStatusStyle(leave.status)}>{statusLabels[leave.status]}</Text></View>
                            <View style={s.cellApproval}><Text style={s.cellText}>{leave.status === 'pending' ? approvalLabel(leave.current_approval_level) : '-'}</Text></View>
                        </View>
                    ))}

                    {leaves.length === 0 && (
                        <View style={s.tableRow}>
                            <View style={{ width: '100%', paddingVertical: 10, alignItems: 'center' }}>
                                <Text style={s.cellText}>Tidak ada data</Text>
                            </View>
                        </View>
                    )}
                </View>

                <Text style={s.footer}>Dicetak pada: {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</Text>
            </Page>
        </Document>
    );
}
