<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DivisionMiddleware
 *
 * Restricts access to routes based on the user's assigned division.
 * Admin users (role=0) bypass division checks and can access all divisions.
 * Other users must belong to one of the allowed divisions (by slug).
 */
class DivisionMiddleware
{
    public function handle(Request $request, Closure $next, ...$allowedSlugs): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin bypasses all division checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        // If no allowed slugs specified, allow any authenticated user
        if (empty($allowedSlugs)) {
            return $next($request);
        }

        // User must have a division assigned
        if (!$user->division_id || !$user->division) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No division assigned to this user.'], 403);
            }
            abort(403, 'No division assigned to this user.');
        }

        // Check if user's division slug is in the allowed list
        if (!in_array($user->division->slug, $allowedSlugs)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied for your division.'], 403);
            }
            abort(403, 'Access denied for your division.');
        }

        return $next($request);
    }
}
