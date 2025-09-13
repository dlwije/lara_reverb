<?php

namespace Modules\GiftCard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\GiftCard\Services\GiftCardService;
use Modules\Wallet\Models\WalletTransaction;

class GiftCardController extends Controller
{
    public function __construct(protected GiftCardService $giftCardService) { }


    /**
     * Redeem a gift card
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'otp' => 'sometimes|required|string'
        ]);

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
     * Get user's gift card redemption history
     */
    public function redemptionHistory(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 15);

        $redemptions = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'gift_card_redeem')
            ->with('giftCard')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return self::success($redemptions, 'Gift Card Redemption History retrieved successfully!');
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
