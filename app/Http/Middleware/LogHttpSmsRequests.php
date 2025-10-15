<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogHttpSmsRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $logFile = storage_path('logs/httpsms-requests.log');
        
        // Zbierz dane o requeście
        $logEntry = str_repeat('=', 80) . "\n";
        $logEntry .= "REQUEST at " . date('Y-m-d H:i:s') . "\n";
        $logEntry .= str_repeat('=', 80) . "\n";
        $logEntry .= "Method: " . $request->method() . "\n";
        $logEntry .= "URL: " . $request->fullUrl() . "\n";
        $logEntry .= "Path: " . $request->path() . "\n";
        $logEntry .= "IP: " . $request->ip() . "\n";
        $logEntry .= "\nHEADERS:\n";
        foreach ($request->headers->all() as $key => $values) {
            $logEntry .= "  $key: " . implode(', ', $values) . "\n";
        }
        $logEntry .= "\nBODY:\n" . $request->getContent() . "\n";
        $logEntry .= "\nPARSED PARAMS:\n" . print_r($request->all(), true);
        
        // Zapisz do pliku (z @suppress dla uprawnień)
        @file_put_contents($logFile, $logEntry, FILE_APPEND);

        $response = $next($request);

        // Loguj response
        $responseLog = "\nRESPONSE:\n";
        $responseLog .= "Status: " . $response->getStatusCode() . "\n";
        $responseLog .= "Content: " . substr($response->getContent(), 0, 500) . "\n";
        $responseLog .= str_repeat('=', 80) . "\n\n";
        
        @file_put_contents($logFile, $responseLog, FILE_APPEND);

        return $response;
    }
}
