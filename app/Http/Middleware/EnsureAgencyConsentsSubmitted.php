<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyConsentsSubmitted
{
    public function handle(Request $request, Closure $next): Response
    {
        $agency = Auth::guard('agency')->user();

        if ($agency && ! $agency->hasSubmittedAllConsents()) {
            return redirect()
                ->route('agency.additional-info.edit')
                ->with('error', 'ご利用の前に、契約書類へのご同意をお願いします。');
        }

        return $next($request);
    }
}
