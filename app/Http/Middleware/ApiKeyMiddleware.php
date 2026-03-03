<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Essa\APIToolKit\Api\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $encryptedApiKey =
            $request->header('API-Key') ??
            $request->header('api_key');

        if ($request->header('api_key') !== "hello world!") {
            try {
                $apiKey = decrypt($encryptedApiKey);
            } catch (\Exception $e) {
                return $this->responseUnAuthenticated(
                    "Invalid API Key",
                    "Unauthorized"
                );
            }
        } else {
            $apiKey = $encryptedApiKey;
        }

        if (!in_array($apiKey, config('app.api_key'))) {
            return $this->responseUnAuthenticated(
                "Invalid API Key",
                "Unauthorized"
            );
        }

        return $next($request);
    }
}
