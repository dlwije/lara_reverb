<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Modules\ApiAuth\Services\AuthTokenService;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AuthTokenService $tokenService): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        try {

            $tokenData = $tokenService->issueTokenViaPasswordGrant($request->email, $request->password);

            Log::info('tokenData:', $tokenData);
            session([
                'access_token' => $tokenData['access_token'] ?? null,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'access_token_expires_in' => $tokenData['expires_in'] ?? 3600, // typically 3600 seconds
                'refresh_token_expires_in' => round(now()->diffInSeconds(now()->addDays(15))), // 15 * 24 * 60 * 60 // 1296000 seconds
            ]);

            return redirect()->intended(route('dashboard', absolute: false));
        }catch (\Throwable $th) {

            Log::error($th);
            return back()->with('error', __('{model} cannot be {action}.', [
                'model'  => __('User'),
                'action' => __('deleted'),
            ]));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $user = Auth::guard('api')->user();
        if ($user) {
            // Revoke only current access token
            $user->token()->revoke(); // just the current one
        }
        session()->forget([
            'access_token',
            'refresh_token',
            'access_token_expires_in',
            'refresh_token_expires_in',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
