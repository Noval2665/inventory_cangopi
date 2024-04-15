<?php

namespace App\Http\Middleware;

use App\Helpers\CASL;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Can
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action, string $subject): Response
    {
        // check if user is admin from jwt token
        if (!CASL::can(Auth()->user()->role->permissions, $action, $subject)) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses untuk melakukan tindakan ini.',
            ], 401);
        }

        return $next($request);
    }
}
