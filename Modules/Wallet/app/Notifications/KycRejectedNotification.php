<?php

namespace Modules\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Wallet\Models\KycVerification;

class KycRejectedNotification extends Notification
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
            ->subject('KYC Verification Requires Additional Information')
            ->line('Your KYC verification requires additional information.')
            ->line('Reason: ' . $this->verification->rejection_reason)
            ->line('Please submit the required documents to complete your verification.')
            ->action('Resubmit KYC', url('/account/kyc'))
            ->line('If you have any questions, please contact support.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'kyc_id' => $this->verification->id,
            'tier' => $this->verification->tier,
            'rejection_reason' => $this->verification->rejection_reason,
            'message' => 'KYC verification requires additional information'
        ];
    }
}
