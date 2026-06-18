<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password DiabetAku</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #F7FAFC;
            color: #1F2937;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
            box-sizing: border-box;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #FFFFFF;
            border-radius: 14px;
            padding: 28px 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        .icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #EAF3FF;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            color: #1565C0;
            font-size: 36px;
        }

        h1 {
            margin: 0;
            text-align: center;
            color: #1565C0;
            font-size: 24px;
            font-weight: 700;
        }

        .desc {
            margin: 12px 0 24px;
            text-align: center;
            color: #4B5563;
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #DCE3EA;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
            margin-bottom: 16px;
            outline: none;
        }

        input:focus {
            border-color: #1565C0;
        }

        button {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 6px;
            background: #1565C0;
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #0F56A5;
        }

        .note {
            margin-top: 16px;
            text-align: center;
            color: #6B7280;
            font-size: 12px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="icon">🔒</div>

            <h1>Atur Ulang Kata Sandi</h1>

            <p class="desc">
                Masukkan kata sandi baru untuk akun DiabetAku Anda.
            </p>

            <form method="POST" action="/api/auth/reset-password">
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <label>Kata Sandi Baru</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Masukkan kata sandi baru"
                    required
                    minlength="8"
                >

                <label>Konfirmasi Kata Sandi</label>
                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="Konfirmasi kata sandi baru"
                    required
                    minlength="8"
                >

                <button type="submit">
                    Atur Ulang Kata Sandi
                </button>
            </form>

            <p class="note">
                Kata sandi minimal 8 karakter. Setelah berhasil, silakan login kembali melalui aplikasi DiabetAku.
            </p>
        </div>
    </div>
</body>
</html>
