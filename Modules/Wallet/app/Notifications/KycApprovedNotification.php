<?php

namespace Modules\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Wallet\Models\KycVerification;

class KycApprovedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public KycVerification $verification) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KYC Verification Approved')
            ->line('Your KYC verification has been approved!')
            ->line('Tier: ' . $this->verification->tier)
            ->line('You can now enjoy higher transaction limits.')
            ->action('View Account', url('/account'))
            ->line('Thank you for completing the verification process.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'kyc_id' => $this->verification->id,
            'tier' => $this->verification->tier,
            'message' => 'Your KYC verification has been approved'
        ];
    }
}
