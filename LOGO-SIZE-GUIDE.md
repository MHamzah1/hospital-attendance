# Logo Sizing Guide - Hospital Attendance System

## 🎨 Logo Customization Notes

### Current Logo Settings:
- **File**: public/logo.png
- **Size**: 80px x 80px (edit untuk mengubah ukuran)
- **Background**: White circle with shadow
- **Position**: Center of page

### 📏 Cara Edit Size Logo:

#### 1. React Components (Main App):
File locations that use logo:
- `resources/js/Pages/Auth/Login.jsx`
- `resources/js/Pages/Dashboard.jsx`  
- `resources/js/Pages/Attendance/Index.jsx`
- And other .jsx files

Edit di setiap file:
```jsx
<img 
    src="/logo.png" 
    alt="Logo" 
    className="w-16 h-16"  // Tailwind: w-20 h-20 untuk lebih besar
    style={{width: '80px', height: '80px'}}  // Atau gunakan style langsung
/>
```

#### 2. Mobile Debug Page:
File: `public/mobile-debug.php`
Edit CSS section:
```css
.logo-img { 
    width: 100px;  /* EDIT INI - Ganti 80px jadi 100px untuk lebih besar */
    height: 100px; /* EDIT INI - Ganti 80px jadi 100px untuk lebih besar */
    background: white;
    border-radius: 50%;
    padding: 10px;
    /* ... other styles */
}
```

#### 3. Mobile Blade Templates:
File locations:
- `resources/views/mobile/`*.blade.php files

Edit tag img:
```html
<img src="/logo.png" alt="Logo" style="width: 100px; height: 100px;">
```

### 🎯 Common Logo Sizes:
- **Small**: 40px x 40px (w-10 h-10 in Tailwind)
- **Medium**: 64px x 64px (w-16 h-16 in Tailwind)  
- **Large**: 80px x 80px (w-20 h-20 in Tailwind)
- **Extra Large**: 100px x 100px (w-24 h-24 in Tailwind)

### ⚡ Quick Size Updates:
1. **Global CSS approach**: Create a CSS class in `resources/css/app.css`:
```css
.hospital-logo {
    width: 100px !important;
    height: 100px !important;
}
```

2. **Then use class**: `<img src="/logo.png" class="hospital-logo">`

### 📝 Current Status:
- ✅ Logo background: White circle (fixed green issue)
- ✅ Logo file: Available at /logo.png
- ✅ Mobile debug updated: Logo shows properly
- 📏 Size: 80px (editable via instructions above)

### 🔧 To Change Logo Size:
1. **Quick**: Edit mobile-debug.php (line ~24): Change 80px to your desired size
2. **Global**: Create CSS class and apply to all components
3. **Individual**: Edit each .jsx and .blade.php file separately

Logo sekarang sudah fixed dengan background putih! 🎉