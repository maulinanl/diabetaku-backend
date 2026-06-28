<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password diabetAku</title>
</head>
<body style="margin:0; padding:0; background:#F8FBFF; font-family:Arial, sans-serif; color:#1F2937;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FBFF; padding:32px 16px;">
        <tr>
            <td align="center">

                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:560px; background:#FFFFFF; border-radius:18px; overflow:hidden; border:1px solid #DDE7F3;">

                    <tr>
                        <td style="background:#3A8DDE; padding:28px 32px; text-align:center;">
                            <h1 style="margin:0; color:#FFFFFF; font-size:28px;">
                                diabetAku
                            </h1>
                            <p style="margin:8px 0 0; color:#EAF4FF; font-size:14px;">
                                Reset Password Akun
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 14px; color:#1F2937; font-size:22px;">
                                Halo, {{ $name }}
                            </h2>

                            <p style="margin:0 0 16px; color:#4B5563; font-size:15px; line-height:1.7;">
                                Admin diabetAku telah mengirimkan permintaan untuk mengatur ulang kata sandi akun Anda.
                            </p>

                            <p style="margin:0 0 24px; color:#4B5563; font-size:15px; line-height:1.7;">
                                Silakan klik tombol di bawah ini untuk membuat kata sandi baru.
                            </p>

                            <div style="text-align:center; margin:30px 0;">
                                <a href="{{ $resetUrl }}"
                                    style="display:inline-block; background:#3A8DDE; color:#FFFFFF; text-decoration:none; padding:14px 28px; border-radius:12px; font-size:15px; font-weight:700;">
                                    Reset Password
                                </a>
                            </div>

                            <p style="margin:0 0 12px; color:#6B7280; font-size:13px; line-height:1.6;">
                                Jika tombol tidak dapat diklik, salin tautan berikut ke browser:
                            </p>

                            <p style="word-break:break-all; margin:0; color:#3A8DDE; font-size:13px; line-height:1.6;">
                                {{ $resetUrl }}
                            </p>

                            <div style="margin-top:28px; padding:14px; background:#EAF4FF; border-radius:12px;">
                                <p style="margin:0; color:#4B5563; font-size:13px; line-height:1.6;">
                                    Jika Anda tidak merasa meminta bantuan reset password, abaikan email ini.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#F5F8FC; padding:18px 32px; text-align:center;">
                            <p style="margin:0; color:#6B7280; font-size:12px;">
                                © {{ date('Y') }} diabetAku. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
