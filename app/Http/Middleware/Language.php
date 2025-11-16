<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $locale = 'en';
            if (optional($request->user())->language) {
                $locale = $request->user()->language;
            } elseif (env('APP_INSTALLED') && function_exists('get_settings')) {
                $locale = get_settings('language');
            }

            app()->setlocale(session('language', ($locale ? $locale : 'en')));

            return $next($request);
        } catch (Exception $e) {
            return $next($request);
        }
    }
}
