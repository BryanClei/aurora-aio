<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if auth cookie exists and set it as Authorization header
        if ($request->cookie("authcookie")) {
            $request->headers->set(
                "Authorization",
                "Bearer " . $request->cookie("authcookie")
            );
        }

        $this->authenticate($request, $guards);

        return $next($request);
    }
}
