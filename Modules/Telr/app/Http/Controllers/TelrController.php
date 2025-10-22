<?php

namespace Modules\Telr\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use function Termwind\render;

class TelrController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('telr/process-form');
    }

    public function payment(Request $request)
    {
        Log::info('Telr Payment Request', ['request' => $request->all()]);
        exit('sd');
    }
    public function process(Request $request)
    {
        $request->validate([
            'store_id' => 'required|string',
            'auth_key' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'bill_fname' => 'required|string',
            'bill_sname' => 'required|string',
            'bill_email' => 'required|email',
            'bill_tel' => 'required|string',
            'telr_token' => 'required|string',
            'repeat_amount' => 'required|numeric',
            'repeat_period' => 'required|string|in:M,W',
            'repeat_interval' => 'required|integer',
            'repeat_term' => 'required|integer',
            'repeat_final' => 'required|numeric',
        ]);

        try {
            // Use Laravel route URLs
            $returnAuth = URL::route('telr.auth');
            $returnCan = URL::route('telr.cancel');
            $returnDecl = URL::route('telr.decline');

            $data = [
                'ivp_method' => 'create',
                'ivp_source' => 'Laravel Transparent JS SDK',
                'ivp_store' => $request->store_id,
                'ivp_authkey' => $request->auth_key,
                'ivp_cart' => rand(100, 999) . rand(100, 999) . rand(100, 999),
                'ivp_test' => 1, // Set to 0 for production
                'ivp_framed' => 2,
                'ivp_amount' => $request->amount,
                'ivp_lang' => 'en',
                'ivp_currency' => $request->currency,
                'ivp_desc' => 'Transaction from Laravel Transparent SDK',
                'return_auth' => $returnAuth,
                'return_can' => $returnCan,
                'return_decl' => $returnDecl,
                'bill_fname' => $request->bill_fname,
                'bill_sname' => $request->bill_sname,
                'bill_addr1' => $request->bill_addr1,
                'bill_addr2' => $request->bill_addr2,
                'bill_city' => $request->bill_city,
                'bill_region' => $request->bill_region,
                'bill_zip' => $request->bill_zip,
                'bill_country' => $request->bill_country,
                'bill_email' => $request->bill_email,
                'bill_tel' => $request->bill_tel,
                'ivp_paymethod' => 'card',
                'card_token' => $request->telr_token,
                'repeat_amount' => $request->repeat_amount,
                'repeat_period' => $request->repeat_period,
                'repeat_interval' => $request->repeat_interval,
                'repeat_start' => 'next',
                'repeat_term' => $request->repeat_term,
                'repeat_final' => $request->repeat_final,
            ];

            $results = $this->apiRequest($data);

            if (isset($results['order']['ref']) && isset($results['order']['url'])) {
                $ref = trim($results['order']['ref']);
                $url = trim($results['order']['url']);

                Session::put([
                    'telr_ref' => $ref,
                    'telr_store_id' => $request->store_id,
                    'telr_auth_key' => $request->auth_key,
                ]);

                return response()->json([
                    'redirect_link' => $url,
                    'success' => true,
                ]);
            }

            Log::error('Telr API Error: Invalid response', ['response' => $results]);
            return response()->json([
                'error' => 'Error occurred in processing transaction',
                'details' => $results['error']['message'] ?? 'Unknown error',
            ], 422);

        } catch (\Exception $e) {
            Log::error('Telr Payment Processing Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function auth(Request $request)
    {
        Log::info('Telr Request', ['request' => $request->all()]);
        // Handle successful authentication callback
        $telrRef = Session::get('telr_ref');
        $telrStoreId = Session::get('telr_store_id');
        $telrAuthKey = Session::get('telr_auth_key');

        if (!$telrRef || !$telrStoreId || !$telrAuthKey) {
            return inertia('PaymentError', [
                'message' => 'Invalid session data',
            ]);
        }

        $params = [
            'ivp_method' => 'check',
            'ivp_store' => $telrStoreId,
            'ivp_authkey' => $telrAuthKey,
            'order_ref' => $telrRef
        ];

        $results = $this->apiRequest($params);

        Log::info('Telr Auth Response', ['results' => $results]);;
        $objOrder = $results['order'] ?? null;
        $objError = $results['error'] ?? null;

        // Check for errors
        if (is_array($objError)) {
            return Inertia::render('telr/payment-error', [
                'message' => 'Transaction failed',
                'details' => $objError,
            ]);
        }

        // Validate required fields
        if (!isset(
            $objOrder['cartid'],
            $objOrder['status']['code'],
            $objOrder['transaction']['status'],
            $objOrder['transaction']['ref']
        )) {
            return Inertia::render('telr/payment-error', [
                'message' => 'Invalid transaction response',
                'details' => $results,
            ]);
        }

        $newTx = $objOrder['transaction']['ref'];
        $ordStatus = $objOrder['status']['code'];
        $txStatus = $objOrder['transaction']['status'];
        $txMessage = $objOrder['transaction']['message'];
        $cartId = $objOrder['cartid'];

        // Handle different order statuses
        if (in_array($ordStatus, [-1, -2, -3, -4])) {
            return Inertia::render('telr/payment-cancel', [
                'message' => 'Transaction was cancelled or expired',
                'reference' => $newTx,
                'status' => $ordStatus,
            ]);
        }

        if ($ordStatus == 4) {
            return Inertia::render('telr/payment-pending', [
                'message' => 'Transaction is pending',
                'reference' => $newTx,
            ]);
        }

        if ($ordStatus == 1) {
            return Inertia::render('telr/payment-pending', [
                'message' => 'Payment pending',
                'reference' => $newTx,
            ]);
        }

        if ($ordStatus == 2) {
            return Inertia::render('telr/payment-success', [
                'message' => 'Transaction authorized successfully',
                'reference' => $newTx,
                'transaction_ref' => $newTx,
            ]);
        }

        if ($ordStatus == 3) {
            if ($txStatus == 'P') {
                return Inertia::render('telr/payment-pending', [
                    'message' => 'Transaction is pending',
                    'reference' => $newTx,
                ]);
            }

            if ($txStatus == 'H') {
                return Inertia::render('telr/payment-pending', [
                    'message' => 'Transaction is on hold',
                    'reference' => $newTx,
                ]);
            }

            if ($txStatus == 'A') {
                return Inertia::render('telr/payment-success', [
                    'message' => 'Transaction authorized successfully',
                    'reference' => $newTx,
                    'transaction_ref' => $newTx,
                ]);
            }
        }

        // Default case for unhandled status
        return Inertia::render('telr/payment-pending', [
            'message' => 'Transaction status is being processed',
            'reference' => $newTx,
            'status' => $ordStatus,
            'transaction_status' => $txStatus,
        ]);
    }

    public function cancel(Request $request)
    {
        // Handle cancellation callback
        return Inertia::render('telr/payment-cancel', [
            'message' => 'Payment was cancelled by user',
        ]);
    }

    public function decline(Request $request)
    {
        // Handle declined payment callback
        $telrRef = Session::get('telr_ref');
        $telrStoreId = Session::get('telr_store_id');
        $telrAuthKey = Session::get('telr_auth_key');

        if (!$telrRef || !$telrStoreId || !$telrAuthKey) {
            return Inertia::render('telr/payment-error', [
                'message' => 'Invalid session data',
            ]);
        }

        $params = [
            'ivp_method' => 'check',
            'ivp_store' => $telrStoreId,
            'ivp_authkey' => $telrAuthKey,
            'order_ref' => $telrRef
        ];

        $results = $this->apiRequest($params);

        Log::info('Telr Decline Response', ['results' => $results]);

        return Inertia::render('telr/payment-decline', [
            'message' => 'Payment was declined',
            'details' => $results['error'] ?? $results,
            'reference' => $telrRef,
        ]);
    }

    private function apiRequest(array $data): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->retry(3, 100)
            ->post('https://secure.telr.com/gateway/order.json', $data);

        if ($response->failed()) {
            throw new \Exception('Telr API request failed: ' . $response->status());
        }

        return $response->json();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('telr::create');
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
        return view('telr::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('telr::edit');
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
