<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseFileSessionDuringInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // During installation, use file-based sessions instead of database
        // This prevents errors when the sessions table doesn't exist yet
        if (!file_exists(base_path('.env')) || !app()->environment('production')) {
            config(['session.driver' => 'file']);
        }

        return $next($request);
    }
}
