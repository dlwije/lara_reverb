<?php

namespace Modules\GiftCard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\GiftCard\Http\Requests\GiftCardBatchRequest;
use Modules\GiftCard\Http\Requests\GiftCardRequest;
use Modules\GiftCard\Models\GiftCard;
use Modules\GiftCard\Services\GiftCardService;
use Modules\PromoRules\Models\GiftCardBatch;

class AdminGiftCardController extends Controller
{
    public function __construct(public GiftCardService $giftCardService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'batch_id', 'code', 'redeemed_by']);
        $perPage = $request->input('per_page', 15);

        $giftCards = $this->giftCardService->getGiftCards($filters, $perPage);

        return self::success($giftCards, 'Gift Cards retrieved successfully!');
//        return view('giftcard::index');
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
    public function store(GiftCardRequest $request)
    {
        try{
            $giftCard = $this->giftCardService->createGiftCard($request->validated());

            return self::success($giftCard, 'Gift Card created successfully!', 201);
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Create gift cards in bulk via CSV upload
     */
    public function createBulk(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'batch_name' => 'required|string|max:255',
            'original_value' => 'required|numeric|min:0',
            'expires_at' => 'required|date|after:today',
            'promo_rule_id' => 'nullable|exists:promo_rules,id',
        ]);

        try{
            $batch = $this->giftCardService->createGiftCardsFromCsv(
                $request->file('csv_file'),
                $request->input('batch_name'),
                $request->input('original_value'),
                $request->input('expires_at'),
                $request->input('promo_rule_id')
            );
            return self::success($batch, 'Gift Cards batch created successfully!', 201);
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $giftCard = GiftCard::with(['batch', 'promoRule', 'redeemedBy'])->findOrFail($id);

        return self::success($giftCard, 'Gift Card retrieved successfully!');
//        return view('giftcard::show');
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
    public function update(GiftCardRequest $request, $id)
    {
        try {
            $giftCard = $this->giftCardService->updateGiftCard($id, $request->validated());

            return self::success($giftCard, 'Gift Card updated successfully!');
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        try {
            $this->giftCardService->deleteGiftCard($id);

            return self::success([], 'Gift Card deleted successfully!');
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Activate a gift card
     */
    public function activate($id) {
        try {
            $giftCard = $this->giftCardService->changeStatus($id, 'active');

            return self::success($giftCard, 'Gift Card activated successfully!');
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Deactivate a gift card
     */
    public function deactivate($id) {
        try {
            $giftCard = $this->giftCardService->changeStatus($id, 'inactive');

            return self::success($giftCard, 'Gift Card deactivated successfully!');
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Void gift card
     */
    public function void($id) {
        try {
            $giftCard = $this->giftCardService->changeStatus($id, 'void');

            return self::success($giftCard, 'Gift Card voided successfully!');
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Export gift cards as CSV
     */
    public function export(Request $request)
    {
        $filters = $request->only(['status', 'batch_id', 'code']);

        return $this->giftCardService->exportGiftCards($filters);
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
     * List all gift card batches
     */
    public function batches(Request $request)
    {
        $filters = $request->only(['status']);
        $perPage = $request->input('per_page', 15);

        $batches = GiftCardBatch::withCount(['giftCards', 'giftCards as redeemed_count' => function ($query) {
                $query->where('status', 'redeemed');
            }])
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return self::success($batches, 'Gift Card Batches retrieved successfully!');
    }

    /**
     * Create a new gift card batch
     */
    public function createBatch(GiftCardBatchRequest $request)
    {
        try {
            $batch = $this->giftCardService->createGiftCardBatch($request->validated());

            return self::success($batch, 'Gift Card Batch created successfully!', 201);
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Generate gift cards for a batch
     */
    public function generateBatchGiftCards(Request $request, $batchId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10000',
            'prefix' => 'nullable|string|max:10',
            'code_length' => 'nullable|integer|min:8|max:20',
        ]);

        try {
            $batch = $this->giftCardService->generateBatchGiftCards(
                $batchId,
                $request->input('quantity'),
                $request->input('prefix'),
                $request->input('code_length')
            );

            return self::success($batch, 'Gift Cards generated successfully!');
        }catch (\Exception $e){
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

}
