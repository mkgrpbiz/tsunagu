<?php

namespace App\Http\Middleware;

use App\Enums\AgencyStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $agency = Auth::guard('agency')->user();

        if ($agency && $agency->status !== AgencyStatus::Approved) {
            return response()->view('agency.restricted', ['agency' => $agency], 403);
        }

        return $next($request);
    }
}
