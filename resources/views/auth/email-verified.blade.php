<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Verifikasi Email' }} - DiabetAku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --background: #F8FBFF;
            --primary-blue: #3A86D1;
            --light-blue: #DCEEFF;
            --very-light-blue: #EFF6FF;
            --red: #FF3B3B;
            --light-red: #FEF2F2;
            --dark-1: #3A3A3C;
            --dark-2: #6B7588;
            --dark-3: #8F90A6;
            --light-1: #DDE5E9;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background: var(--background);
            color: var(--dark-1);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .page {
            width: 100%;
            max-width: 410px;
        }

        .logo {
            display: block;
            width: 190px;
            max-width: 72%;
            height: auto;
            margin: 0 auto 24px;
        }

        .card {
            width: 100%;
            background: var(--white);
            border: 1px solid var(--light-1);
            border-radius: 18px;
            padding: 26px 22px 24px;
            box-shadow: 0 12px 28px rgba(58, 134, 209, 0.10);
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--primary-blue);
            font-size: 13px;
            line-height: 18px;
            font-weight: 700;
        }

        h1 {
            margin: 0;
            color: var(--dark-1);
            font-size: 22px;
            line-height: 1.35;
            font-weight: 800;
            letter-spacing: -0.2px;
        }

        .message {
            margin: 12px 0 0;
            color: var(--dark-2);
            font-size: 14px;
            line-height: 1.65;
            font-weight: 500;
        }

        .status-box {
            margin-top: 18px;
            padding: 14px 15px;
            border-radius: 10px;
            background: var(--very-light-blue);
            border: 1px solid var(--light-blue);
        }

        .status-box.error {
            background: var(--light-red);
            border-color: rgba(255, 59, 59, 0.20);
        }

        .status-title {
            margin: 0 0 5px;
            color: var(--primary-blue);
            font-size: 12px;
            line-height: 17px;
            font-weight: 700;
        }

        .status-box.error .status-title {
            color: var(--red);
        }

        .status-text {
            margin: 0;
            color: var(--dark-1);
            font-size: 12px;
            line-height: 1.55;
            font-weight: 500;
        }

        .open-app-button {
            display: flex;
            width: 100%;
            height: 50px;
            margin-top: 20px;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: var(--primary-blue);
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            box-shadow: none;
        }

        .open-app-button:active {
            transform: translateY(1px);
        }

        .manual-note {
            margin: 12px 0 0;
            color: var(--dark-3);
            font-size: 11px;
            line-height: 1.55;
            text-align: center;
        }

        .footer {
            margin-top: 16px;
            text-align: center;
            color: var(--dark-3);
            font-size: 11px;
            line-height: 1.5;
        }

        @media (max-width: 420px) {
            body {
                padding: 18px;
                align-items: flex-start;
            }

            .page {
                padding-top: 34px;
            }

            .card {
                border-radius: 16px;
                padding: 24px 20px 22px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    @php
        $status = $status ?? 'success';
        $isError = $status === 'error';
        $appUrl = $appUrl ?? config('app.mobile_deeplink', env('APP_DEEPLINK_URL', 'diabetaku://login'));
        $showOpenAppButton = $showOpenAppButton ?? true;
    @endphp

    <main class="page">
        <img class="logo" src="{{ asset('assets/images/logo.png') }}" alt="DiabetAku">

        <section class="card">
            <p class="eyebrow">Verifikasi Email</p>

            <h1>{{ $title ?? 'Email berhasil diverifikasi' }}</h1>

            <p class="message">
                {{ $message ?? 'Email kamu sudah berhasil diverifikasi. Silakan lanjut masuk ke aplikasi DiabetAku.' }}
            </p>

            @if (($showInstruction ?? true) === true)
                <div class="status-box {{ $isError ? 'error' : '' }}">
                    <p class="status-title">
                        {{ $isError ? 'Perlu dicoba lagi' : 'Langkah selanjutnya' }}
                    </p>
                    <p class="status-text">
                        {{ $instruction ?? 'Buka aplikasi DiabetAku, lalu login menggunakan email dan password yang sudah kamu daftarkan.' }}
                    </p>
                </div>
            @endif

            @if ($showOpenAppButton)
                <a class="open-app-button" href="{{ $appUrl }}">
                    Buka Aplikasi DiabetAku
                </a>

                <p class="manual-note">
                    Kalau tombol tidak membuka aplikasi, buka DiabetAku secara manual dari perangkat kamu.
                </p>
            @endif
        </section>

        <p class="footer">
            © {{ date('Y') }} DiabetAku
        </p>
    </main>
</body>

</html>
