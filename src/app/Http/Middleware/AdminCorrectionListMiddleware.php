<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Admin\AdminCorrectionController;

class AdminCorrectionListMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ((int) $user->role === 1) {
            $controller = app(AdminCorrectionController::class);
            $result = $controller->index($request);
            return $result instanceof Response
                ? $result
                : response($result);
        }

        return $next($request);
    }
}
