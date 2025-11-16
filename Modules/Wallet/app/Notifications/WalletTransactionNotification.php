<?php

namespace Modules\Wallet\Notifications;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Models\WalletTransaction;

class WalletTransactionNotification extends Notification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $transaction;
    protected $channel;
    /**
     * Create a new event instance.
     */
    public function __construct(WalletTransaction $transaction, string $channel = 'all', public $notifyType = 'transaction')
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
            'category' => $this->notifyType ?? 'unknown',
            'direction' => $this->transaction->direction ?? '',
            'amount' => $this->transaction->amount ?? 0,
            'currency' => $this->transaction->currency ?? 'AED',
            'running_balance' => $this->transaction->running_balance,
            'title' => $title,
            'message' => $message,
            'message_params' => ['amount' => $this->transaction->amount ?? 0],
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
            'deposit' => 'Deposited to Wallet Successfully',
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
            'gift_card_redeem' => 'you_have_successfully_redeemed_a_gift_card',
            'purchase' => $this->transaction->direction === 'CR'
                ? 'refund_processed_and_has_been_added_to_your_wallet'
                    : 'payment_has_been_processed_from_your_wallet',
            'refund_credit' => 'refund_has_been_credited_to_your_wallet',
            'admin_adjustment' => $this->transaction->direction === 'CR'
                ? "Admin has credited {$amount} to your wallet."
                : "Admin has deducted {$amount} from your wallet.",
            'promo_credit' => "Bonus credit of {$amount} has been added to your wallet.",
            default => $this->transaction->direction === 'CR'
                ? 'amount_has_been_credited_to_your_wallet'
                : 'redeemed_successfully'
        };
    }
    private function getMessageTitle(): string
    {
        $amount = number_format($this->transaction->amount, 2) . ' ' . $this->transaction->currency;

        return match($this->transaction->type) {
            'gift_card_redeem' => 'redeemed_successfully',
            'purchase' => $this->transaction->direction === 'CR'
                ? 'refunded_successfully'
                : 'purchase_completed',
            'refund_credit' => 'refunded_successfully',
            'admin_adjustment' => $this->transaction->direction === 'CR'
                ? "Admin has credited to your wallet."
                : "Admin has deducted from your wallet.",
            'promo_credit' => "Bonus credit has been added to your wallet.",
            default => $this->transaction->direction === 'CR'
                ? "{$amount} has been credited to your wallet."
                : "{$amount} has been debited from your wallet."
        };
    }

    private function getActionUrl(): string
    {
        return get_frontend_url('/wallet/history');
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
