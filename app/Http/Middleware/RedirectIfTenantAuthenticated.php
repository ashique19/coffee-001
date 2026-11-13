<?php

namespace App\Http\Middleware;

use App\Auth\AdminRouteAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfTenantAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AdminRouteAuth::resolveAuthenticatedUser($request) !== null) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
