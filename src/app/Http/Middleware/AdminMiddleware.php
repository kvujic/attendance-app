<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    // admin_only for all admin page
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        // not login
        if (! $guard->check()) {
            return redirect()->route('admin.login');
        }

        $user = $guard->user();
        if ((int)($user->role ?? 0) !== 1) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => '管理者権限がありません']);
        }

        return $next($request);
    }
}
