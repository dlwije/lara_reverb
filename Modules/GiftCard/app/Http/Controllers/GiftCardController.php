<?php

namespace Modules\GiftCard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\GiftCard\Http\Requests\RedeemGiftCardRequest;
use Modules\GiftCard\Services\GiftCardService;
use Modules\GiftCard\Services\OTPService;
use Modules\Wallet\Models\WalletTransaction;

class GiftCardController extends Controller
{
    public function __construct(protected GiftCardService $giftCardService, protected OTPService $otpService) { }


    /**
     * Redeem a gift card
     */
    public function redeem(RedeemGiftCardRequest $request)
    {
        try {
            $user = auth()->user();
            $result = $this->giftCardService->redeemGiftCard($user, $request->code, $request->otp);

            return self::success($result, 'Gift Card Redeemed successfully!');
        } catch (\Exception $e) {
            Log::error($e);
            if ($e->getMessage() === 'OTP_REQUIRED') {
                return self::error(
                    'OTP verification required', 422, [],
                    [
                        'otp_sent' => true,
                        'message' => 'OTP has been sent to your registered email/phone',
                        'code' => 'OTP_REQUIRED',
                    ]
                );
            }
            return self::error($e->getMessage(), 422);
        }
    }

    public function resendOtp(Request $request)
    {
        try {
            $user = auth()->user();
            $request->validate(['code' => 'required|string|max:50']);

            // Validate gift card first
            $giftCard = $this->giftCardService->validateGiftCard($request->code);
            $finalCredit = $this->giftCardService->calculateFinalCredit($giftCard['gift_card'], $user);

            // Generate and send new OTP
            $this->otpService->generateAndSendOtp($user);

            return self::success([], 'OTP resent successfully!');
        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Validate gift card without redeeming
     */
    public function validateCard(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string|max:50']);

            $result = $this->giftCardService->validateGiftCard($request->code);

            return self::success($result, 'Gift Card Validated successfully!');
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Preview gift card redemption
     */
    public function previewRedemption(Request $request)
    {
        try{
            $user = auth()->user();
            $request->validate(['code' => 'required|string|max:50']);

            $preview = $this->giftCardService->previewRedemption($user, $request->code);

            return self::success($preview, 'Gift Card Previewed successfully!');
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Get user's gift card redemption history
     */
    public function redemptionHistory(Request $request)
    {
        try {
            $user = auth()->user();
            $perPage = $request->input('per_page', 15);

            $redemptions = WalletTransaction::where('user_id', $user->id)
                ->where('type', 'gift_card_redeem')
                ->with(['giftCard', 'walletLot'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return self::success($redemptions, 'Gift Card Redemption History retrieved successfully!');
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

}
