# Dokumentasi Sistem Absensi - Update Fitur Baru

## Overview Fitur yang Ditambahkan

Sistem telah diupdate dengan fitur-fitur baru berikut:

### 1. **Login dengan NIP**
- Karyawan sekarang login menggunakan **NIP** sebagai username (bukan email)
- Admin login menggunakan username **"admin"** dengan password **"admin123"**
- Formulir login telah diperbarui untuk menerima NIP/Email/Admin ID

### 2. **6 Menu Utama**
Sistem sekarang memiliki 6 menu menu sebagai berikut:
1. **Dashboard** - Ringkasan informasi
2. **Absensi** - Input dan tracking absensi karyawan
3. **Pengajuan Cuti** - Manajemen cuti dengan filter tanggal
4. **Pengajuan Lembur** - Manajemen lembur dengan filter tanggal
5. **Penggajian** - Manajemen penggajian
6. **Jadwal Karyawan** - Manajemen jadwal kerja (baru untuk admin)
7. **Kelola Karyawan** - Manajemen data karyawan dengan import massal (solo untuk admin)

### 3. **Bulk Import Karyawan**
Admin dapat mengimport data karyawan secara massal menggunakan file Excel (.xlsx, .xls, atau .csv).

**File Format Excel (DATA KARYAWAN.xlsx):**
```
Kolom | Nama Kolom | Tipe Data | Contoh
------|------------|-----------|----------
A     | NIP        | Text      | 2021C171
B     | Nama       | Text      | dr. Jati Sarasanti
C     | Email      | Text      | jati@hospital.com
D     | Jenis Kelamin | Text   | L/P
E     | Pendidikan | Text      | S1
F     | Tempat Lahir | Text    | Surabaya
G     | Tanggal Lahir | Date   | 1990-05-15
H     | Alamat     | Text      | Jl. Kesehatan No. 1
I     | Kota       | Text      | Surabaya
J     | No. HP     | Text      | 08123456789
K     | Departemen | Text      | Dokter Umum
L     | Jabatan    | Text      | Dokter
M    | Tanggal Masuk | Date   | 2021-01-15
N     | NPWP       | Text      | 12.345.678.9-012.000
O     | BPJS Kesehatan | Text  | 0001234567890
P     | BPJS TK    | Text      | 0001234567890
Q     | Nama Rekening | Text   | PT Bank ABC
R     | Nomor Rekening | Text  | 1234567890
S     | Status     | Text      | AKTIF/NONAKTIF
```

**Cara Import:**
1. Admin login dengan username "admin"
2. Masuk ke menu "Kelola Karyawan"
3. Klik tombol "Import" atau "Bulk Import"
4. Upload file Excel yang sudah disiapkan
5. Sistem akan preview data dan meminta mapping kolom
6. Konfirmasi dan proses import
7. Password default untuk karyawan yang diimport = NIP mereka

### 4. **Bulk Import Jadwal Karyawan**
Admin dapat mengimport jadwal kerja karyawan secara massal.

**File Format Excel (JADWAL KARYAWAN.xlsx):**
```
Kolom | Nama Kolom | Tipe Data | Contoh
------|------------|-----------|----------
A     | NIP        | Text      | 2021C171
B     | Tanggal    | Date      | 2026-04-01
C     | Nama Shift | Text      | Shift Pagi
```

**Daftar Shift yang Tersedia:**
Sistem akan menampilkan daftar shift yang tersedia di aplikasi.

**Cara Import:**
1. Admin login dengan username "admin"
2. Masuk ke menu "Jadwal Karyawan"
3. Klik tombol "Import"
4. Upload file Excel jadwal
5. Lakukan mapping kolom jika diperlukan
6. Konfirmasi dan proses import

### 5. **Filter Tanggal untuk Pengajuan Cuti dan Lembur**
Menu Pengajuan Cuti dan Pengajuan Lembur sekarang memiliki filter tanggal:
- **Tanggal Mulai** - Filter dari tanggal
- **Tanggal Akhir** - Filter sampai tanggal
- **Reset** - Hapus filter tanggal

Admin dapat melihat semua pengajuan dengan filter per karyawan (tersembunyi untuk karyawan biasa yang hanya melihat data mereka).

## Setup Pertama Kali

### 1. Bersihkan Data Lama (OPSIONAL)
Jika ingin memulai dari awal dengan data bersih:
```bash
php artisan db:clear-setup --yes
```
Perintah ini akan:
- Hapus semua data dari database (karyawan, absensi, cuti, lembur, dll)
- Buat user admin baru dengan:
  - **NIP/Username:** admin
  - **Password:** admin123
  - **Role:** Admin SDM

### 2. Download Template Excel
Ada 2 template yang bisa didownload:
- **Template Data Karyawan** - Dari menu Kelola Karyawan > Import
- **Template Jadwal Karyawan** - Dari menu Jadwal Karyawan > Import

### 3. Siapkan Data
1. Isi data karyawan di template Excel sesuai format
2. Jika ada, isi juga jadwal karyawan
3. Pastikan NIP unik dan valid

### 4. Import Data
1. Login sebagai admin dengan username "admin" dan password "admin123"
2. Import data karyawan
3. Setup jadwal karyawan
4. Karyawan sudah bisa login dengan NIP mereka dan password = NIP mereka (bisa diubah kemudian)

## Catatan Penting

1. **Keamanan Password Default**
   - Setelah import, karyawan harus mengganti password mereka
   - Password default = NIP mereka

2. **Format NIP**
   - NIP harus unik (tidak boleh duplikat)
   - NIP digunakan sebagai username login
   - NIP juga digunakan sebagai Employee ID

3. **Validasi Data**
   - Sistem otomatis mengecek duplikasi NIP dan Email
   - Kolom wajib isi: NIP, Nama, Email untuk karyawan
   - Kolom wajib isi: NIP, Tanggal, Nama Shift untuk jadwal

4. **Error Handling**
   - Jika ada error import, sistem akan menampilkan pesan detail
   - Bisa mengulang import tanpa khawatir duplikat data

## Perubahan Database

- **Kolom `nip`** ditambahkan ke tabel `users` sebagai unique index
- **Email** masih bisa digunakan untuk beberapa keperluan, tapi login utama menggunakan NIP
- Semua data lama tetap tersimpan jika tidak jalankan `db:clear-setup`

## API Routes (untuk Frontend Integration)

### Authentication
- `POST /login` - Login dengan NIP/Email

### Employee Management
- `GET /employees` - List karyawan (admin only)
- `POST /employees` - Tambah karyawan (admin only)
- `GET /employees/import` - Form import (admin only)
- `POST /bulk-import/preview` - Preview file (admin only)
- `POST /bulk-import/process` - Process import (admin only)

### Schedule Management
- `GET /schedule` - List jadwal (admin only)
- `GET /schedule/import` - Form import jadwal (admin only)
- `POST /schedule/import/preview` - Preview jadwal file (admin only)
- `POST /schedule/import` - Process jadwal import (admin only)

### Leave & Overtime with Date Filter
- `GET /leaves?status=all&date_from=2026-04-01&date_to=2026-04-30` - Filter dengan tanggal
- `GET /overtimes?status=all&date_from=2026-04-01&date_to=2026-04-30` - Filter dengan tanggal

## FAQ

**Q: Bagaimana jika karyawan lupa password?**
A: Admin bisa reset password mereka di menu Kelola Karyawan.

**Q: Apakah bisa import dari CSV?**
A: Ya, format CSV juga didukung dengan delimiter koma.

**Q: Bagaimana jika ada error saat import?**
A: Sistem akan menampilkan baris mana yang error dan sebabnya, lalu kamu bisa perbaiki di file Excel dan import lagi.

**Q: Apakah data lama hilang?**
A: Data lama tetap ada kecuali kamu jalankan `php artisan db:clear-setup --yes`

---

**Versi:** Update April 2026
**Last Modified:** 2026-04-03
