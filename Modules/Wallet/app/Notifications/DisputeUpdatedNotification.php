<?php

namespace Modules\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Wallet\Models\Dispute;

class DisputeUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Dispute $dispute, public string $updateType) {}

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
        $subject = "Dispute {$this->updateType}";
        $message = "Your dispute #{$this->dispute->id} has been {$this->updateType}.";

        if ($this->updateType === 'escalated') {
            $message .= " It has been marked as high priority and will be reviewed urgently.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->action('View Dispute', url('/account/disputes/' . $this->dispute->id))
            ->line('Thank you for your patience.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'update_type' => $this->updateType,
            'message' => "Dispute {$this->updateType}"
        ];
    }
}
