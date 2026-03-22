<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi - {{ config('app.name', 'Hospital Attendance') }}</title>
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
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-card {
            text-align: center;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(72, 187, 120, 0.3);
        }
        .status-card.out {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            box-shadow: 0 8px 20px rgba(237, 137, 54, 0.3);
        }
        .status-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .status-text {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .status-time {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .clock-btn {
            width: 100%;
            background: white;
            color: #48bb78;
            border: 3px solid white;
            padding: 18px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .clock-btn:hover {
            background: rgba(255,255,255,0.9);
        }
        .clock-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .attendance-history {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .history-title {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            background: #f7fafc;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        .history-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .history-date {
            font-weight: 600;
            color: #2d3748;
        }
        .history-times {
            text-align: right;
            font-size: 14px;
            color: #718096;
        }
        .history-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-present {
            background: #c6f6d5;
            color: #2d7d32;
        }
        .status-late {
            background: #fed7d7;
            color: #c53030;
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
        .location-info {
            background: #e6fffa;
            border-left: 4px solid #38b2ac;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .location-info .title {
            font-weight: 600;
            color: #234e52;
            margin-bottom: 5px;
        }
        .location-info .text {
            color: #2c7a7b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/mobile/dashboard" class="back-btn">←</a>
        <h1>⏰ Absensi</h1>
        <div style="font-size: 14px; opacity: 0.9;">
            {{ date('l, d F Y') }}
        </div>
    </div>

    <div class="container">
        <!-- Current Status -->
        <div class="status-card" id="statusCard">
            <div class="status-icon" id="statusIcon">🕐</div>
            <div class="status-text" id="statusText">Belum Clock In</div>
            <div class="status-time" id="statusTime">{{ date('H:i:s') }}</div>
            <button class="clock-btn" id="clockBtn" onclick="toggleClock()">
                🕐 CLOCK IN
            </button>
        </div>

        <!-- Location Info -->
        <div class="location-info">
            <div class="title">📍 Informasi Lokasi</div>
            <div class="text">
                Pastikan Anda berada di area rumah sakit untuk melakukan absensi.
                <br><small>GPS: Aktif - Lokasi terdeteksi</small>
            </div>
        </div>

        <!-- Today's Summary -->
        <div class="card">
            <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Ringkasan Hari Ini</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="text-align: center; padding: 15px; background: #f0fff4; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: bold; color: #38a169;">--:--</div>
                    <div style="font-size: 12px; color: #718096;">Clock In</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #fffdf0; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: bold; color: #d69e2e;">--:--</div>
                    <div style="font-size: 12px; color: #718096;">Clock Out</div>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="attendance-history">
            <div class="history-title">📋 Riwayat Absensi</div>
            
            <div class="history-item">
                <div>
                    <div class="history-date">Kemarin</div>
                    <div class="history-times">
                        In: 08:00 | Out: 17:00
                    </div>
                </div>
                <div class="history-status status-present">Hadir</div>
            </div>

            <div class="history-item">
                <div>
                    <div class="history-date">2 Hari Lalu</div>
                    <div class="history-times">
                        In: 08:15 | Out: 17:05
                    </div>
                </div>
                <div class="history-status status-late">Terlambat</div>
            </div>

            <div class="history-item">
                <div>
                    <div class="history-date">3 Hari Lalu</div>
                    <div class="history-times">
                        In: 07:55 | Out: 17:00
                    </div>
                </div>
                <div class="history-status status-present">Hadir</div>
            </div>

            <div style="text-align: center; margin-top: 15px;">
                <a href="#" style="color: #4299e1; text-decoration: none; font-size: 14px;">
                    📄 Lihat Semua Riwayat
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="/mobile/dashboard" class="nav-item">
            <span class="icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="/mobile/attendance" class="nav-item active">
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
        console.log('🏥 Hospital Attendance - Absensi Mobile');
        
        let isClockIn = false;
        
        function toggleClock() {
            const statusCard = document.getElementById('statusCard');
            const statusIcon = document.getElementById('statusIcon');
            const statusText = document.getElementById('statusText');
            const clockBtn = document.getElementById('clockBtn');
            
            clockBtn.disabled = true;
            clockBtn.innerHTML = '⏳ Memproses...';
            
            setTimeout(() => {
                if (!isClockIn) {
                    // Clock In
                    statusCard.className = 'status-card';
                    statusIcon.textContent = '✅';
                    statusText.textContent = 'Sudah Clock In';
                    clockBtn.innerHTML = '🕕 CLOCK OUT';
                    isClockIn = true;
                    
                    // Update summary
                    const clockInTime = document.querySelector('[style*="color: #38a169"]');
                    if (clockInTime) {
                        clockInTime.textContent = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
                    }
                } else {
                    // Clock Out
                    statusCard.className = 'status-card out';
                    statusIcon.textContent = '🏁';
                    statusText.textContent = 'Sudah Clock Out';
                    clockBtn.innerHTML = '✨ Selesai Hari Ini';
                    clockBtn.disabled = true;
                    
                    // Update summary
                    const clockOutTime = document.querySelector('[style*="color: #d69e2e"]');
                    if (clockOutTime) {
                        clockOutTime.textContent = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
                    }
                }
                
                if (isClockIn && clockBtn.innerHTML !== '✨ Selesai Hari Ini') {
                    clockBtn.disabled = false;
                }
                
            }, 1500);
        }

        // Update time every second
        setInterval(() => {
            const timeElement = document.getElementById('statusTime');
            if (timeElement) {
                timeElement.textContent = new Date().toLocaleTimeString('id-ID');
            }
        }, 1000);

        // Add touch feedback
        document.querySelectorAll('.nav-item').forEach(item => {
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