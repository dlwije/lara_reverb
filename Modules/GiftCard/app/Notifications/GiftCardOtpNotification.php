<?php

namespace Modules\GiftCard\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class GiftCardOtpNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $otp, public string $action) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Gift Card Redemption OTP')
            ->line('You are attempting to redeem a gift card.')
            ->line('Your OTP code is: ' . $this->otp)
            ->line('This OTP is valid for 10 minutes.')
            ->line('If you did not request this, please contact support immediately.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'otp' => $this->otp,
            'action' => $this->action,
            'message' => 'OTP sent for gift card redemption'
        ];
    }
}
