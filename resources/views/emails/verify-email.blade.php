@php
    $primary = '#3A86D1';
    $background = '#F8FBFF';
    $lightBlue = '#DCEEFF';
    $veryLightBlue = '#EFF6FF';
    $dark = '#3A3A3C';
    $muted = '#6B7588';
    $soft = '#8F90A6';
    $border = '#DDE5E9';
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email DiabetAku</title>
</head>

<body
    style="margin:0; padding:0; background:{{ $background }}; font-family:'Plus Jakarta Sans', Arial, Helvetica, sans-serif; color:{{ $dark }};">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        Verifikasi email untuk mengaktifkan akun DiabetAku kamu.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="width:100%; background:{{ $background }}; border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%; max-width:440px; border-collapse:collapse;">
                    <tr>
                        <td align="center" style="padding:0 0 22px 0;">
                            <img src="{{ config('app.url') }}/assets/images/logo.png" width="190" alt="DiabetAku"
                                style="display:block; width:190px; max-width:72%; height:auto; border:0; outline:none; text-decoration:none;">
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="background:#FFFFFF; border:1px solid {{ $border }}; border-radius:14px; padding:26px 24px 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border-collapse:collapse;">
                                <tr>
                                    <td align="left" style="padding:0 0 6px 0;">
                                        <p
                                            style="margin:0; color:{{ $primary }}; font-size:13px; line-height:18px; font-weight:700;">
                                            Verifikasi Email
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding:0 0 18px 0;">
                                        <h1
                                            style="margin:0; color:{{ $dark }}; font-size:22px; line-height:30px; font-weight:700;">
                                            Hai, {{ $name }}
                                        </h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding:0 0 18px 0;">
                                        <p
                                            style="margin:0; color:{{ $muted }}; font-size:14px; line-height:22px; font-weight:400;">
                                            Terima kasih sudah mendaftar di DiabetAku. Tekan tombol di bawah ini untuk
                                            mengaktifkan email akun kamu.
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 0 20px 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0"
                                            style="border-collapse:collapse; background:#FFFFFF; border:1px solid {{ $border }}; border-radius:6px;">
                                            <tr>
                                                <td style="padding:14px 16px;">
                                                    <p
                                                        style="margin:0 0 5px 0; color:{{ $muted }}; font-size:12px; line-height:17px; font-weight:500;">
                                                        Email terdaftar
                                                    </p>
                                                    <p
                                                        style="margin:0; color:{{ $dark }}; font-size:14px; line-height:20px; font-weight:600; word-break:break-word;">
                                                        {{ $email }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="center" style="padding:0 0 18px 0;">
                                        <a href="{{ $verificationUrl }}"
                                            style="display:block; width:100%; box-sizing:border-box; background:{{ $primary }}; color:#FFFFFF; border-radius:6px; padding:15px 18px; text-align:center; text-decoration:none; font-size:14px; line-height:20px; font-weight:600;">
                                            Verifikasi Email
                                        </a>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 0 18px 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                            border="0"
                                            style="border-collapse:collapse; background:{{ $veryLightBlue }}; border:1px solid {{ $lightBlue }}; border-radius:8px;">
                                            <tr>
                                                <td style="padding:12px 14px;">
                                                    <p
                                                        style="margin:0; color:{{ $primary }}; font-size:12px; line-height:18px; font-weight:600;">
                                                        Link berlaku selama {{ $expireMinutes }} menit.
                                                    </p>
                                                    @if ($isDoctor)
                                                        <p
                                                            style="margin:5px 0 0 0; color:{{ $primary }}; font-size:11px; line-height:17px; font-weight:400;">
                                                            Setelah email terverifikasi, akun dokter akan menunggu
                                                            verifikasi admin.
                                                        </p>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td align="left" style="padding:0;">
                                        <p
                                            style="margin:0; color:{{ $soft }}; font-size:11px; line-height:18px; font-weight:400;">
                                            Jika kamu tidak merasa membuat akun DiabetAku, abaikan email ini.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:16px 10px 0 10px;">
                            <p
                                style="margin:0; color:{{ $soft }}; font-size:11px; line-height:17px; font-weight:400;">
                                © {{ date('Y') }} DiabetAku
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
