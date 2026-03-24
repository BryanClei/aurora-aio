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

        // Proxy strips Authorization header — remap from X-Auth-Token - PRODUCTION
        // if (!$request->bearerToken()) {
        //     $xAuthToken = $request->header('X-Auth-Token');

        //     if ($xAuthToken) {
        //         // Strip "Bearer " prefix if present, then re-set cleanly
        //         $token = str_starts_with($xAuthToken, 'Bearer ')
        //             ? $xAuthToken
        //             : 'Bearer ' . $xAuthToken;

        //         $request->headers->set('Authorization', $token);

        //         Log::info('Auth remapped from X-Auth-Token', [
        //             'token_preview' => substr($token, 0, 20) . '...'
        //         ]);
        //     }
        // }

        $this->authenticate($request, $guards);

        return $next($request);
    }
}
