<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal - {{ config('app.name', 'Hospital Attendance') }}</title>
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
        .week-nav {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .week-nav button {
            background: #4299e1;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
        }
        .week-title {
            font-weight: 600;
            color: #2d3748;
        }
        .schedule-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4299e1;
        }
        .schedule-card.today {
            border-left-color: #48bb78;
            background: #f0fff4;
        }
        .schedule-date {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .date-info {
            font-weight: 600;
            color: #2d3748;
        }
        .day-name {
            font-size: 14px;
            color: #718096;
        }
        .shift-info {
            background: #e6fffa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .shift-time {
            font-size: 18px;
            font-weight: bold;
            color: #2c7a7b;
            margin-bottom: 5px;
        }
        .shift-type {
            color: #4a5568;
            font-size: 14px;
        }
        .schedule-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-upcoming {
            background: #bee3f8;
            color: #2b6cb0;
        }
        .status-current {
            background: #c6f6d5;
            color: #2d7d32;
        }
        .status-completed {
            background: #e2e8f0;
            color: #4a5568;
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
    </style>
</head>
<body>
    <div class="header">
        <a href="/mobile/dashboard" class="back-btn">←</a>
        <h1>📅 Jadwal Kerja</h1>
        <div style="font-size: 14px; opacity: 0.9;">
            Minggu ini
        </div>
    </div>

    <div class="container">
        <!-- Week Navigation -->
        <div class="week-nav">
            <button onclick="previousWeek()">←</button>
            <div class="week-title" id="weekTitle">{{ date('d M') }} - {{ date('d M', strtotime('+6 days')) }}</div>
            <button onclick="nextWeek()">→</button>
        </div>

        <!-- Today's Schedule -->
        <div class="schedule-card today">
            <div class="schedule-date">
                <div>
                    <div class="date-info">Hari Ini - {{ date('d M Y') }}</div>
                    <div class="day-name">{{ date('l') }}</div>
                </div>
                <div class="schedule-status status-current">Sedang Berlangsung</div>
            </div>
            <div class="shift-info">
                <div class="shift-time">07:00 - 15:00</div>
                <div class="shift-type">Shift Pagi - Ruang IGD</div>
            </div>
        </div>

        <!-- Tomorrow's Schedule -->
        <div class="schedule-card">
            <div class="schedule-date">
                <div>
                    <div class="date-info">Besok - {{ date('d M Y', strtotime('+1 day')) }}</div>
                    <div class="day-name">{{ date('l', strtotime('+1 day')) }}</div>
                </div>
                <div class="schedule-status status-upcoming">Akan Datang</div>
            </div>
            <div class="shift-info">
                <div class="shift-time">15:00 - 23:00</div>
                <div class="shift-type">Shift Sore - Ruang ICU</div>
            </div>
        </div>

        <!-- Next Day -->
        <div class="schedule-card">
            <div class="schedule-date">
                <div>
                    <div class="date-info">{{ date('d M Y', strtotime('+2 days')) }}</div>
                    <div class="day-name">{{ date('l', strtotime('+2 days')) }}</div>
                </div>
                <div class="schedule-status status-upcoming">Akan Datang</div>
            </div>
            <div class="shift-info">
                <div class="shift-time">23:00 - 07:00</div>
                <div class="shift-type">Shift Malam - Ruang Emergency</div>
            </div>
        </div>

        <!-- Weekend -->
        <div class="schedule-card">
            <div class="schedule-date">
                <div>
                    <div class="date-info">{{ date('d M Y', strtotime('+3 days')) }}</div>
                    <div class="day-name">{{ date('l', strtotime('+3 days')) }}</div>
                </div>
                <div class="schedule-status status-upcoming">Libur</div>
            </div>
            <div style="text-align: center; padding: 20px; color: #718096;">
                📴 Hari Libur
            </div>
        </div>

        <!-- Summary -->
        <div style="background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Ringkasan Minggu Ini</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: #f0fff4; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: bold; color: #38a169;">5</div>
                    <div style="font-size: 12px; color: #718096;">Hari Kerja</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #fffdf0; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: bold; color: #d69e2e;">40</div>
                    <div style="font-size: 12px; color: #718096;">Jam Kerja</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f0f4ff; border-radius: 8px;">
                    <div style="font-size: 20px; font-weight: bold; color: #4299e1;">2</div>
                    <div style="font-size: 12px; color: #718096;">Hari Libur</div>
                </div>
            </div>
        </div>
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
        <a href="/mobile/schedule" class="nav-item active">
            <span class="icon">📅</span>
            <span>Jadwal</span>
        </a>
        <a href="/mobile/profile" class="nav-item">
            <span class="icon">👤</span>
            <span>Profile</span>
        </a>
    </div>

    <script>
        console.log('🏥 Hospital Attendance - Schedule Mobile');
        
        function previousWeek() {
            console.log('Previous week clicked');
            // Add functionality to load previous week
        }
        
        function nextWeek() {
            console.log('Next week clicked');
            // Add functionality to load next week
        }

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