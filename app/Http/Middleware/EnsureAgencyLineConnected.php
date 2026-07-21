<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyLineConnected
{
    public function handle(Request $request, Closure $next): Response
    {
        $agency = Auth::guard('agency')->user();

        if ($agency && ! $agency->line_uid) {
            return response()->view('agency.line_required', [], 403);
        }

        return $next($request);
    }
}
