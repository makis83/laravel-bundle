<?php

namespace Makis83\LaravelBundle\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Makis83\LaravelBundle\Exceptions\ExtendedException;

/**
 * Middleware for aborting further operations if user is authenticated.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-10-18
 * Time: 17:52
 */
class AbortIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request Request object
     * @param Closure(Request): (Response) $next Closure
     * @param null|string ...$guards Guards
     * @return Response Response
     * @throws ExtendedException If user is authenticated
     * @link https://stackoverflow.com/a/70739984/9653787
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Get guards
        $guardsArray = empty($guards) ? [null] : $guards;

        // Loop through guards and check if user is logged in
        foreach ($guardsArray as $guard) {
            if (Auth::guard($guard)->check()) {
                throw new ExtendedException(
                    403,
                    'This resource is forbidden for authenticated user.',
                );
            }
        }

        // Pass request to next middleware
        return $next($request);
    }
}
