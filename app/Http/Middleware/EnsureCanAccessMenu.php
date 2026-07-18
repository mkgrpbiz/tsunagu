<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessMenu
{
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        if (! Auth::user()?->canAccessMenu($menuKey)) {
            abort(403, 'この操作は許可されていません。');
        }

        return $next($request);
    }
}
