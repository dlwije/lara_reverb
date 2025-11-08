<?php

namespace Modules\Cart\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Cart\Classes\ApiCart;
use Modules\Cart\Models\Cart;

class IdentifyCart
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // The service provider already handles cart creation
        // We just pass through and ensure identifier is returned
        $cart = app('apicart');

        $response = $next($request);

        // Always return the current cart identifier in response
        return $response->header('X-Cart-Identifier', $cart->getIdentifier());
    }



    protected function generateIdentifier()
    {
        return 'api_' . md5(uniqid('cart_', true));
    }
}
