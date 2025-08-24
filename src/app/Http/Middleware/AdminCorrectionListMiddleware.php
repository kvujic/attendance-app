<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Admin\AdminCorrectionController;
use Illuminate\Console\View\Components\Component;
use Illuminate\Support\Facades\Log;

class AdminCorrectionListMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        //dd('AdminCorrectionList HIT');
        /*\Log::debug('AdminCorrectionList hit', [
            'uid' => Auth::id(),
            'authority' => Auth::check() ? (int) Auth::user()->authority : null,
        ]);
        */

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }
        // tale admin to use controller for admin
        if ((int) $user->role === 1) {
            $controller = app(AdminCorrectionController::class);
            $result = $controller->index($request);
            return $result instanceof \Symfony\Component\HttpFoundation\Response
                ? $result
                : response($result);
        }

        // use user's root controller if not admin
        return $next($request);
    }
}
