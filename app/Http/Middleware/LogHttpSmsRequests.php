<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogHttpSmsRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        // Loguj request
        Log::channel('single')->info('=== httpSMS REQUEST ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        // Loguj response
        Log::channel('single')->info('=== httpSMS RESPONSE ===', [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ]);

        return $response;
    }
}
