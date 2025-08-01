<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if ($request->cookie('authcookie')) {
            $request->headers->set(
                'Authorization',
                'Bearer ' . $request->cookie('authcookie')
            );
        }

        $this->authenticate($request, $guards);

        return $next($request);
    }
}
