<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Cuti - {{ config('app.name', 'Hospital Attendance') }}</title>
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
        .available { color: #38a169; }
        .used { color: #d69e2e; }
        .pending { color: #4299e1; }
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
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #4299e1, #667eea);
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
        .leave-history {
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
        .history-dates {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .history-type {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .history-reason {
            color: #4a5568;
            font-size: 14px;
            background: #f7fafc;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/mobile/dashboard" class="back-btn">←</a>
        <h1>🇾🇪 Pengajuan Cuti</h1>
        <div style="font-size: 14px; opacity: 0.9;">
            Leave Request
        </div>
    </div>

    <div class="container">
        <!-- Leave Summary -->
        <div class="summary-card">
            <div class="summary-title">
                📊 Ringkasan Cuti Tahunan
            </div>
            <div class="summary-stats">
                <div class="stat-item">
                    <div class="stat-number available">8</div>
                    <div class="stat-label">Tersisa</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number used">4</div>
                    <div class="stat-label">Terpakai</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number pending">2</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('request')">Ajukan Cuti</button>
            <button class="tab-btn" onclick="showTab('history')">Riwayat</button>
        </div>

        <!-- Request Form Tab -->
        <div id="request-tab" class="tab-content active">
            <div class="form-card">
                <form id="leaveForm">
                    <div class="form-group">
                        <label class="form-label">Jenis Cuti</label>
                        <select class="form-select" required>
                            <option value="">Pilih jenis cuti</option>
                            <option value="annual">Cuti Tahunan</option>
                            <option value="sick">Cuti Sakit</option>
                            <option value="emergency">Cuti Darurat</option>
                            <option value="maternity">Cuti Melahirkan</option>
                            <option value="marriage">Cuti Menikah</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alasan Cuti</label>
                        <textarea class="form-textarea" placeholder="Jelaskan alasan pengajuan cuti..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pengganti Selama Cuti</label>
                        <select class="form-select" required>
                            <option value="">Pilih pengganti</option>
                            <option value="nurse1">Sari - Perawat Senior</option>
                            <option value="nurse2">Dewi - Perawat IGD</option>
                            <option value="nurse3">Andi - Perawat ICU</option>
                        </select>
                    </div>

                    <button type="submit" class="submit-btn">
                        📤 Ajukan Cuti
                    </button>
                </form>
            </div>
        </div>

        <!-- History Tab -->
        <div id="history-tab" class="tab-content">
            <div class="leave-history">
                <div class="summary-title">
                    📅 Riwayat Pengajuan Cuti
                </div>

                <div class="history-item">
                    <div class="status-badge status-approved">Disetujui</div>
                    <div class="history-dates">15 Dec 2024 - 17 Dec 2024</div>
                    <div class="history-type">Cuti Tahunan (3 hari)</div>
                    <div class="history-reason">
                        Liburan keluarga dan istirahat
                    </div>
                </div>

                <div class="history-item">
                    <div class="status-badge status-pending">Pending</div>
                    <div class="history-dates">20 Dec 2024 - 21 Dec 2024</div>
                    <div class="history-type">Cuti Sakit (2 hari)</div>
                    <div class="history-reason">
                        Demam tinggi dan perlu istirahat
                    </div>
                </div>

                <div class="history-item">
                    <div class="status-badge status-approved">Disetujui</div>
                    <div class="history-dates">10 Nov 2024 - 10 Nov 2024</div>
                    <div class="history-type">Cuti Darurat (1 hari)</div>
                    <div class="history-reason">
                        Keperluan keluarga mendadak
                    </div>
                </div>

                <div class="history-item">
                    <div class="status-badge status-rejected">Ditolak</div>
                    <div class="history-dates">25 Oct 2024 - 27 Oct 2024</div>
                    <div class="history-type">Cuti Tahunan (3 hari)</div>
                    <div class="history-reason">
                        Libur panjang - jadwal penuh di rumah sakit
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
        console.log('🏥 Hospital Attendance - Leave Request Mobile');
        
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
        
        document.getElementById('leaveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple validation
            const startDate = this.querySelector('input[type="date"]:first-of-type').value;
            const endDate = this.querySelector('input[type="date"]:nth-of-type(2)').value;
            const reason = this.querySelector('textarea').value;
            
            if (!startDate || !endDate || !reason.trim()) {
                alert('⚠️ Mohon lengkapi semua field yang diperlukan!');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('⚠️ Tanggal mulai tidak boleh setelah tanggal selesai!');
                return;
            }
            
            // Simulate form submission
            this.querySelector('.submit-btn').innerHTML = '⏳ Mengirim...';
            this.querySelector('.submit-btn').disabled = true;
            
            setTimeout(() => {
                alert('✅ Pengajuan cuti berhasil dikirim! Menunggu persetujuan supervisor.');
                this.reset();
                this.querySelector('.submit-btn').innerHTML = '📤 Ajukan Cuti';
                this.querySelector('.submit-btn').disabled = false;
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