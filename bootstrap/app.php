<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (
            Middleware $middleware
        ): void {

            /*
            |--------------------------------------------------------------------------
            | Middleware Aliases
            |--------------------------------------------------------------------------
            */

            $middleware->alias([
                'role' =>
                    RoleMiddleware::class,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Web Security Middleware
            |--------------------------------------------------------------------------
            |
            | Apply baseline security headers to every route using Laravel's
            | standard "web" middleware group.
            |
            */

            $middleware->appendToGroup(
                'web',
                [
                    SecurityHeadersMiddleware::class,
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Guest Redirect
            |--------------------------------------------------------------------------
            */

            $middleware->redirectGuestsTo(
                fn (
                    Request $request
                ) =>
                    route(
                        'login'
                    )
            );
        }
    )
    ->withExceptions(
        function (
            Exceptions $exceptions
        ): void {
            //
        }
    )
    ->create();