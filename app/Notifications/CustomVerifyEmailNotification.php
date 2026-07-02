<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $name = $notifiable->full_name ?: 'Pengguna DiabetAku';
        $isDoctor = (int) ($notifiable->role_id ?? 0) === 2;

        return (new MailMessage)
            ->subject('Verifikasi Email Akun DiabetAku')
            ->view('emails.verify-email', [
                'name' => $name,
                'email' => $notifiable->getEmailForVerification(),
                'verificationUrl' => $verificationUrl,
                'expireMinutes' => Config::get('auth.verification.expire', 60),
                'isDoctor' => $isDoctor,
            ]);
    }

    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
