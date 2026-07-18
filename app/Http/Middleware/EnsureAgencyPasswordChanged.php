<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $agency = Auth::guard('agency')->user();

        if ($agency && $agency->must_change_password) {
            return redirect()
                ->route('agency.profile.edit')
                ->with('error', '初期パスワードのままです。続ける前にパスワードを変更してください。');
        }

        return $next($request);
    }
}
