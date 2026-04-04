<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectMobile
{
    // Map desktop paths to mobile equivalents
    private array $routeMap = [
        'dashboard'  => '/mobile',
        'attendance' => '/mobile/attendance',
        'leaves'     => '/mobile/leave',
        'overtimes'  => '/mobile/overtime',
        'profile'    => '/mobile/profile',
        'schedule'   => '/mobile/schedule',
        'login'      => '/mobile/login',
        ''           => '/mobile',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only redirect GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Skip Inertia AJAX requests
        if ($request->header('X-Inertia')) {
            return $next($request);
        }

        $path = $request->path();

        // Already on a mobile path — no redirect needed
        if (str_starts_with($path, 'mobile')) {
            return $next($request);
        }

        // Skip system/API paths
        if ($path === 'up' || str_starts_with($path, 'api/') || str_starts_with($path, 'sanctum/')) {
            return $next($request);
        }

        // Detect mobile user agent
        $userAgent = $request->header('User-Agent', '');
        if (!preg_match('/Mobile|Android|iPhone|iPad|Windows Phone/i', $userAgent)) {
            return $next($request);
        }

        // Find matching mobile route
        $mobilePath = '/mobile';
        foreach ($this->routeMap as $prefix => $target) {
            if ($prefix === '' ? $path === '' : str_starts_with($path, $prefix)) {
                $mobilePath = $target;
                break;
            }
        }

        return redirect($mobilePath);
    }
}
