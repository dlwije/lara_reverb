<?php

namespace Modules\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Wallet\Models\Dispute;

class DisputeCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Dispute $dispute){}

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
            ->subject('New Dispute Created')
            ->line('A new dispute has been created by ' . $this->dispute->user->name)
            ->line('Reason: ' . $this->dispute->reason)
            ->action('View Dispute', url('/admin/disputes/' . $this->dispute->id))
            ->line('Please review and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'user_id' => $this->dispute->user_id,
            'reason' => $this->dispute->reason,
            'message' => 'New dispute created by ' . $this->dispute->user->name
        ];
    }
}
