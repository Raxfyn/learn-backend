<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $start = microtime(true);
        $status = 'PENDING';

        try {
            $response = $next($request);
            $status = (string) $response->getStatusCode();
            return $response;
        } catch (\Throwable $e) {
            $status = 'EXC:' . class_basename($e);
            throw $e;
        } finally {
            Log::info(sprintf(
                '%s %s -> %s (%.2fms)',
                $request->method(),
                $request->path(),
                $status,
                (microtime(true) - $start) * 1000
            ));
        }
    }
}
