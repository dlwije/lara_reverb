<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Services\KYCService;
use Modules\Wallet\Services\WalletLockService;
use Modules\Wallet\Services\WalletService;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        public KYCService $kycService,
        public WalletLockService $lockService
    ){}

    /**
     * Get wallet balance and details
     */
    public function getWallet()
    {
        try {
            $user = auth()->user();

            // Check if wallet is locked
            if($this->lockService->isWalletFrozen($user->id)){
                return self::error(
                    'Wallet is temporarily locked. Please contact support.',
                    403,
                    [],
                    ['is_locked' => true]
                );
            }

            $wallet = $this->walletService->getUserWallet($user);

            return self::success($wallet);

        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }

    /**
     * Get wallet lots
     */
    public function getLots(Request $request)
    {
        try {

            $user = auth()->user();
            $status = $request->get('status', 'active');
            $perPage = $request->get('per_page', 15);

            $lots = WalletLot::where('user_id', $user->id)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->orderBy('expires_at', 'asc')
                ->paginate($perPage);

            return self::success($lots);

        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['type','from', 'to', 'min', 'max']);
            $perPage = $request->get('per_page', 15);

            $transactions = $this->walletService->getUserTransactions($user, $filters, $perPage);

            return self::success($transactions);

        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }

    /**
     * Export transactions as CSV
     */
    public function exportTransactions(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['type', 'from', 'to', 'min', 'max']);

            return $this->walletService->exportTransactions($user, $filters);
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }

    /**
     * Get wallet statements
     */
    public function getStatements(Request $request)
    {
        try {
            $user = auth()->user();
            $month = $request->get('month', now()->format('Y-m'));

            $filters = $request->only(['from', 'to']);
            $perPage = $request->get('per_page', 15);

            $statement = $this->walletService->getUserTransactions($user, $filters, $perPage);

        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }

    public function getWalletSummary()
    {
        try {
            $user = auth()->user();
            $walletSummary = $this->walletService->getWalletSummary($user);
            return self::success($walletSummary);
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }
    public function getAvailableBalanceWithLots()
    {
        try {
            $user = auth()->user();
            $walletSummary = $this->walletService->getAvailableBalanceWithLots($user);
            return self::success($walletSummary);
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('settings/wallet');
    }

    public function walletStatement()
    {
        return Inertia::render('settings/wallet-statement');
    }

    public function addCard()
    {
        return Inertia::render('settings/add-card');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('wallet::create');
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
        return view('wallet::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('wallet::edit');
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
