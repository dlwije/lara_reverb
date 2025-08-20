<?php

namespace App\Http\Middleware;

use App\Models\Sma\Pos\Register;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OpenRegister
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $register_id = $request->session()->get('open_register_id', false);
        $register = $register_id ? Register::where('id', $register_id)->exists() : false;
        if((! $register_id || !$register) && ! $request->user()->openedRegister) {
            $request->session()->flash('open_register', true);
            $request->session()->flash('error', __('Please open a register first!'));
        }

        return $next($request);
    }
}
