<?php

namespace Webkul\Telesales\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyIncomingLeadToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $configuredToken = (string) config('telesales.api_token');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Incoming Lead API chưa được cấu hình token.',
            ], 503);
        }

        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        return $next($request);
    }
}
