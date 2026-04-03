# 🔧 Network IP Configuration - Setup Manual

> File ini menjelaskan file-file yang perlu diedit ketika Anda pindah ke jaringan baru (kantor/rumah) dengan IP berbeda.

## 📍 IP Saat Ini
- **Lokasi**: Kantor RSKHS
- **IP Address**: `192.168.100.50`
- **Laravel Server**: `http://192.168.100.50:8000`
- **Vite Dev Server**: `http://192.168.100.50:5173`

---

## 🔄 Cara Ganti IP (Saat Pindah Lokasi)

### **Step 1: Cari IP Lokal Anda**
Buka Command Prompt/PowerShell dan jalankan:
```bash
ipconfig
```
Cari baris `IPv4 Address` dalam section `Ethernet adapter` atau `Wireless LAN adapter`
Contoh hasil: `192.168.0.102` atau `192.168.100.50`

---

### **Step 2: Edit File `.env`**

**📄 File**: `c:\xampp\htdocs\hospital-attendance\.env`

**Cari baris ini:**
```
APP_URL=http://192.168.100.50:8000
```

**Ubah ke IP Anda:**
```
APP_URL=http://192.168.XXX.XXX:8000
```

**Contoh:**
- Kantor: `APP_URL=http://192.168.100.50:8000` ✅
- Rumah: `APP_URL=http://192.168.0.102:8000` ✅

---

### **Step 3: Edit File `vite.config.js`**

**📄 File**: `c:\xampp\htdocs\hospital-attendance\vite.config.js` (line ~17-18)

**Sekarang file ini membaca `APP_URL` otomatis, jadi Anda tidak perlu edit IP manual lagi.**

Yang penting, pastikan `APP_URL` di `.env` sudah pakai IP lokal komputer Anda, misalnya:
```bash
APP_URL=http://192.168.0.102:8000
```

---

### **Step 4: Restart Server**

Setelah edit kedua file di atas, jalankan ulang server:

```bash
# Terminal 1 - Laravel Server
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 - Vite Dev Server  
npm run dev
```

Ganti `192.168.XXX.XXX` dengan IP lokal Anda dari Step 1.

---

## 📋 File Checklist

✅ **WAJIB Ganti** (1 file):
- [ ] `.env` → `APP_URL`

❌ **TIDAK perlu ganti:**
- `vite.config.js` untuk IP manual
- Database connection (host = 127.0.0.1 = localhost, tetap sama)
- Port Laravel (8000) dan Vite (5173) tetap sama
- File konfigurasi lain

---

## 🧪 Verifikasi Koneksi

Setelah setup, tests koneksi dengan:

```bash
# Test 1: Laravel Server
curl http://192.168.XXX.XXX:8000

# Test 2: Buka di browser
http://192.168.XXX.XXX:8000
```

Jika muncul halaman login, berarti konfigurasi berhasil! ✅

---

## 📱 Akses dari Mobile (HP)

Setelah ganti IP, Anda bisa akses dari HP dengan:
```
http://192.168.XXX.XXX:8000
```

Asalkan HP dan komputer terhubung ke jaringan yang sama (WiFi/LAN yang sama).

---

## ⚠️ Troubleshooting

### "ERR_CONNECTION_REFUSED"
- Pastikan IP di `.env` dan `vite.config.js` sudah sama ✓
- Pastikan Laravel server dan Vite dev server sudah running ✓
- Cek lagi hasil `ipconfig` untuk IP yang benar ✓

### "Vite server tidak connect"
- Sekiranya Vite port 5173 blocked, ganti di `vite.config.js`:
  ```javascript
  port: 5173,  // Ubah ke 5174 atau port lain jika error
  ```

### "Database error"
- Database localhost (127.0.0.1) tidak perlu ganti
- Cek MySQL/XAMPP apakah sudah running

---

## 💾 Network Configuration History

| Tanggal | Lokasi | IP Address | Status |
|---------|--------|-----------|---------|
| 2026-03-13 | Kantor | 192.168.100.50 | ✅ Aktif |
| 2026-02-28 | Rumah | 192.168.0.102 | 📦 Previous |
| 2026-01-01 | Kantor (Lama) | 30.30.30.97 | ⏹️ Old |

---

## 🚀 Quick Reference

```bash
# Jika di kantor (IP: 192.168.100.50)
php artisan serve --host=0.0.0.0 --port=8000

# Jika di rumah (IP: 192.168.0.102)
php artisan serve --host=0.0.0.0 --port=8000

# Jika tidak tahu IP-nya, cek dulu
ipconfig
```

---

**Pertanyaan?** Lihat file `NETWORK-CONFIG-NOTES.md` untuk detail teknis lebih lanjut.
