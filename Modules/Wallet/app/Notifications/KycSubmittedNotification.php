<?php

namespace Modules\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Wallet\Models\KycVerification;

class KycSubmittedNotification extends Notification
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
            ->subject('New KYC Verification Submitted')
            ->line('A new KYC verification has been submitted by ' . $this->verification->user->name)
            ->line('Tier: ' . $this->verification->tier)
            ->action('Review KYC', url('/admin/kyc/' . $this->verification->id))
            ->line('Please review the submitted documents.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'kyc_id' => $this->verification->id,
            'user_id' => $this->verification->user_id,
            'tier' => $this->verification->tier,
            'message' => 'New KYC verification submitted by ' . $this->verification->user->name
        ];
    }
}
