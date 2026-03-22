# 🏠 Network Configuration - RUMAH vs KANTOR

## 🌐 **KONFIGURASI SAAT INI (RUMAH)**
- **IP Address**: `192.168.0.102`
- **Laravel Server**: `http://192.168.100.50:8000`
- **Vite Dev Server**: `http://192.168.100.50:5174`
- **Status**: ✅ AKTIF

---

## 📝 **FILE YANG PERLU DIUBAH KETIKA BALIK KE KANTOR**

### 1. **📄 `.env`** (LINE 5)
```bash
# RUMAH (SEKARANG)
APP_URL=http://192.168.0.102:8000

# KANTOR (NANTI)  
APP_URL=http://192.168.100.50:3000
```

### 2. **⚡ `vite.config.js`** (LINE 12)
```javascript
// RUMAH (SEKARANG)
hmr: {
    host: '192.168.100.50',
},

// KANTOR (NANTI)
hmr: {
    host: '192.168.100.50',
},
```

### 3. **🌐 Apache Virtual Host** (OPTIONAL - jika mau full network)
**File**: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
```apache
# RUMAH (SEKARANG) - tidak perlu setting
# Apache berjalan localhost saja

# KANTOR (NANTI) - untuk network access
<VirtualHost *:80>
    ServerName 192.168.100.50
    ServerAlias *.192.168.100.50
    DocumentRoot "C:/xampp/htdocs/hospital-attendance/public"
    DirectoryIndex index.php index.html
    
    <Directory "C:/xampp/htdocs/hospital-attendance/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. **📋 Windows hosts file** (OPTIONAL - jika mau custom domain)
**File**: `C:\Windows\System32\drivers\etc\hosts`
```bash
# RUMAH (SEKARANG) - tidak perlu
# (kosongkan atau comment)

# KANTOR (NANTI) - custom domain
192.168.100.50 absensi.local
192.168.100.50 hospital.local
```

---

## 🚀 **CARA SWITCH JARINGAN**

### **🏠 UNTUK DEVELOPMENT RUMAH (SEKARANG)**
1. ✅ `.env` → `APP_URL=http://192.168.100.50:8000`
2. ✅ `vite.config.js` → `host: '192.168.100.50'`
3. ✅ Run: `php artisan serve --host=192.168.100.50 --port=8000`
4. ✅ Access: `http://192.168.100.50:8000`

### **🏢 UNTUK JARINGAN KANTOR (NANTI KEMBALI)**
1. 🔄 `.env` → `APP_URL=http://30.30.30.97:3000`
2. 🔄 `vite.config.js` → `host: '30.30.30.97'`
3. 🔄 Apache VirtualHost setup (jika mau network access)
4. 🔄 Clear cache: `php artisan config:clear`
5. 🔄 Restart XAMPP Apache
6. 🔄 Access: `http://30.30.30.97:3000`

---

## ⚡ **COMMANDS UNTUK SWITCH**

### **Clear Cache (WAJIB setelah ganti IP)**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### **Start Development Server**
```bash
# RUMAH
php artisan serve --host=192.168.0.102 --port=8000

# KANTOR (dengan Apache XAMPP aktif)
# Tidak perlu php artisan serve, pakai Apache directly
```

### **Start Assets Development**
```bash
npm run dev
```

---

## 📱 **TESTING DARI DEVICE LAIN**

### **RUMAH**
- **WiFi Device**: `http://192.168.0.102:8000`
- **Mobile/Tab**: Harus terhubung WiFi yang sama

### **KANTOR**  
- **Network Device**: `http://30.30.30.97:3000`
- **Mobile**: Dari network 10.10.10.x, 192.168.20.x, 30.30.30.x

---

## 🔧 **QUICK SETUP SCRIPT**

### **Untuk RUMAH**
```bash
# Update .env
(Get-Content .env) -replace 'APP_URL=http://30.30.30.97:3000', 'APP_URL=http://192.168.0.102:8000' | Set-Content .env

# Update vite.config.js manually
# Clear cache
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Start servers
php artisan serve --host=192.168.0.102 --port=8000 &
npm run dev &
```

### **Untuk KANTOR**
```bash
# Update .env
(Get-Content .env) -replace 'APP_URL=http://192.168.0.102:8000', 'APP_URL=http://30.30.30.97:3000' | Set-Content .env

# Update vite.config.js manually
# Clear cache
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Start XAMPP Apache (via Control Panel)
npm run dev &
```

---

## 🎯 **CURRENT STATUS**
- ✅ **Laravel Server**: Running on `http://192.168.0.102:8000`
- ✅ **XAMPP**: Running on localhost
- ✅ **Vite**: Running on `http://192.168.0.102:5174`
- ✅ **Database**: MySQL via XAMPP
- ✅ **Network**: Home network (192.168.0.x)

---

## 📞 **TROUBLESHOOTING**

### **Tidak bisa akses dari HP**
1. Pastikan HP dan laptop terhubung WiFi yang sama
2. Cek Windows Firewall (allow port 8000)
3. Test ping: `ping 192.168.0.102`

### **Asset tidak load**
1. Pastikan Vite server running (`npm run dev`)
2. Clear browser cache
3. Check network di browser dev tools

### **Database error**
1. Pastikan XAMPP MySQL aktif
2. Check .env database credentials
3. Run `php artisan migrate` jika perlu

---

**💡 INGAT: Setiap ganti IP, selalu jalankan `php artisan config:clear`**