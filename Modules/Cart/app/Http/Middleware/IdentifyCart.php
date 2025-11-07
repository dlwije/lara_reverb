<?php

namespace Modules\Cart\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Cart\Classes\ApiCart;

class IdentifyCart
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get cart identifier from header, token, or generate new one
        $identifier = $request->header('X-Cart-Identifier')
            ?: $request->input('cart_identifier')
                ?: $this->generateIdentifier();

        // Initialize cart with identifier
        app()->singleton('apicart', function () use ($identifier) {
            return new ApiCart($identifier);
        });

        $response = $next($request);

        // Add cart identifier to response for client to store
        return $response->header('X-Cart-Identifier', $identifier);
    }

    protected function generateIdentifier()
    {
        return 'api_' . md5(uniqid('cart_', true));
    }
}
