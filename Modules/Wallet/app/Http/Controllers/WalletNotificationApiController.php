<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletNotificationApiController extends Controller
{
    public function getPreferences(Request $request)
    {
        $user = auth()->user();

        return self::success($user->getNotificationSettings());
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.enabled' => 'boolean',
            'preferences.*.channels' => 'array'
        ]);

        $user = auth()->user();
        $user->updateNotificationPreferences($request->preferences);

        return self::success($user->getNotificationSettings(), 'Notification preferences updated successfully');
    }

    /**
     * Update specific preference
     */
    public function updatePreference(Request $request, string $type)
    {
        $request->validate([
            'enabled' => 'boolean',
            'channels' => 'array'
        ]);

        $user = auth()->user();
        $user->updateNotificationPreference(
            $type,
            $request->channels ?? [],
            $request->enabled ?? true
        );

        return self::success($user->getNotificationSettings()[$type], 'Notification preferences updated successfully');
    }
}
