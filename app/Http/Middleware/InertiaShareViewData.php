<?php

namespace App\Http\Middleware;

use App\Models\Sma\Setting\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class InertiaShareViewData
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
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

        // Share globally with all Blade view
        View::share([
            'site_url'         => $site_url,
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
            ]
        ]);
        return $next($request);
    }
}
