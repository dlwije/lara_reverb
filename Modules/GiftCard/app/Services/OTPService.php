<?php

namespace Modules\GiftCard\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Wallet\Models\WalletTransaction;

class OTPService
{
    protected $enabled;
    protected $threshold;
    protected $expiryMinutes;
    public function __construct()
    {
        $this->enabled = config('giftcard.otp.enabled', false);
        $this->threshold = config('giftcard.otp.amount_threshold', 1000);
        $this->expiryMinutes = config('giftcard.otp.expiry_minutes', 10);
    }

    public function isOtpRequired(User $user, float $amount): bool
    {
        if(!$this->enabled) return false;
        if($amount >= $this->threshold) return true;

        if($this->hasSuspiciousActivity($user)) return true;

        if($this->isNewDeviceOrLocation($user)) return true;

        return false;
    }

    public function generateAndSendOtp(User $user, string $action = 'gift_card_redeem'): string
    {
        $otp = rand(100000, 999999);

        Cache::put(
            $this->getOtpKey($user->id, $action),
            $otp,
            now()->addMinutes($this->expiryMinutes)
        );

        $this->sendOtp($user, $otp, $action);

        return $otp;
    }

    public function verifyOtp(User $user, string $otp, string $action = 'gift_card_redeem'): bool
    {
        $storedOtp = Cache::get($this->getOtpKey($user->id, $action));

        if(!$storedOtp || $storedOtp !== $otp) return false;

        Cache::forget($this->getOtpKey($user->id, $action));

        return true;
    }

    private function hasSuspiciousActivity(User $user): bool
    {
        $recentRedemption = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'gift_card_redeem')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return $recentRedemption >= config('giftcard.otp.max_daily_redemptions', 5);
    }

    private function isNewDeviceOrLocation(User $user): bool
    {
        $deviceId = request()->header('X-Device-Id');
        $ip = request()->ip();

        $previousRedemption = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'gift_card_redeem')
            ->where(function ($query) use ($deviceId, $ip) {
                if($deviceId) {
                    $query->where('metadata->device_id', $deviceId);
                }
                $query->orWhere('ip', $ip);
            })
            ->exists();

        return !$previousRedemption;
    }

    private function sendOtp(User $user, string $otp, string $action): void
    {
        $message = "Your OTP for gift card redemption is: {$otp}. Valid for {$this->expiryMinutes} minutes.";

        // Send via SMS (integrate with SMS service)
        // $this->smsService->send($user->phone, $message);

        // Send via Email
        $user->notify(new GiftCardOtpNotification($otp, $action));
    }

    private function getOtpKey(int $userId, string $action): string
    {
        return "wallet_otp:{$userId}:{$action}";
    }
}
