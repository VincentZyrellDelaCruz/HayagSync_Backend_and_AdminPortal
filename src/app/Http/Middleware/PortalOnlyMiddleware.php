<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PortalOnlyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isStaff = $user?->staff()->exists();
        $isParent = $user?->parent_guardian()->exists();

        if (!$user || (!$isStaff && !$isParent)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'error' => 'Unauthorized access! Please log in with a valid HayagSync account.',
            ]);
        }

        return $next($request);
    }
}
