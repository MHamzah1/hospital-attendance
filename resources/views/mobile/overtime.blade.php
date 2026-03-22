<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembur - {{ config('app.name', 'Hospital Attendance') }}</title>
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
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .summary-title {
            color: #2d3748;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            color: #718096;
        }
        .hours { color: #4299e1; }
        .compensation { color: #38a169; }
        .requests { color: #d69e2e; }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            background: #f9f9f9;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #4299e1;
            background: white;
        }
        .form-textarea {
            height: 100px;
            resize: vertical;
        }
        .time-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #f6ad55, #ed8936);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        .overtime-history {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .history-item {
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
        }
        .history-item:last-child {
            margin-bottom: 0;
        }
        .history-date {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .history-time {
            color: #718096;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .history-reason {
            color: #4a5568;
            font-size: 14px;
            background: #f7fafc;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .history-compensation {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .compensation-amount {
            font-weight: 600;
            color: #38a169;
        }
        .compensation-hours {
            color: #4299e1;
            font-size: 14px;
        }
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background: #bee3f8;
            color: #2b6cb0;
        }
        .status-approved {
            background: #c6f6d5;
            color: #2d7d32;
        }
        .status-rejected {
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
        .tab-nav {
            display: flex;
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tab-btn {
            flex: 1;
            padding: 15px;
            background: #f7fafc;
            border: none;
            font-weight: 600;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #f6ad55, #ed8936);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .calc-display {
            background: #fffdf0;
            border: 2px solid #f6e05e;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
        }
        .calc-hours {
            font-size: 24px;
            font-weight: bold;
            color: #d69e2e;
            margin-bottom: 5px;
        }
        .calc-compensation {
            color: #38a169;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/mobile/dashboard" class="back-btn">←</a>
        <h1>⏰ Pengajuan Lembur</h1>
        <div style="font-size: 14px; opacity: 0.9;">
            Overtime Request
        </div>
    </div>

    <div class="container">
        <!-- Overtime Summary -->
        <div class="summary-card">
            <div class="summary-title">
                📊 Ringkasan Lembur Bulan Ini
            </div>
            <div class="summary-stats">
                <div class="stat-item">
                    <div class="stat-number hours">24</div>
                    <div class="stat-label">Jam Lembur</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number compensation">1.2M</div>
                    <div class="stat-label">Kompensasi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number requests">8</div>
                    <div class="stat-label">Pengajuan</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('request')">Ajukan Lembur</button>
            <button class="tab-btn" onclick="showTab('history')">Riwayat</button>
        </div>

        <!-- Request Form Tab -->
        <div id="request-tab" class="tab-content active">
            <div class="form-card">
                <form id="overtimeForm">
                    <div class="form-group">
                        <label class="form-label">Tanggal Lembur</label>
                        <input type="date" class="form-input" id="overtimeDate" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Waktu Lembur</label>
                        <div class="time-input-group">
                            <div>
                                <label style="font-size: 14px; margin-bottom: 5px; display: block;">Jam Mulai</label>
                                <input type="time" class="form-input" id="startTime" required>
                            </div>
                            <div>
                                <label style="font-size: 14px; margin-bottom: 5px; display: block;">Jam Selesai</label>
                                <input type="time" class="form-input" id="endTime" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jenis Lembur</label>
                        <select class="form-select" required>
                            <option value="">Pilih jenis lembur</option>
                            <option value="regular">Lembur Reguler (1.5x gaji)</option>
                            <option value="weekend">Lembur Weekend (2x gaji)</option>
                            <option value="holiday">Lembur Hari Libur (3x gaji)</option>
                            <option value="emergency">Lembur Darurat (2.5x gaji)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alasan Lembur</label>
                        <textarea class="form-textarea" placeholder="Jelaskan alasan dan jenis pekerjaan lembur..." required></textarea>
                    </div>

                    <!-- Automatic Calculation -->
                    <div class="calc-display" id="calculationDisplay" style="display: none;">
                        <div class="calc-hours" id="calcHours">0 Jam</div>
                        <div class="calc-compensation" id="calcCompensation">Estimasi: Rp 0</div>
                    </div>

                    <button type="submit" class="submit-btn">
                        📤 Ajukan Lembur
                    </button>
                </form>
            </div>
        </div>

        <!-- History Tab -->
        <div id="history-tab" class="tab-content">
            <div class="overtime-history">
                <div class="summary-title">
                    📅 Riwayat Lembur
                </div>

                <div class="history-item">
                    <div class="status-badge status-approved">Disetujui</div>
                    <div class="history-date">15 December 2024</div>
                    <div class="history-time">18:00 - 22:00 (4 jam)</div>
                    <div class="history-reason">
                        Lembur reguler - Penanganan pasien emergency dan dokumentasi
                    </div>
                    <div class="history-compensation">
                        <div class="compensation-amount">Rp 200,000</div>
                        <div class="compensation-hours">4 jam × 1.5x</div>
                    </div>
                </div>

                <div class="history-item">
                    <div class="status-badge status-pending">Pending</div>
                    <div class="history-date">18 December 2024</div>
                    <div class="history-time">16:00 - 20:00 (4 jam)</div>
                    <div class="history-reason">
                        Lembur weekend - Shift tambahan karena kekurangan staff
                    </div>
                    <div class="history-compensation">
                        <div class="compensation-amount">Rp 400,000</div>
                        <div class="compensation-hours">4 jam × 2x</div>
                    </div>
                </div>

                <div class="history-item">
                    <div class="status-badge status-approved">Disetujui</div>
                    <div class="history-date">12 December 2024</div>
                    <div class="history-time">20:00 - 00:00 (4 jam)</div>
                    <div class="history-reason">
                        Lembur darurat - Penanganan kasus critical di ICU
                    </div>
                    <div class="history-compensation">
                        <div class="compensation-amount">Rp 500,000</div>
                        <div class="compensation-hours">4 jam × 2.5x</div>
                    </div>
                </div>

                <div class="history-item">
                    <div class="status-badge status-rejected">Ditolak</div>
                    <div class="history-date">10 December 2024</div>
                    <div class="history-time">17:00 - 21:00 (4 jam)</div>
                    <div class="history-reason">
                        Lembur reguler - tidak sesuai dengan SOP lembur
                    </div>
                    <div class="history-compensation">
                        <div class="compensation-amount">-</div>
                        <div class="compensation-hours">Ditolak</div>
                    </div>
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
        console.log('🏥 Hospital Attendance - Overtime Mobile');
        
        // Base hourly rate (in rupiah)
        const BASE_HOURLY_RATE = 50000;
        
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Mark button as active
            event.target.classList.add('active');
        }
        
        function calculateOvertime() {
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            const overtimeType = document.querySelector('.form-select').value;
            
            if (!startTime || !endTime || !overtimeType) {
                document.getElementById('calculationDisplay').style.display = 'none';
                return;
            }
            
            // Calculate hours
            const start = new Date('2024-01-01 ' + startTime);
            const end = new Date('2024-01-01 ' + endTime);
            let hours = (end - start) / (1000 * 60 * 60);
            
            if (hours <= 0) {
                hours = 24 + hours; // Handle overnight shifts
            }
            
            // Calculate multiplier based on overtime type
            let multiplier = 1;
            switch(overtimeType) {
                case 'regular': multiplier = 1.5; break;
                case 'weekend': multiplier = 2; break;
                case 'holiday': multiplier = 3; break;
                case 'emergency': multiplier = 2.5; break;
            }
            
            const compensation = hours * BASE_HOURLY_RATE * multiplier;
            
            // Update display
            document.getElementById('calcHours').textContent = hours.toFixed(1) + ' Jam';
            document.getElementById('calcCompensation').textContent = 
                'Estimasi: Rp ' + compensation.toLocaleString('id-ID');
            document.getElementById('calculationDisplay').style.display = 'block';
        }
        
        // Add event listeners for automatic calculation
        document.getElementById('startTime').addEventListener('change', calculateOvertime);
        document.getElementById('endTime').addEventListener('change', calculateOvertime);
        document.querySelector('.form-select').addEventListener('change', calculateOvertime);
        
        // Set default date to today
        document.getElementById('overtimeDate').value = new Date().toISOString().split('T')[0];
        
        document.getElementById('overtimeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple validation
            const date = this.querySelector('#overtimeDate').value;
            const startTime = this.querySelector('#startTime').value;
            const endTime = this.querySelector('#endTime').value;
            const reason = this.querySelector('textarea').value;
            
            if (!date || !startTime || !endTime || !reason.trim()) {
                alert('⚠️ Mohon lengkapi semua field yang diperlukan!');
                return;
            }
            
            // Calculate hours
            const start = new Date('2024-01-01 ' + startTime);
            const end = new Date('2024-01-01 ' + endTime);
            let hours = (end - start) / (1000 * 60 * 60);
            
            if (hours <= 0) hours = 24 + hours;
            
            if (hours < 1) {
                alert('⚠️ Minimal lembur adalah 1 jam!');
                return;
            }
            
            if (hours > 12) {
                alert('⚠️ Maksimal lembur adalah 12 jam per hari!');
                return;
            }
            
            // Simulate form submission
            this.querySelector('.submit-btn').innerHTML = '⏳ Mengirim...';
            this.querySelector('.submit-btn').disabled = true;
            
            setTimeout(() => {
                alert('✅ Pengajuan lembur berhasil dikirim! Menunggu persetujuan supervisor.');
                this.reset();
                document.getElementById('calculationDisplay').style.display = 'none';
                this.querySelector('.submit-btn').innerHTML = '📤 Ajukan Lembur';
                this.querySelector('.submit-btn').disabled = false;
                
                // Reset default date
                document.getElementById('overtimeDate').value = new Date().toISOString().split('T')[0];
            }, 2000);
        });

        // Add touch feedback
        document.querySelectorAll('.nav-item, .tab-btn').forEach(item => {
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