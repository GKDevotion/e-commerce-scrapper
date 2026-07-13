<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $start    = microtime(true);
        $response = $next($request);
        $elapsed  = (int) ((microtime(true) - $start) * 1000);

        // Log API calls for monitoring
        if ($request->is('api/*') && Auth::check()) {
            try {
                ApiLog::create([
                    'user_id'          => Auth::id(),
                    'service'          => 'internal_api',
                    'endpoint'         => $request->path(),
                    'method'           => $request->method(),
                    'status_code'      => $response->getStatusCode(),
                    'response_time_ms' => $elapsed,
                    'success'          => $response->getStatusCode() < 400,
                    'ip_address'       => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // Silently fail — don't break the request
            }
        }

        return $response;
    }
}
