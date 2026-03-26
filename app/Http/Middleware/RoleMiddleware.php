<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$allowedUserTypes
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$allowedUserTypes): Response
    {
        $user = auth()->user();
        
        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // Convert string parameters to integers for comparison
        $allowedTypes = array_map('intval', $allowedUserTypes);
        
        // Check if user's role is in the allowed types
        if (!in_array($user->role, $allowedTypes)) {
            // For AJAX requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized access. You do not have permission to access this resource.'
                ], 403);
            }
            
            // For regular requests, abort with 403 or redirect to dashboard
            abort(403, 'Unauthorized access. You do not have permission to access this resource.');
        }

        return $next($request);
    }
}