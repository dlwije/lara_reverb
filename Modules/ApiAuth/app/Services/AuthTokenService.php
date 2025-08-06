<?php

namespace Modules\ApiAuth\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class AuthTokenService
{
    public function issueTokenViaPasswordGrant(string $email, string $password, string $scope = ''): array
    {
//        Log::info([$email, $password, $scope]);
        $internalRequest = Request::create('/oauth/token', 'POST', [
            'grant_type'    => 'password',
            'client_id'     => env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
            'username'      => $email,
            'password'      => $password,
            'scope'         => $scope,
        ]);

        $response = App::handle($internalRequest);
//        Log::info([$response->getContent()]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Token issue failed.');
        }

        return json_decode($response->getContent(), true);
    }

    public function issueTokenViaRefreshToken(string $refreshToken, string $scope = ''): array
    {
        $internalRequest = Request::create('/oauth/token', 'POST', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
            'scope'         => $scope,
        ]);

        $response = App::handle($internalRequest);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Token issue failed.');
        }

        return json_decode($response->getContent(), true);
    }

    public function respondWithToken($token_data, $token_type = null, $user=null): \Illuminate\Http\JsonResponse
    {
        $data = [
            'token' => [
                'access_token' => $token_data['access_token'] ?? null,
                'refresh_token' => $token_data['refresh_token'] ?? null,
                'token_type' => $token_data['token_type'] ?? null,
                'access_token_expires_in' => $token_data['expires_in'] ?? 3600, // typically 3600 seconds
                'refresh_token_expires_in' => round(now()->diffInSeconds(now()->addDays(15))), // 15 * 24 * 60 * 60 // 1296000 seconds
            ],
            'user' => [
                'id' => $user->id ?? '',
                'email' => $user->email ?? '',
                'team_id' => $user->team_id ?? '',
                'name' => $user->name ?? '',
                'created_at' => $user->created_at ?? '',
            ],
        ];

        if($token_type == 'register') $message = __('messages.registered_successfully');
        else if($token_type == 'refresh') $message = __('messages.generated_successfully');
        else if($token_type == 'otp') $message = __('messages.otp_verified_successfully');
        else $message = __('messages.login_successfully');
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], 200);
    }
}
