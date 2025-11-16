<?php
namespace App\Services;

use App\Models\LoginLog;
use Illuminate\Http\Request;

#[AllowDynamicProperties]
class HelperService
{
    function __construct()
    {
        $this->customer_team_id = config('app.customer_team_id');;
        $this->system_user_team_id = config('app.system_user_team_id');
    }

    public static function logLogin(Request $request, $user = null, $loginType = 'email', $success = true)
    {
        $model = !empty($request->header('model')) ? $request->header('model') : null;
        $manufacturer = !empty($request->header('manufacturer')) ? $request->header('manufacturer') : null;

        $device_model = $manufacturer .'/'.$model;

        LoginLog::create([
            'user_id'    => $user?->id,
            'ip_address' => $request->ip(),
            'device_type'=> $request->header('device') ?? 'web',
            'device_model'=> $device_model,
            'os'         => $request->header('osVersion') ?? null,
            'browser'    => $request->userAgent(),
            'login_type' => $loginType,
            'location'   => null, // Optional: Add geoip here
            'successful' => $success,
        ]);
    }
}
