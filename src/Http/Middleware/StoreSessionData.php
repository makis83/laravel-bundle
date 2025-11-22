<?php

namespace Makis83\LaravelBundle\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for saving user data into sessions.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-10-18
 * Time: 17:53
 */
class StoreSessionData
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request Request object
     * @param Closure(Request): (Response) $next Closure
     * @return Response Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Save user data into session
        $request->session()->put([
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->userAgent(),
            'languages' => $request->getLanguages(),
            'last_activity' => time(),
            'url' => $request->header('Origin', $request->header('Referer'))
        ]);

        // Proceed next
        return $next($request);
    }
}
