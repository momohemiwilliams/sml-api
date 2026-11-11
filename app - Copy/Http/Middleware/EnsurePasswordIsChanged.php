<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->must_change_password) {
            // Allow logout and change password endpoints
            if ($request->is('api/logout') || $request->is('api/change-initial-password')) {
                return $next($request);
            }
            
            return response()->json([
                'message' => 'Password change required.',
                'require_change_password' => true
            ], 403);
        }

        return $next($request);
    }
}
