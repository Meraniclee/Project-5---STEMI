<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response|RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Authentication Check
        |--------------------------------------------------------------------------
        */

        $user =
            $request->user();


        if (
            !$user
        ) {
            return redirect()
                ->route(
                    'login'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Active Account Enforcement
        |--------------------------------------------------------------------------
        |
        | This check runs on every protected role route. Therefore, if an
        | Administrator deactivates a Doctor or Consultant while that user is
        | already logged in, their authenticated session will stop working on
        | the next protected request.
        |
        */

        if (
            !$user->isActive()
        ) {
            $this->terminateSession(
                $request
            );


            return redirect()
                ->route(
                    'login'
                )
                ->with(
                    'error',
                    'Your account is currently inactive. Please contact the administrator.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Route Role Configuration
        |--------------------------------------------------------------------------
        */

        abort_if(
            empty(
                $roles
            ),
            403,
            'This section does not have an authorized role configured.'
        );


        /*
        |--------------------------------------------------------------------------
        | Role Authorization
        |--------------------------------------------------------------------------
        */

        abort_unless(
            in_array(
                $user->role,
                $roles,
                true
            ),
            403,
            'You are not authorized to access this section.'
        );


        return $next(
            $request
        );
    }


    /**
     * Fully terminate an authenticated session.
     */
    private function terminateSession(
        Request $request
    ): void {
        Auth::logout();


        $request
            ->session()
            ->invalidate();


        $request
            ->session()
            ->regenerateToken();
    }
}