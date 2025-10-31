<?php

namespace Modules\Wallet\Services;

use Illuminate\Support\Facades\Log;
use Modules\Wallet\Models\NotificationPreference;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Notifications\WalletTransactionNotification;

class NotificationService
{
    /**
     * Send transaction notification
     */
    public function sendTransactionNotification(WalletTransaction $transaction, string $channel = 'all'): void
    {
        Log::info('Send transaction notification', ['transaction' => $transaction]);
        $user = $transaction->user;
        Log::info('User Notify: ' . $user->id ?? 'no');

        // Check if user has enabled this notification type
        if (!$user->isNotificationEnabled('transaction')) {
            Log::info('Transaction notifications disabled for user', ['user_id' => $user->id]);
            return;
        }

        // Get user's preferred channels
        $userChannels = $user->getNotificationChannels('transaction');
        Log::info('userChannels: ' . json_encode($userChannels));

        // If specific channel requested, check if it's enabled
        if ($channel !== 'all' && !in_array($channel, $userChannels)) {
            Log::info('Channel disabled for user', [
                'user_id' => $user->id,
                'channel' => $channel
            ]);
            return;
        }

        // Send notification through user's preferred channels
        try {
            $user->notify(new WalletTransactionNotification($transaction, $channel, NotificationPreference::TYPE_TRANSACTION));

            Log::info('Transaction notification sent', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'channels' => $userChannels
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send transaction notification', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send expiry reminder notifications
     */
    public function sendExpiryReminders(): void
    {
        $expiringLots = WalletLot::where('status', 'active')
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->with('user')
            ->get();

        foreach ($expiringLots as $lot) {
            $daysUntilExpiry = $lot->expires_at->diffInDays(now());

            // Send reminders at 30, 7, and 1 day before expiry
            if (in_array($daysUntilExpiry, [30, 7, 1])) {
                $this->sendExpiryReminder($lot, $daysUntilExpiry);
            }
        }
    }

    /**
     * Send individual expiry reminder
     */
    private function sendExpiryReminder(WalletLot $lot, int $daysUntilExpiry): void
    {
        $user = $lot->user;

        if (!$this->shouldSendNotification($user, 'expiry_reminder', 'mail')) {
            return;
        }

        try {
            $user->notify(new ExpiryReminderNotification($lot, $daysUntilExpiry));

            Log::info('Expiry reminder sent', [
                'user_id' => $user->id,
                'lot_id' => $lot->id,
                'days_until_expiry' => $daysUntilExpiry
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send expiry reminder', [
                'user_id' => $user->id,
                'lot_id' => $lot->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    private function shouldSendNotification(User $user, string $type, string $channel): bool
    {
        // Get user notification preferences (you can create a user_preferences table)
        $preferences = $user->notificationPreferences ?? [
            'transaction' => ['mail', 'database'],
            'expiry_reminder' => ['mail'],
            'promotional' => ['mail']
        ];

        return in_array($channel, $preferences[$type] ?? []);
    }

    /**
     * Send bulk notifications (for admin actions)
     */
    public function sendBulkTransactionNotifications(array $transactionIds, string $channel = 'mail'): void
    {
        $transactions = WalletTransaction::whereIn('id', $transactionIds)
            ->with('user')
            ->get();

        foreach ($transactions as $transaction) {
            $this->sendTransactionNotification($transaction, $channel);
        }
    }
}
