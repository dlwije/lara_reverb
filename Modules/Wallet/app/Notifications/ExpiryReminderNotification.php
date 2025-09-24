<?php

namespace Botble\Wallet\Events;

use Botble\Wallet\Models\WalletLot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class ExpiryReminderNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public WalletLot $lot, public int $daysUntilExpiry){}


    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = number_format($this->lot->remaining, 2);

        return (new MailMessage)
            ->subject('Wallet Funds Expiring Soon!')
            ->line("You have {$amount} AED expiring in {$this->daysUntilExpiry} days.")
            ->line('Source: ' . ucfirst(str_replace('_', ' ', $this->lot->source)))
            ->line('Expiry Date: ' . $this->lot->expires_at->format('M j, Y'))
            ->action('Use Funds Now', url('/events'))
            ->line('Don\'t let your funds go to waste! Use them before they expire.');
    }

    public function toArray($notifiable): array
    {
        return [
            'lot_id' => $this->lot->id,
            'amount' => $this->lot->remaining,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expiry_date' => $this->lot->expires_at->toISOString(),
            'message' => "You have " . number_format($this->lot->remaining, 2) .
                        " AED expiring in {$this->daysUntilExpiry} days.",
            'action_url' => url('/events'),
            'icon' => '⏰',
            'color' => 'warning'
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
