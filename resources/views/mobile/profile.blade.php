<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - {{ config('app.name', 'Hospital Attendance') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: #f7fafc;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            text-decoration: none;
            font-size: 20px;
        }
        .container { 
            max-width: 400px; 
            margin: 0 auto; 
            padding: 20px;
            padding-bottom: 80px;
        }
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 30px;
        }
        .profile-name {
            font-size: 22px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .profile-role {
            color: #718096;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #4299e1;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            color: #718096;
        }
        .menu-section {
            background: white;
            border-radius: 15px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 20px;
            text-decoration: none;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s ease;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .menu-item:hover {
            background: #f7fafc;
        }
        .menu-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }
        .menu-content {
            flex: 1;
        }
        .menu-title {
            font-weight: 600;
            margin-bottom: 2px;
        }
        .menu-desc {
            font-size: 12px;
            color: #718096;
        }
        .menu-arrow {
            color: #cbd5e0;
            font-size: 16px;
        }
        .info-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-title {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #718096;
            font-size: 14px;
        }
        .info-value {
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #718096;
            font-size: 12px;
            transition: color 0.3s ease;
        }
        .nav-item.active { color: #4299e1; }
        .nav-item .icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .logout-btn {
            background: #f56565;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            display: block;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background: #e53e3e;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/mobile/dashboard" class="back-btn">←</a>
        <h1>👤 Profile</h1>
        <div style="font-size: 14px; opacity: 0.9;">
            Pengaturan Akun
        </div>
    </div>

    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-image">{{ substr(auth()->user()->name ?? 'User', 0, 1) }}</div>
            <div class="profile-name">{{ auth()->user()->name ?? 'John Doe' }}</div>
            <div class="profile-role">{{ auth()->user()->role ?? 'Perawat' }} - RS Dr. Soetomo</div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-number">24</div>
                    <div class="stat-label">Hari Kerja</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Kehadiran</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">15</div>
                    <div class="stat-label">Overtime</div>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="info-section">
            <div class="info-title">
                💻 Informasi Personal
            </div>
            <div class="info-row">
                <span class="info-label">NIP</span>
                <span class="info-value">{{ auth()->user()->employee_id ?? '2024010001' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value">{{ auth()->user()->email ?? 'john@hospital.com' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Departemen</span>
                <span class="info-value">{{ auth()->user()->department ?? 'Perawatan' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Shift</span>
                <span class="info-value">{{ auth()->user()->shift ?? 'Pagi (07:00-15:00)' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Bergabung</span>
                <span class="info-value">{{ auth()->user()->created_at ? auth()->user()->created_at->format('d M Y') : '01 Jan 2024' }}</span>
            </div>
        </div>

        <!-- Menu Section 1 -->
        <div class="menu-section">
            <a href="#" class="menu-item" onclick="showComingSoon()">
                <div class="menu-icon" style="background: #ebf8ff; color: #3182ce;">✏️</div>
                <div class="menu-content">
                    <div class="menu-title">Edit Profile</div>
                    <div class="menu-desc">Ubah informasi personal</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
            <a href="#" class="menu-item" onclick="showComingSoon()">
                <div class="menu-icon" style="background: #f0fff4; color: #38a169;">🔒</div>
                <div class="menu-content">
                    <div class="menu-title">Ganti Password</div>
                    <div class="menu-desc">Ubah kata sandi akun</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
            <a href="#" class="menu-item" onclick="showComingSoon()">
                <div class="menu-icon" style="background: #fffdf0; color: #d69e2e;">🔔</div>
                <div class="menu-content">
                    <div class="menu-title">Notifikasi</div>
                    <div class="menu-desc">Pengaturan pemberitahuan</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
        </div>

        <!-- Menu Section 2 -->
        <div class="menu-section">
            <a href="/mobile/leave" class="menu-item">
                <div class="menu-icon" style="background: #fef5e7; color: #dd6b20;">🇮🇳</div>
                <div class="menu-content">
                    <div class="menu-title">Riwayat Cuti</div>
                    <div class="menu-desc">Lihat pengajuan cuti</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
            <a href="/mobile/overtime" class="menu-item">
                <div class="menu-icon" style="background: #f0f4ff; color: #4299e1;">⏰</div>
                <div class="menu-content">
                    <div class="menu-title">Riwayat Lembur</div>
                    <div class="menu-desc">Lihat riwayat overtime</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
            <a href="#" class="menu-item" onclick="showComingSoon()">
                <div class="menu-icon" style="background: #edf2f7; color: #4a5568;">📊</div>
                <div class="menu-content">
                    <div class="menu-title">Laporan</div>
                    <div class="menu-desc">Laporan kehadiran bulanan</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
        </div>

        <!-- Menu Section 3 -->
        <div class="menu-section">
            <a href="#" class="menu-item" onclick="showComingSoon()">
                <div class="menu-icon" style="background: #e6fffa; color: #2c7a7b;">⚙️</div>
                <div class="menu-content">
                    <div class="menu-title">Pengaturan</div>
                    <div class="menu-desc">Pengaturan aplikasi</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
            <a href="#" class="menu-item" onclick="showComingSoon()">
                <div class="menu-icon" style="background: #fef5e7; color: #d69e2e;">❓</div>
                <div class="menu-content">
                    <div class="menu-title">Bantuan</div>
                    <div class="menu-desc">FAQ dan dukungan</div>
                </div>
                <div class="menu-arrow">›</div>
            </a>
        </div>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn" onclick="confirmLogout(event)">
                🚪 Logout
            </button>
        </form>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="/mobile/dashboard" class="nav-item">
            <span class="icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="/mobile/attendance" class="nav-item">
            <span class="icon">⏰</span>
            <span>Absensi</span>
        </a>
        <a href="/mobile/schedule" class="nav-item">
            <span class="icon">📅</span>
            <span>Jadwal</span>
        </a>
        <a href="/mobile/profile" class="nav-item active">
            <span class="icon">👤</span>
            <span>Profile</span>
        </a>
    </div>

    <script>
        console.log('🏥 Hospital Attendance - Profile Mobile');
        
        function showComingSoon() {
            alert('🚀 Fitur ini akan segera tersedia!');
        }
        
        function confirmLogout(event) {
            if (!confirm('🚪 Apakah Anda yakin ingin logout?')) {
                event.preventDefault();
            }
        }

        // Add touch feedback
        document.querySelectorAll('.nav-item, .menu-item').forEach(item => {
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            item.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>