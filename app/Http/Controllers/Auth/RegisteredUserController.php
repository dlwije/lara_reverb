<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Modules\ApiAuth\Actions\SaveUser;
use Modules\ApiAuth\Enums\RoleEnum;
use Modules\ApiAuth\Http\Controllers\ApiAuthController;
use Modules\ApiAuth\Http\Requests\UserRequest;
use Modules\ApiAuth\Services\AuthTokenService;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Ensure roles are provided or fall back to ['customer']
        $data['roles'] = $request->input('roles', [RoleEnum::customer->name]);;
        $data['team_id'] = $data['team_id'] ?? 1; // or get team ID dynamically
        $data['login_type'] = 'email';



        // Generate Passport access token
        $tokenService = app(AuthTokenService::class);

        try {

            $user = (new SaveUser)->execute($data);
            $tokenData = $tokenService->issueTokenViaPasswordGrant($request->email, $request->password);

            event(new Registered($user));

            Auth::login($user);

            session([
                'access_token' => $tokenData['access_token'] ?? null,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'access_token_expires_in' => $tokenData['expires_in'] ?? 3600, // typically 3600 seconds
                'refresh_token_expires_in' => round(now()->diffInSeconds(now()->addDays(15))), // 15 * 24 * 60 * 60 // 1296000 seconds
            ]);
//            return self::inertiaSuccess([], 'dashboard');

            return redirect()->intended(route('dashboard', absolute: false));
        }catch (\Throwable $th) {

            return back()->with('error', __('{model} cannot be {action}.', [
                'model'  => __('User'),
                'action' => __('deleted'),
            ]));
        }
    }
}
