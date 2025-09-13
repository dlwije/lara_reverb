<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Services\WalletService;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService){}

    /**
     * Get wallet balance and details
     */
    public function getWallet()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getUserWallet($user);

        return self::success($wallet,'Wallet');
    }

    /**
     * Get wallet lots
     */
    public function getLots(Request $request)
    {
        $user = auth()->user();
        $status = $request->get('status', 'active');
        $perPage = $request->get('per_page', 15);

        $lots = WalletLot::where('user_id', $user->id)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('expires_at', 'asc')
            ->paginate($perPage);

        return self::success($lots,'Wallet Lots');
    }

    /**
     * Get transaction history
     */
    public function getTransactions(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only(['type','from', 'to', 'min', 'max']);
        $perPage = $request->get('per_page', 15);

        $transactions = $this->walletService->getUserTransactions($user, $filters, $perPage);

        return self::success($transactions,'Wallet Transactions');
    }

    /**
     * Export transactions as CSV
     */
    public function exportTransactions(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only(['type', 'from', 'to', 'min', 'max']);

        return $this->walletService->exportTransactions($user, $filters);
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
