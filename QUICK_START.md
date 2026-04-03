# QUICK START GUIDE - Setup Sistem Absensi

## 🚀 Setup Awal (5 Menit)

### Step 1: Fresh Database Setup
```bash
# Terminal / Command Prompt di folder hospital-attendance
php artisan migrate:fresh --force
php artisan db:clear-setup --yes
```

Setelah ini, admin user sudah terbuat dengan:
- **Username:** `admin`
- **Password:** `admin123`

### Step 2: Start Server (Pilih Salah Satu)

**Opsi A: Using XAMPP**
- Buka XAMPP Control Panel
- Start Apache
- Buka browser: http://localhost/hospital-attendance

**Opsi B: Using PHP Built-in Server**
```bash
php artisan serve
# Buka: http://localhost:8000
```

### Step 3: Login
- Username/NIP: `admin`
- Password: `admin123`

---

## 📊 Import Data Karyawan (10 Menit)

### Step 1: Download Template
1. Login sebagai admin
2. Klik menu **"Kelola Karyawan"**
3. Klik tombol **"Import"**
4. Klik **"Unduh Template Excel"**
5. File `Template_DATA_KARYAWAN.xlsx` akan terdownload

### Step 2: Isi Data
Buka file Excel dan isi kolom:
- **NIP** - nomor induk pegawai (misal: 2021C171)
- **Nama** - nama lengkap karyawan
- **Email** - email aktif
- **Departemen** - departemen/unit kerja
- **Jabatan** - posisi/jabatan
- **Tanggal Masuk** - tanggal mulai bekerja
- Dan kolom lain sesuai kebutuhan

**Contoh Data:**
| NIP | Nama | Email | Departemen | Jabatan | Tgl Masuk |
|---|---|---|---|---|---|
| 2021C171 | dr. Jati Sarasanti | jati@rs.com | Dokter Umum | Dokter | 2021-01-15 |
| 2021C172 | Sri Handayani | sri@rs.com | Keperawatan | Perawat | 2021-02-01 |

### Step 3: Upload File
1. Kembali ke halaman import
2. Klik **"Pilih File"** atau drag-drop file Excel
3. Tunggu preview muncul
4. Sistem otomatis akan mapping kolom
5. Klik **"Lanjut"** → **"Konfirmasi"** → **"Import"**

### Step 4: Selesai!
- Karyawan sudah tersimpan
- Password default mereka = NIP mereka
- Karyawan bisa login dengan NIP mereka

---

## 📅 Import Jadwal Karyawan (5 Menit)

### Step 1: Download Template
1. Login sebagai admin
2. Klik menu **"Jadwal Karyawan"** 
3. Klik tombol **"Import"**
4. Klik **"Unduh Template Excel"**

### Step 2: Isi Data
File hanya perlu 3 kolom:

| NIP | Tanggal | Nama Shift |
|---|---|---|
| 2021C171 | 2026-04-01 | Shift Pagi |
| 2021C171 | 2026-04-02 | Shift Sore |
| 2021C172 | 2026-04-01 | Shift Malam |

**Nama Shift yang Tersedia:**
- Lihat di tab "Daftar Shift" di template Excel
- Atau lihat di aplikasi → Jadwal Karyawan

### Step 3: Upload & Import
Sama seperti import karyawan:
1. Upload file
2. Mapping kolom (biasanya otomatis)
3. Konfirmasi
4. Import

---

## 🔑 Login Karyawan

**Home Page:** `http://localhost/hospital-attendance`

**Form Login Baru:**
- Username field sekarang menerima: **NIP**, Email, atau Admin ID
- Contoh:
  - Username: `2021C171` (NIP karyawan)
  - Password: `2021C171` (password default = NIP)

**Catatan:**
- Setiap karyawan harus ganti password mereka setelah login pertama
- Admin tetap bisa login dengan email jika diperlukan

---

## 📋 Menu yang Tersedia

### Untuk Semua Karyawan:
- **Dashboard** - Ringkasan informasi
- **Absensi** - Input absensi & melihat riwayat
- **Pengajuan Cuti** - Buat & lihat pengajuan cuti
  - Fitur baru: Filter by date range (Tanggal dari - sampai)
- **Pengajuan Lembur** - Buat & lihat pengajuan lembur
  - Fitur baru: Filter by date range (Tanggal dari - sampai)
- **Penggajian** - Lihat slip gaji

### Untuk Admin Saja:
- **Jadwal Karyawan** - Kelola jadwal kerja karyawan
  - Fitur baru: Bulk import jadwal dari Excel
- **Kelola Karyawan** - Kelola data karyawan
  - Fitur baru: Bulk import karyawan dari Excel
  - Buat/edit data karyawan manual

---

## 🆘 Troubleshooting

**Problem: Login gagal**
```
Solusi: Cek username/NIP sudah benar. Gunakan NIP dari template Excel saat import.
```

**Problem: Import file tidak bisa diupload**
```
Solusi: 
- Pastikan file format .xlsx, .xls, atau .csv
- File tidak terenkripsi
- Ukuran file < 50MB
```

**Problem: Kolom tidak bisa dipetakan**
```
Solusi: 
- Pastikan header kolom di Excel sesuai format template
- Gunakan nama kolom yang sama persis
- Atau manual mapping saat import preview
```

**Problem: Password karyawan lupa**
```
Solusi: Admin bisa reset password di menu Kelola Karyawan
```

**Problem: Error "Foreign Key Constraint"**
```
Solusi: 
- Jalankan: php artisan db:clear-setup --yes
- Atau cek data integrity di database
```

---

## 📞 Contact

Untuk bantuan teknis lebih lanjut, hubungi developer atau admin sistem.

---

**Last Updated:** 04-04-2026
**Version:** 1.0
