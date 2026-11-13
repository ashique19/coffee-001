<?php

namespace App\Notifications;

use App\Models\BusinessSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.password.reset', [
            'tenant' => $this->tenant,
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject(__('auth.reset_title'))
            ->line(__('auth.forgot_lead'))
            ->action(__('auth.reset_password'), $url)
            ->line(__('auth.token_invalid'));
    }
}
