<?php

namespace Makis83\LaravelBundle\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for adding execution time into request headers.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-10-29
 * Time: 22:30
 */
class AddExecutionTime
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request Request object
     * @param Closure(Request): (Response) $next Closure
     * @param bool $logQuery Whether to log query
     * @return Response Response
     */
    public function handle(Request $request, Closure $next, bool $logQuery = false): Response
    {
        // Mark start time
        $startTime = microtime(true);

        // Handle request
        $response = $next($request);

        // Calculate execution time
        $executionTime = microtime(true) - $startTime;

        // Add execution time to response headers
        $response->headers->set('X-Execution-Time', round($executionTime, 6) . ' seconds');

        // Log execution time
        if ($logQuery) {
            Log::info('API Request Performance', [
                'uri' => $request->getUri(),
                'method' => $request->getMethod(),
                'execution_time' => round($executionTime, 6),
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);
        }

        return $response;
    }
}
