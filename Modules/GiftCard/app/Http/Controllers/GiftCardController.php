<?php

namespace Modules\GiftCard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\GiftCard\Http\Requests\RedeemGiftCardRequest;
use Modules\GiftCard\Services\GiftCardService;
use Modules\Wallet\Models\WalletTransaction;

class GiftCardController extends Controller
{
    public function __construct(protected GiftCardService $giftCardService) { }


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

    /**
     * Bulk redeem gift cards (admin function)
     */
    public function bulkRedeem(Request $request)
    {
        try {
            $request->validate([
                'codes' => 'required|array',
                'codes.*' => 'string|max:50',
                'user_id' => 'required|exists:users,id',
            ]);

            $result = $this->giftCardService->bulkRedeemGiftCards($request->codes, $request->user_id);

            return self::success($result, 'Gift Card bulk redemption completed successfully!');
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('giftcard::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('giftcard::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('giftcard::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('giftcard::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
