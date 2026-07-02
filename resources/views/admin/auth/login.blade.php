<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin diabetAku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3A86D1;
            --primary-dark: #3F73B7;
            --primary-soft: #EFF6FF;
            --primary-soft-2: #DCEEFF;
            --bg: #F8FBFF;
            --white: #FFFFFF;
            --dark: #3A3A3C;
            --dark-2: #6B7588;
            --line: #DDE5E9;
            --red: #FF3B3B;
            --red-soft: #FEF2F2;
            --shadow-soft: 0 18px 46px rgba(58, 134, 209, .14);
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(220, 238, 255, .92) 0, rgba(248, 251, 255, 0) 360px),
                var(--bg);
            color: var(--dark);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 40% 60%;
            overflow: hidden;
        }

        .left-panel {
            position: relative;
            background: var(--primary);
            border-top-right-radius: 86px;
            border-bottom-right-radius: 86px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 26px;
            padding: 42px;
            color: white;
        }

        .left-panel::before {
            content: "";
            position: absolute;
            top: -64px;
            right: -64px;
            width: 190px;
            height: 190px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
        }

        .left-panel::after {
            content: "";
            position: absolute;
            bottom: -90px;
            left: -90px;
            width: 230px;
            height: 230px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .10);
        }

        .left-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .brand-logo img {
            height: 78px;
            object-fit: contain;
        }

        .brand-fallback {
            display: none;
            font-size: 42px;
            font-weight: 800;
            margin: 0;
        }

        .left-title {
            font-size: 36px;
            line-height: 1.25;
            font-weight: 800;
            margin: 28px 0 12px;
        }

        .left-desc {
            max-width: 430px;
            margin: 0 auto;
            font-size: 15px;
            line-height: 1.8;
            opacity: .95;
            font-weight: 500;
        }

        .illustration {
            position: relative;
            z-index: 2;
            width: 92%;
            max-width: 520px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 18px 34px rgba(0, 0, 0, .10));
        }

        .right-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, .94);
            padding: 42px 38px;
            border-radius: 28px;
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }

        .title {
            margin-bottom: 28px;
        }

        .title h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.3;
            color: var(--dark);
            font-weight: 800;
        }

        .title h1 span {
            color: var(--primary);
        }

        .title p {
            margin: 12px 0 0;
            color: var(--dark-2);
            font-size: 14px;
            line-height: 1.6;
            font-weight: 500;
        }

        .error {
            background: var(--red-soft);
            color: var(--red);
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255, 59, 59, .14);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-2);
            font-size: 14px;
            font-weight: 700;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 19px;
            height: 19px;
            color: var(--primary);
        }

        input {
            width: 100%;
            height: 52px;
            padding: 0 14px 0 48px;
            border: 1px solid var(--line);
            border-radius: 14px;
            font-size: 14px;
            outline: none;
            transition: .18s ease;
            background: white;
            color: var(--dark);
        }

        input::placeholder {
            color: #AAB8D1;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(58, 134, 209, .10);
            background: var(--white);
        }

        .password-wrapper input {
            padding-right: 64px;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--primary);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            padding: 4px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 4px 0 24px;
            color: var(--dark-2);
            font-size: 13px;
            font-weight: 500;
        }

        .remember-row input {
            width: 18px;
            height: 18px;
            padding: 0;
            margin: 0;
            accent-color: var(--primary);
        }

        .login-button {
            width: 100%;
            height: 52px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 800;
            font-size: 15px;
            transition: .18s ease;
            box-shadow: 0 12px 24px rgba(58, 134, 209, .22);
        }

        .login-button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .login-button:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        .info {
            margin-top: 20px;
            padding: 14px;
            border-radius: 16px;
            background: var(--primary-soft);
            color: var(--dark-2);
            font-size: 12px;
            text-align: center;
            line-height: 1.5;
            font-weight: 500;
        }

        .copyright {
            position: absolute;
            bottom: 24px;
            color: var(--dark-2);
            font-size: 12px;
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .left-panel {
                min-height: 260px;
                border-radius: 0 0 48px 48px;
                padding: 36px 28px;
            }

            .brand-logo {
                display: flex;
                justify-content: center;
                margin-bottom: 10px;
            }

            .brand-logo img {
                height: 62px;
            }

            .left-title {
                font-size: 28px;
                margin-top: 16px;
            }

            .left-desc {
                font-size: 13px;
            }

            .illustration {
                display: none;
            }

            .right-panel {
                padding: 28px 18px 70px;
            }

            .login-card {
                padding: 30px 22px;
                border-radius: 24px;
            }

            .title h1 {
                font-size: 26px;
            }

            .copyright {
                bottom: 20px;
            }
        }
    </style>

</head>

<body>

    <div class="login-page">

        <section class="left-panel">
            <div class="left-content">
                <div class="brand-logo">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Logo diabetAku"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">

                    <h1 class="brand-fallback">diabetAku</h1>
                </div>

                <div class="left-title">
                    Admin Dashboard
                </div>

                <div class="left-desc">
                    Sistem untuk memantau pengguna, memverifikasi dokter,
                    dan mengelola data master aplikasi diabetAku.
                </div>
            </div>

            <img
                src="{{ asset('assets/images/login-illustration.png') }}"
                alt="Ilustrasi Login"
                class="illustration">
        </section>

        <section class="right-panel">
            <div class="login-card">

                <div class="title">
                    <h1>
                        <span>Selamat Datang</span><br>
                        Admin Dashboard
                    </h1>
                    <p>
                        Masuk menggunakan akun administrator untuk mengelola sistem diabetAku.
                    </p>
                </div>

                @if(session('error'))
                    <div class="error">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="/admin/login" id="loginForm">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email Admin</label>

                        <div class="input-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6h16v12H4z"></path>
                                <path d="M4 7l8 6 8-6"></path>
                            </svg>

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Masukkan email admin"
                                autocomplete="email"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <div class="input-wrapper password-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 018 0v3"></path>
                            </svg>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required>

                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                Lihat
                            </button>
                        </div>
                    </div>

                    <label class="remember-row">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>

                    <button type="submit" class="login-button" id="loginButton">
                        Masuk
                    </button>
                </form>

                <div class="info">
                    Halaman ini hanya dapat diakses oleh administrator sistem.
                </div>

            </div>

            <div class="copyright">
                © 2026 diabetAku. All rights reserved.
            </div>
        </section>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = 'Sembunyi';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = 'Lihat';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function () {
            const button = document.getElementById('loginButton');
            button.disabled = true;
            button.textContent = 'Memproses...';
        });
    </script>

</body>

</html>
