# 🏥 Sistem Absensi Rumah Sakit (Hospital Attendance System)

Sistem manajemen absensi, cuti, lembur, dan penggajian untuk rumah sakit.

## ✨ Fitur Utama
- **Absensi Foto** - Clock in/out menggunakan kamera
- **Pengajuan Cuti** - Form cuti dengan approval admin SDM
- **Pengajuan Lembur** - Form lembur dengan approval admin SDM
- **Penggajian Kompleks** - Termasuk potongan BPJS, PPh 21, dll
- **Slip Gaji** - Dengan detail hari kerja, cuti & lembur
- **Export** - Slip gaji bisa di-export ke PDF & Excel

## 🛠️ Teknologi
- Laravel 11 + Breeze (React)
- React 18 + Inertia.js
- Tailwind CSS
- MySQL (XAMPP)

## 📋 Persyaratan
- PHP >= 8.2
- Composer
- Node.js >= 18 & NPM
- MySQL (XAMPP)
- GD Extension (untuk foto)

## 🚀 Instalasi

### 1. Clone & Install Dependencies
```bash
# Buat project Laravel baru
composer create-project laravel/laravel hospital-attendance
cd hospital-attendance

# Install Breeze dengan React
composer require laravel/breeze --dev
php artisan breeze:install react

# Install additional packages
composer require barryvdh/laravel-dompdf maatwebsite/excel
```

### 2. Copy Source Code
Salin semua file dari folder ini ke project Laravel Anda, timpa file yang ada.

### 3. Konfigurasi Database
Edit file `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hospital_attendance
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Buat Database
Buka phpMyAdmin (http://localhost/phpmyadmin) dan buat database:
```sql
CREATE DATABASE hospital_attendance;
```

### 5. Jalankan Migrasi & Seeder
```bash
php artisan migrate
php artisan db:seed
```

### 6. Buat Storage Link
```bash
php artisan storage:link
```

### 7. Jalankan Aplikasi
```bash
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - Vite (React)
npm run dev
```

### 8. Akses Aplikasi
Buka http://localhost:8000

**Akun Default:**
| Role | Email | Password |
|------|-------|----------|
| Admin SDM | admin@hospital.com | password |
| Karyawan | budi@hospital.com | password |
| Perawat | siti@hospital.com | password |
| Dokter | dokter@hospital.com | password |

## 📁 Struktur Database
- `users` - Data karyawan & admin
- `attendances` - Data absensi (foto)
- `leave_requests` - Pengajuan cuti
- `overtime_requests` - Pengajuan lembur
- `payrolls` - Data penggajian
- `payroll_details` - Detail potongan & tunjangan

## 💰 Komponen Penggajian
**Pendapatan:** Gaji Pokok, Tunjangan Jabatan, Tunjangan Makan, Tunjangan Transport, Lembur
**Potongan:** BPJS Kesehatan (1%), BPJS Ketenagakerjaan (2%), PPh 21, Potongan Ketidakhadiran
