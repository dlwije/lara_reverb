<?php

namespace App\Http\Middleware;

use App\Models\Sma\Setting\Store;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $opened_register = null;
        $available_stores = null;

        $user = $request->user();
        if($user){
            $user->loadMissing(['roles','store']);
            if(get_module('pos')) {
                $opened_register = $user->openedRegister;
                $user->all_permissions = $user->getAllPermissions()->pluck('name');
                session(['open_register_id' => $opened_register?->id]);
                if($user->store_id) {
                    $available_stores = [$user->store];
                    session(['selected_store_id' => $user->store_id]);
                }
            }
        }

        if(! $available_stores){
            $available_stores = Store::active()->get(['id', 'name']);
        }

        $langFiles = json_decode(file_get_contents(lang_path('languages.json')));

        $site_url = \Request::segments();

        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'site_url'         => $site_url,
            'csrfToken'        => csrf_token(),
            'demo'             => demo(),
            'base'             => url('/'),
            'opened_register'  => $opened_register,
            'available_stores' => $available_stores,
            'pos_module'       => get_module('pos'),
            'language'         => app()->getLocale(),
            'languages'        => $langFiles->available,
            'settings'         => get_public_settings(),
            'open_register'    => !$opened_register,
            'selected_store'   => session('selected_store_id', null),
            'select_store'     => $request->flash['select_store'] ?? false,
            'filters'          => $request->input('filters') ?? ['search' => '', 'sort' => 'latest'],
            'flash'            => [
                'error'         => session('error'),
                'message'       => session('message'),
                'select_store'  => session('select_store') ?: $request->flash['select_store'] ?? null,
                'open_register' => session('open_register') ?: $request->flash['open_register'] ?? null,
            ],
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'roles' => fn() => $request->user()?->roles->pluck('name') ?? null,
                'permissions' => fn() => $request->user()?->getAllPermissions()->pluck('name') ?? null,
                'accessToken' => fn () => session('access_token'),
            ],

            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
