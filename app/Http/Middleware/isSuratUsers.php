<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class isSuratUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $check = auth()->check();
        $staffOrWakil = (auth()->user()->is_staff or auth()->user()->is_wk);
        $isAdmin = auth()->user()->is_admin;

        if ($check and ($isAdmin or $staffOrWakil)) {
            return $next($request);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Forbidden access',
            ], 403);
        }
    }
}
