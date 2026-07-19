<?php

use App\Http\Middleware\EnsureAgencyApproved;
use App\Http\Middleware\EnsureAgencyConsentsSubmitted;
use App\Http\Middleware\EnsureAgencyPasswordChanged;
use App\Http\Middleware\EnsureCanAccessMenu;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('agency/*') ? route('agency.login') : route('admin.login')
        );

        $middleware->alias([
            'agency.password_changed' => EnsureAgencyPasswordChanged::class,
            'agency.approved' => EnsureAgencyApproved::class,
            'agency.consents_submitted' => EnsureAgencyConsentsSubmitted::class,
            'menu' => EnsureCanAccessMenu::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'apply/*',
            'line/webhook',
            'debug-log',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
