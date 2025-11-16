<?php

namespace Modules\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Wallet\Models\Dispute;

class DisputeResolvedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Dispute $dispute) {}

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
            ->subject('Dispute Resolved')
            ->line('Your dispute has been resolved.')
            ->line('Resolution: ' . ucfirst(str_replace('_', ' ', $this->dispute->resolution)))
            ->line('Notes: ' . $this->dispute->notes)
            ->action('View Details', url('/account/disputes/' . $this->dispute->id))
            ->line('Thank you for your patience.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'resolution' => $this->dispute->resolution,
            'message' => 'Your dispute has been ' . $this->dispute->resolution
        ];
    }
}
