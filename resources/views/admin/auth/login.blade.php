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
            --primary: #3A8DDE;
            --primary-dark: #2476C7;
            --bg: #F8FBFF;
            --white: #FFFFFF;
            --dark: #1F2937;
            --gray: #6B7280;
            --light: #DDE7F3;
            --soft-blue: #EAF4FF;
            --red: #EF4444;
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #EAF4FF 0, #F8FBFF 34%, #F8FBFF 100%);
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

            padding: 40px;
            color: white;
        }

        .left-content {
            z-index: 2;
            text-align: center;
            margin-bottom: 20px;
        }

        .brand-logo img {
            height: 75px;
            object-fit: contain;
        }

        .brand-fallback {
            display: none;
            font-size: 42px;
            font-weight: 800;
            margin: 0;
        }

        .left-title {
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .left-desc {
            max-width: 420px;
            margin: 0 auto;
            font-size: 15px;
            line-height: 1.8;
            opacity: .95;
        }

        .illustration {
            width: 95%;
            max-width: 550px;
            height: auto;
            object-fit: contain;
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
            background: rgba(255, 255, 255, .92);
            padding: 42px 38px;
            border-radius: 28px;
            box-shadow: 0 22px 56px rgba(58, 141, 222, .16);
            border: 1px solid rgba(58, 141, 222, .12);
        }

        .title {
            margin-bottom: 28px;
        }

        .title h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
            color: var(--dark);
        }

        .title h1 span {
            color: var(--primary);
        }

        .title p {
            margin: 12px 0 0;
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
        }

        .error {
            background: #FFEAEA;
            color: var(--red);
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-dark);
            font-size: 13px;
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
            height: 50px;
            padding: 0 14px 0 48px;
            border: 1.5px solid rgba(58, 141, 222, .45);
            border-radius: 14px;
            font-size: 14px;
            outline: none;
            transition: .2s;
            background: #F8FBFF;
            color: var(--dark);
        }

        input::placeholder {
            color: #AAB8D1;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(58, 141, 222, .12);
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
            font-weight: 700;
            cursor: pointer;
            padding: 4px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 4px 0 24px;
            color: var(--dark);
            font-size: 13px;
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
            height: 50px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: .2s;
        }

        .login-button:hover {
            background: var(--primary-dark);
        }

        .login-button:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        .info {
            margin-top: 20px;
            padding: 13px;
            border-radius: 14px;
            background: var(--soft-blue);
            color: var(--gray);
            font-size: 12px;
            text-align: center;
            line-height: 1.5;
        }

        .copyright {
            position: absolute;
            bottom: 24px;
            color: var(--gray);
            font-size: 12px;
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
                margin-bottom: 18px;
            }

            .left-title {
                font-size: 22px;
            }

            .left-desc {
                font-size: 13px;
            }

            .illustration {
                opacity: .55;
                height: 70%;
            }

            .right-panel {
                padding: 28px 20px 56px;
            }

            .login-card {
                max-width: 100%;
                padding: 30px 24px;
                border-radius: 26px;
            }

            .title h1 {
                font-size: 26px;
            }

            .copyright {
                bottom: 18px;
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
