<?php

use App\Http\Middleware\EnsureHasPagePermission;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'permission' => EnsureHasPagePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authorization errors (from policies) for web requests
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            // Only handle web requests, not API requests
            if ($request->expectsJson() && ! $request->inertia()) {
                return null; // Let default handler return JSON response
            }

            // Use custom message, replace default Laravel messages
            $originalMessage = $e->getMessage();
            $message = (! $originalMessage || $originalMessage === 'This action is unauthorized.')
                ? 'You do not have permission to access this page.'
                : $originalMessage;

            // Stay on current page, fallback to dashboard if no previous page
            $fallbackUrl = url()->previous() !== url()->current()
                ? url()->previous()
                : route('dashboard');

            session()->flash('error', $message);

            return redirect()->to($fallbackUrl);
        });

        // Handle 403 abort errors (from middleware) for web requests
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            // Only handle web requests, not API requests
            if ($request->expectsJson() && ! $request->inertia()) {
                return null; // Let default handler return JSON response
            }

            // Use custom message, replace default Laravel messages
            $originalMessage = $e->getMessage();
            $message = (! $originalMessage || $originalMessage === 'This action is unauthorized.')
                ? 'You do not have permission to access this page.'
                : $originalMessage;

            // Stay on current page, fallback to dashboard if no previous page
            $fallbackUrl = url()->previous() !== url()->current()
                ? url()->previous()
                : route('dashboard');

            session()->flash('error', $message);

            return redirect()->to($fallbackUrl);
        });
    })->create();
