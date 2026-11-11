<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ApiBasicAuthMiddleware
{
    // The email of the existing system user to impersonate when authenticated
    private const SYSTEM_USER_EMAIL = 'admin@fmdqgroup.com';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for Authorization header
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Basic ')) {
            return response()->json(['message' => 'Authorization header missing'], 401, ['WWW-Authenticate' => 'Basic']);
        }

        // Decode credentials
        $credentials = base64_decode(substr($header, 6));
        
        if (!str_contains($credentials, ':')) {
             return response()->json(['message' => 'Invalid credentials format'], 401, ['WWW-Authenticate' => 'Basic']);
        }

        list($username, $password) = explode(':', $credentials, 2);

        // Get credentials from config
        $configUsername = config('api.basic_auth.username');
        $configPassword = config('api.basic_auth.password');

        if (!$configUsername || !$configPassword) {
            return response()->json(['message' => 'Server configuration error: API credentials not set.'], 500);
        }

        // Validate against Config Credentials
        if ($username !== $configUsername || $password !== $configPassword) {
            return response()->json(['message' => 'Invalid API credentials'], 401, ['WWW-Authenticate' => 'Basic']);
        }

        // User logic removed as requested.
        // The request continues without a logged-in Laravel user context.


        return $next($request);
    }
}
