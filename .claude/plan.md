# Rencana Implementasi: Update Sistem Penggajian

## Ringkasan Perubahan

User meminta 3 perubahan utama:
1. **Lembur per kategori** dengan tarif tertentu
2. **Template DATA KARYAWAN** - sudah punya kolom NPWP, BPJS (OK)
3. **Template DATA PENGGAJIAN** - struktur baru dengan kalkulasi otomatis

---

## 1. Lembur Per Kategori

### Kategori & Tarif:
| Kategori | Tarif per kejadian |
|----------|-------------------|
| JAM | Rp 60.000 |
| Malam | Rp 100.000 |
| Shift | Rp 80.000 |
| Hari Raya | Rp 150.000 |

### Field Database (Mapping ke existing):
| Baru | Existing Field | Keterangan |
|------|----------------|-----------|
| overtime_jam | overtime_hourly | Rename konsep, hitung per jumlah * 60000 |
| overtime_malam | overtime_night (baru) | Per kejadian * 100000 |
| overtime_shift | overtime_shift | Per kejadian * 80000 |
| overtime_hariraya | overtime_holiday | Per kejadian * 150000 |

**Catatan**: Field `overtime_night` perlu ditambahkan ke migration karena belum ada.

---

## 2. Struktur Penggajian (Excel Reference)

### A. PENDAPATAN (Input Admin = Biru)

**Gaji & Tunjangan:**
- F: Gaji Pokok
- G: Tunjangan Jabatan
- H: Tunjangan Fungsional
- I: Tunjangan Khusus
- J: Tunjangan Transport
- K: Tunjangan Makan
- L: Tunjangan Kehadiran

**→ BRUTO (Auto-calculated = Merah):**
```
BRUTO = Gaji Pokok + Semua Tunjangan
```

**Lembur & Tambahan (Input Admin):**
- Lembur Jam (jumlah × Rp 60.000)
- Lembur Malam (jumlah × Rp 100.000)
- Lembur Shift (jumlah × Rp 80.000)
- Lembur Hari Raya (jumlah × Rp 150.000)
- Koreksi Upah (+)
- Lain-lain

**→ TOTAL PENDAPATAN (Auto-calculated = Merah):**
```
TOTAL PENDAPATAN = BRUTO + Total Lembur + Koreksi Upah + Lain-lain
```

### B. POTONGAN

**Auto-calculated (dari Web):**
- BPJS Kesehatan = BRUTO × 1%
- BPJS TK JHT = BRUTO × 2%
- BPJS TK JP = BRUTO × 1%
- PPh 21 (tarif progresif)

**Input Admin:**
- CDT
- ALPA (Ketidakhadiran)
- Cashbond
- Piutang Obat
- Koreksi Upah (-)
- Adm. Bank

**→ TOTAL POTONGAN (Auto-calculated = Merah):**
```
TOTAL POTONGAN = (BPJS Kes + JHT + JP + PPh21) + (CDT + ALPA + Cashbond + Piutang + Koreksi + Adm Bank)
```

### C. GAJI DIBAYARKAN (Auto-calculated = Merah)
```
GAJI DIBAYARKAN = TOTAL PENDAPATAN - TOTAL POTONGAN
```

---

## 3. File yang Perlu Diubah

### Database Migration (Baru)
```
database/migrations/2026_03_16_000001_update_payroll_overtime_categories.php
```
- Tambah field `overtime_night` (untuk Lembur Malam)
- Rename/clarify existing overtime fields for new rates

### Model
```
app/Models/Payroll.php
```
- Update fillable dengan field overtime baru
- Tambah konstanta tarif lembur
- Update casting

### Controller
```
app/Http/Controllers/PayrollController.php
```
- Update method `calculatePayroll()` dengan kalkulasi baru:
  - BRUTO = sum tunjangan
  - Lembur dihitung per kategori × tarif
  - BPJS dihitung dari BRUTO (bukan base_salary)
  - TOTAL PENDAPATAN = BRUTO + lembur + koreksi + lain2
  - TOTAL POTONGAN = BPJS + PPh + admin deductions
  - GAJI DIBAYARKAN = TOTAL PENDAPATAN - TOTAL POTONGAN

### Frontend
```
resources/js/Pages/Payroll/Edit.jsx
```
- Update section lembur dengan 4 kategori baru
- Tampilkan tarif di label (JAM @60rb, Malam @100rb, dst)
- Kalkulasi realtime

```
resources/js/Pages/Payroll/SlipGaji.jsx
```
- Update tampilan pendapatan dengan struktur baru
- Tampilkan BRUTO sebagai subtotal
- Tampilkan lembur per kategori dengan nominal
- Tampilkan TOTAL PENDAPATAN
- Update potongan dengan BPJS breakdown
- Tampilkan TOTAL POTONGAN
- GAJI DIBAYARKAN

```
resources/views/payroll/slip-pdf.blade.php
```
- Update PDF template sesuai struktur baru

---

## 4. Urutan Implementasi

1. **Migration** - Tambah field overtime_night
2. **Model Payroll** - Update fillable, casts, konstanta
3. **PayrollController** - Update kalkulasi
4. **Edit.jsx** - Update form lembur
5. **SlipGaji.jsx** - Update tampilan
6. **slip-pdf.blade.php** - Update PDF

---

## 5. Catatan Penting

- **Template DATA KARYAWAN** sudah sesuai dengan Excel "(OK) DATA KARYAWAN.xlsx" yang memiliki NPWP, BPJS Kesehatan, BPJS Ketenagakerjaan
- **Tarif lembur** disimpan sebagai konstanta di Model untuk kemudahan update
- **BPJS dihitung dari BRUTO**, bukan dari base_salary saja
- **Field overtime existing** tidak dihapus untuk backward compatibility, hanya ditambah field baru
