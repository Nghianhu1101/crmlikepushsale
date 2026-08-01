<?php

namespace Webkul\Telesales\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Telesales\Models\SourceConnection;

class VerifyIncomingLeadToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $configuredToken = (string) config('telesales.api_token');
        $providedToken = (string) $request->bearerToken();

        if (
            $configuredToken !== ''
            && $providedToken !== ''
            && hash_equals($configuredToken, $providedToken)
        ) {
            return $next($request);
        }

        $connection = $providedToken !== ''
            ? SourceConnection::query()
                ->where('token_hash', hash('sha256', $providedToken))
                ->first()
            : null;

        if ($connection?->is_active) {
            $request->attributes->set('telesales_source_connection', $connection);

            return $next($request);
        }

        if ($connection) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token không hợp lệ hoặc nguồn data đã tạm dừng.',
            ], 401);
        }

        if ($configuredToken === '' && ! SourceConnection::query()->where('is_active', true)->exists()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Incoming Lead API chưa có nguồn data hoạt động.',
            ], 503);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'Token không hợp lệ hoặc nguồn data đã tạm dừng.',
        ], 401);
    }
}
