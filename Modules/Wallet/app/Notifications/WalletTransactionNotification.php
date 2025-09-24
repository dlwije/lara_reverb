<?php

namespace Botble\Wallet\Events;

use Botble\Wallet\Models\WalletTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WalletTransactionNotification extends Notification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $transaction;
    protected $channel;
    /**
     * Create a new event instance.
     */
    public function __construct(WalletTransaction $transaction, string $channel = 'all')
    {
        // Safely extract transaction from event or use as is
        if (property_exists($transaction, 'transaction')) {
            $this->transaction = $transaction->transaction;
        } else {
            $this->transaction = $transaction;
        }

        $this->channel = $channel;
    }

    private function getTransactionId()
    {
        return $this->transaction->id ?? null;
    }

    public function via($notifiable): array
    {
        $channels = [];

        if ($this->channel === 'all' || $this->channel === 'database') {
            $channels[] = 'database';
        }

        if ($this->channel === 'all' || $this->channel === 'mail') {
            $channels[] = 'mail';
        }

        if ($this->channel === 'all' || $this->channel === 'broadcast') {
            $channels[] = 'broadcast';
        }

        // Add SMS/WhatsApp channels if configured
        if (config('wallet.notifications.sms_enabled')) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('NotifyToMail: '.$this->transaction->id);
        $subject = $this->getSubject();
        $amount = number_format($this->transaction->amount, 2);
        $balance = $this->transaction->running_balance ?? 0;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->getMessage())
            ->line('Amount: ' . $amount . ' ' . $this->transaction->currency)
            ->line('Current Balance: ' . number_format($balance, 2) . ' ' . $this->transaction->currency)
            ->action('View Transaction', $this->getActionUrl())
            ->line('Thank you for using our wallet service!')
            ->lineIf($this->transaction->direction === 'DR',
                'If you did not make this transaction, please contact support immediately.');
    }

    public function toDatabase($notifiable): array
    {
        Log::info('NotifyToDatabase: '.$this->transaction->id);
        return [
            'transaction_id' => $this->transaction->id ?? null,
            'type' => $this->transaction->type ?? 'unknown',
            'direction' => $this->transaction->direction ?? '',
            'amount' => $this->transaction->amount ?? 0,
            'currency' => $this->transaction->currency ?? 'AED',
            'running_balance' => $this->transaction->running_balance,
            'title' => $this->getMessageTitle(),
            'message' => $this->getMessage(),
            'action_url' => $this->getActionUrl(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'timestamp' => now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        Log::info('NotifyToBroadcast: '.$this->transaction->id);
        return new BroadcastMessage([
            'transaction_id' => $this->transaction->id,
            'type' => $this->transaction->type,
            'direction' => $this->transaction->direction,
            'amount' => $this->transaction->amount,
            'title' => $this->getMessageTitle(),
            'message' => $this->getMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function toSms($notifiable): string
    {
        Log::info('NotifyToSms: '.$this->transaction->id);
        return $this->getMessage() . ' Amount: ' .
               number_format($this->transaction->amount, 2) . ' ' .
               $this->transaction->currency . '. Balance: ' .
               number_format($this->transaction->running_balance ?? 0, 2) .
               ' ' . $this->transaction->currency;
    }

    private function getSubject(): string
    {
        return match($this->transaction->type) {
            'gift_card_redeem' => 'Gift Card Redeemed Successfully',
            'purchase' => 'Payment Processed',
            'refund_credit' => 'Refund Received',
            'admin_adjustment' => 'Wallet Adjustment',
            'promo_credit' => 'Bonus Credit Added',
            default => 'Wallet Transaction Notification'
        };
    }

    private function getMessage(): string
    {
        $amount = number_format($this->transaction->amount, 2) . ' ' . $this->transaction->currency;

        return match($this->transaction->type) {
            'gift_card_redeem' => "You have successfully redeemed a gift card. {$amount} has been added to your wallet.",
            'purchase' => $this->transaction->direction === 'CR'
                ? "Refund processed. {$amount} has been added to your wallet."
                : "Payment of {$amount} has been processed from your wallet.",
            'refund_credit' => "Refund of {$amount} has been credited to your wallet.",
            'admin_adjustment' => $this->transaction->direction === 'CR'
                ? "Admin has credited {$amount} to your wallet."
                : "Admin has deducted {$amount} from your wallet.",
            'promo_credit' => "Bonus credit of {$amount} has been added to your wallet.",
            default => $this->transaction->direction === 'CR'
                ? "{$amount} has been credited to your wallet."
                : "{$amount} has been debited from your wallet."
        };
    }
    private function getMessageTitle(): string
    {
        $amount = number_format($this->transaction->amount, 2) . ' ' . $this->transaction->currency;

        return match($this->transaction->type) {
            'gift_card_redeem' => "Redeemed Successfully",
            'purchase' => $this->transaction->direction === 'CR'
                ? "Refunded Successfully."
                : "Purchase Completed.",
            'refund_credit' => "Refunded Successfully.",
            'admin_adjustment' => $this->transaction->direction === 'CR'
                ? "Admin has credited to your wallet."
                : "Admin has deducted from your wallet.",
            'promo_credit' => "Bonus credithas been added to your wallet.",
            default => $this->transaction->direction === 'CR'
                ? "{$amount} has been credited to your wallet."
                : "{$amount} has been debited from your wallet."
        };
    }

    private function getActionUrl(): string
    {
        return url('/wallet/transactions/' . $this->transaction->id);
    }

    private function getIcon(): string
    {
        return $this->transaction->direction === 'CR' ? '💰' : '💳';
    }

    private function getColor(): string
    {
        return $this->transaction->direction === 'CR' ? 'success' : 'warning';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notification.'.auth()->id()),
        ];
    }

    public function BroadCastAs()
    {
        return "NewUserNotification";
    }
}
