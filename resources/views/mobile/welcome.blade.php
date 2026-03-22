<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Hospital Attendance') }} - Mobile</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }
        .container { max-width: 400px; margin: 20px auto; padding: 20px; }
        .card { 
            background: rgba(255,255,255,0.95); 
            border-radius: 20px; 
            padding: 40px 30px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .logo { 
            font-size: 64px; 
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .title { 
            color: #2d3748; 
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 40px;
            font-size: 16px;
        }
        .welcome-box { 
            background: linear-gradient(45deg, #48bb78, #38a169); 
            color: white; 
            padding: 25px; 
            border-radius: 15px; 
            margin: 25px 0;
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }
        .btn { 
            display: block; 
            background: linear-gradient(45deg, #4299e1, #3182ce); 
            color: white; 
            padding: 18px 25px; 
            text-decoration: none; 
            border-radius: 30px; 
            margin: 15px 0;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }
        .btn:hover, .btn:active { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(45deg, #ed8936, #dd6b20);
            box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }
        .info-card {
            background: #f7fafc;
            padding: 20px 15px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #4299e1;
        }
        .info-number {
            font-size: 24px;
            font-weight: bold;
            color: #4299e1;
        }
        .info-label {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #48bb78;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(72, 187, 120, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(72, 187, 120, 0); }
            100% { box-shadow: 0 0 0 0 rgba(72, 187, 120, 0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <img src="/logo.png" alt="Logo" class="logo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: contain; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px;">
            <h1 class="title">Hospital Attendance</h1>
            <p class="subtitle">Sistem Absensi Rumah Sakit</p>
            
            <div class="welcome-box">
                <strong>🎉 Selamat Datang!</strong><br>
                Aplikasi sistem absensi rumah sakit siap digunakan pada perangkat mobile Anda.
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-number">{{ date('H:i') }}</div>
                    <div class="info-label">Waktu Saat Ini</div>
                </div>
                <div class="info-card">
                    <div class="status-indicator"></div>
                    <div class="info-number">Online</div>
                    <div class="info-label">Status Server</div>
                </div>
            </div>

            <a href="/mobile/login" class="btn">
                🔑 Masuk ke Sistem
            </a>
            
            <a href="/register" class="btn btn-secondary">
                📝 Daftar Akun Baru  
            </a>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <p style="color: #718096; font-size: 14px;">
                    <strong>📱 Mode Mobile</strong><br>
                    Versi khusus mobile tanpa JavaScript complex<br>
                    <small>Server: {{ request()->getHost() }}:{{ request()->getPort() }}</small>
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>Hospital Attendance System v1.0</strong></p>
            <p>Mobile-Optimized Version</p>
            <p>{{ date('d F Y') }} - {{ date('H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Basic mobile interaction
        console.log('🏥 Hospital Attendance Mobile - Loaded successfully!');
        
        // Add click feedback for buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            btn.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Show loading feedback
        document.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', function(e) {
                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '⏳ Loading...';
                btn.style.opacity = '0.7';
                
                // Restore if navigation fails
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.opacity = '1';
                }, 5000);
            });
        });
    </script>
</body>
</html>