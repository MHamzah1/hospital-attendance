<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'Hospital Attendance') }}</title>
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
            backdrop-filter: blur(10px);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo { 
            font-size: 48px; 
            margin-bottom: 15px;
        }
        .title { 
            color: #2d3748; 
            margin-bottom: 5px;
            font-size: 24px;
            font-weight: 700;
        }
        .subtitle {
            color: #718096;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
            background: white;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        .btn {
            width: 100%;
            background: linear-gradient(45deg, #4299e1, #3182ce); 
            color: white; 
            padding: 18px 25px; 
            text-decoration: none; 
            border-radius: 12px; 
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }
        .btn:hover, .btn:active { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .back-btn {
            display: inline-block;
            color: #4299e1;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 8px 0;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .remember-me input {
            margin-right: 8px;
        }
        .remember-me label {
            font-size: 14px;
            color: #718096;
        }
        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #4299e1;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
            color: #718096;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/mobile" class="back-btn">← Kembali ke Beranda</a>
        
        <div class="card">
            <div class="header">
                <img src="/logo.png" alt="Logo" class="logo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: contain; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px;">
                <h1 class="title">Masuk Sistem</h1>
                <p class="subtitle">Hospital Attendance - Mobile</p>
            </div>

            <form id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                
                @if ($errors->any())
                    <div class="error-message">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="form-group">
                    <label for="email" class="form-label">📧 Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="user@hospital.com"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">🔒 Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    🔑 Masuk ke Sistem
                </button>

                <div class="loading" id="loadingText">
                    ⏳ Memproses login...
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-password">
                        🔄 Lupa Password?
                    </a>
                @endif
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
                <p style="color: #718096; font-size: 12px;">
                    Belum punya akun? <a href="/register" style="color: #4299e1;">Daftar di sini</a><br>
                    <small>Version Mobile - No JavaScript Complex</small>
                </p>
            </div>
        </div>
    </div>

    <script>
        console.log('🏥 Hospital Attendance Login - Mobile Version');
        
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const loading = document.getElementById('loadingText');
        
        form.addEventListener('submit', function(e) {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Memproses...';
            loading.style.display = 'block';
            
            // Add some basic validation feedback
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            if (!email.value || !password.value) {
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '🔑 Masuk ke Sistem';
                loading.style.display = 'none';
                alert('Mohon lengkapi email dan password');
                return;
            }
            
            // Let form submit normally
            console.log('Login form submitted');
        });

        // Add touch feedback for mobile
        submitBtn.addEventListener('touchstart', function() {
            if (!this.disabled) {
                this.style.transform = 'scale(0.98)';
            }
        });
        
        submitBtn.addEventListener('touchend', function() {
            if (!this.disabled) {
                this.style.transform = 'scale(1)';
            }
        });
    </script>
</body>
</html>