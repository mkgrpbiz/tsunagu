<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::user();

        if ($admin && $admin->must_change_password) {
            return redirect()
                ->route('admin.profile.edit')
                ->with('error', '初期パスワードのままです。続ける前にパスワードを変更してください。');
        }

        return $next($request);
    }
}
