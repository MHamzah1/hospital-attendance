<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ config('app.name', 'Hospital Attendance') }}</title>
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
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header .user-info {
            font-size: 14px;
            opacity: 0.9;
        }
        .container { 
            max-width: 400px; 
            margin: 0 auto; 
            padding: 20px;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .welcome-card .emoji {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .welcome-card h2 {
            color: #2d3748;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #4299e1;
        }
        .stat-card.success { border-left-color: #48bb78; }
        .stat-card.warning { border-left-color: #ed8936; }
        .stat-card.info { border-left-color: #4299e1; }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-number.success { color: #48bb78; }
        .stat-number.warning { color: #ed8936; }
        .stat-number.info { color: #4299e1; }
        .stat-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .menu-item {
            background: white;
            border-radius: 15px;
            padding: 25px 20px;
            text-align: center;
            text-decoration: none;
            color: #2d3748;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .menu-item:hover, .menu-item:active {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .menu-item .icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        .menu-item .label {
            font-size: 14px;
            font-weight: 600;
        }
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .quick-actions h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .action-btn {
            display: block;
            width: 100%;
            background: linear-gradient(45deg, #48bb78, #38a169);
            color: white;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .action-btn:last-child { margin-bottom: 0; }
        .action-btn:hover, .action-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
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
            color: #e53e3e;
            text-align: center;
            padding: 15px;
            text-decoration: none;
            display: block;
            margin-top: 20px;
        }
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #48bb78;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(72, 187, 120, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(72, 187, 120, 0); }
            100% { box-shadow: 0 0 0 0 rgba(72, 187, 120, 0); }
        }
        .pb-nav { padding-bottom: 80px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 Dashboard</h1>
        <div class="user-info">
            <span class="status-indicator"></span>
            Selamat datang, {{ Auth::user()->name ?? 'User' }}!
        </div>
    </div>

    <div class="container pb-nav">
        <div class="welcome-card">
            <div class="emoji">👋</div>
            <h2>Selamat Datang!</h2>
            <p>Hospital Attendance System - Mobile Dashboard</p>
            <small style="color: #718096;">{{ date('l, d F Y') }} - {{ date('H:i') }}</small>
        </div>

        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-number success">{{ date('d') }}</div>
                <div class="stat-label">Hari Ini</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number info">{{ date('H:i') }}</div>
                <div class="stat-label">Waktu</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number warning">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number info">✅</div>
                <div class="stat-label">Status</div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="/mobile/attendance" class="menu-item">
                <span class="icon">⏰</span>
                <span class="label">Absensi</span>
            </a>
            <a href="/mobile/schedule" class="menu-item">
                <span class="icon">📅</span>
                <span class="label">Jadwal</span>
            </a>
            <a href="/mobile/leave" class="menu-item">
                <span class="icon">🏖️</span>
                <span class="label">Cuti</span>
            </a>
            <a href="/mobile/overtime" class="menu-item">
                <span class="icon">⏳</span>
                <span class="label">Lembur</span>
            </a>
        </div>

        <div class="quick-actions">
            <h3>📋 Aksi Cepat</h3>
            <button class="action-btn" onclick="clockIn()">
                🕐 Clock In
            </button>
            <button class="action-btn" onclick="clockOut()">
                🕕 Clock Out
            </button>
        </div>

        <form method="POST" action="{{ route('logout') }}" style="margin-top: 20px;">
            @csrf
            <button type="submit" class="logout-btn">
                🚪 Logout
            </button>
        </form>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="/mobile/dashboard" class="nav-item active">
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
        <a href="/mobile/profile" class="nav-item">
            <span class="icon">👤</span>
            <span>Profile</span>
        </a>
    </div>

    <script>
        console.log('🏥 Hospital Attendance Dashboard - Mobile');
        
        function clockIn() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Processing...';
            btn.disabled = true;
            
            // Simulate clock in process
            setTimeout(() => {
                btn.innerHTML = '✅ Clocked In!';
                btn.style.background = 'linear-gradient(45deg, #48bb78, #38a169)';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            }, 1000);
        }
        
        function clockOut() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Processing...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = '✅ Clocked Out!';
                btn.style.background = 'linear-gradient(45deg, #ed8936, #dd6b20)';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            }, 1000);
        }

        // Add touch feedback for menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            item.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Update time every minute
        setInterval(() => {
            const timeElements = document.querySelectorAll('.stat-number.info');
            if (timeElements.length > 1) {
                timeElements[1].textContent = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
            }
        }, 60000);
    </script>
</body>
</html>