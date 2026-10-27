<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Maximum failed login attempts before
     * temporary throttling begins.
     */
    private const MAX_ATTEMPTS = 5;


    /**
     * Lockout duration in seconds.
     */
    private const DECAY_SECONDS = 60;


    /**
     * Display the HeartScope login page.
     */
    public function showLoginForm(
        Request $request
    ): View|RedirectResponse {
        if (
            Auth::check()
        ) {
            return redirect()
                ->route(
                    'dashboard'
                );
        }


        return view(
            'auth.login'
        );
    }


    /**
     * Authenticate the user.
     */
    public function login(
        Request $request
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Already Authenticated
        |--------------------------------------------------------------------------
        */

        if (
            Auth::check()
        ) {
            return redirect()
                ->route(
                    'dashboard'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Login Request
        |--------------------------------------------------------------------------
        */

        $credentials =
            $request->validate(
                [
                    'email' => [
                        'required',
                        'email',
                        'max:255',
                    ],

                    'password' => [
                        'required',
                        'string',
                        'max:4096',
                    ],
                ],
                [
                    'email.required' =>
                        'Please enter your email address.',

                    'email.email' =>
                        'Please enter a valid email address.',

                    'password.required' =>
                        'Please enter your password.',

                    'password.max' =>
                        'The password input is too long.',
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | Normalize Email Address
        |--------------------------------------------------------------------------
        */

        $email =
            Str::lower(
                trim(
                    $credentials[
                        'email'
                    ]
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Login Throttling
        |--------------------------------------------------------------------------
        */

        $throttleKey =
            $this->throttleKey(
                $email,
                $request
            );


        if (
            RateLimiter::tooManyAttempts(
                $throttleKey,
                self::MAX_ATTEMPTS
            )
        ) {
            $seconds =
                RateLimiter::availableIn(
                    $throttleKey
                );


            throw ValidationException::withMessages([
                'email' =>
                    'Too many login attempts. Please try again in '
                    . $seconds
                    . ' second(s).',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Remember Me
        |--------------------------------------------------------------------------
        */

        $remember =
            $request->boolean(
                'remember'
            );


        /*
        |--------------------------------------------------------------------------
        | Authenticate Active Account
        |--------------------------------------------------------------------------
        */

        $authenticated =
            Auth::attempt(
                [
                    'email' =>
                        $email,

                    'password' =>
                        $credentials[
                            'password'
                        ],

                    'status' =>
                        'active',
                ],
                $remember
            );


        /*
        |--------------------------------------------------------------------------
        | Failed Login
        |--------------------------------------------------------------------------
        */

        if (
            !$authenticated
        ) {
            RateLimiter::hit(
                $throttleKey,
                self::DECAY_SECONDS
            );


            return back()
                ->withErrors([
                    'email' =>
                        'The email address or password is incorrect, or the account is inactive.',
                ])
                ->onlyInput(
                    'email'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Successful Login
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear(
            $throttleKey
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Session Fixation
        |--------------------------------------------------------------------------
        */

        $request
            ->session()
            ->regenerate();


        /*
        |--------------------------------------------------------------------------
        | Update Login Activity
        |--------------------------------------------------------------------------
        */

        $user =
            Auth::user();


        /*
        |--------------------------------------------------------------------------
        | Defensive Active Check
        |--------------------------------------------------------------------------
        |
        | Auth::attempt() already requires status=active, but this protects
        | against unexpected account state changes during authentication.
        |
        */

        if (
            !$user
            ||
            !$user->isActive()
        ) {
            Auth::logout();


            $request
                ->session()
                ->invalidate();


            $request
                ->session()
                ->regenerateToken();


            return redirect()
                ->route(
                    'login'
                )
                ->with(
                    'error',
                    'Your account is currently inactive. Please contact the administrator.'
                );
        }


        $user->forceFill([
            'last_login_at' =>
                now(),
        ])->save();


        /*
        |--------------------------------------------------------------------------
        | Continue to Intended Page
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->intended(
                route(
                    'dashboard'
                )
            );
    }


    /**
     * Log the user out.
     */
    public function logout(
        Request $request
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Terminate Authentication Session
        |--------------------------------------------------------------------------
        */

        Auth::logout();


        /*
        |--------------------------------------------------------------------------
        | Destroy Existing Session
        |--------------------------------------------------------------------------
        */

        $request
            ->session()
            ->invalidate();


        /*
        |--------------------------------------------------------------------------
        | Generate Fresh CSRF Token
        |--------------------------------------------------------------------------
        */

        $request
            ->session()
            ->regenerateToken();


        return redirect()
            ->route(
                'login'
            )
            ->with(
                'success',
                'You have been signed out successfully.'
            );
    }


    /**
     * Build a non-sensitive throttle identifier.
     *
     * The email address is hashed so that the cache key
     * does not contain the plain-text account email.
     */
    private function throttleKey(
        string $email,
        Request $request
    ): string {
        $ip =
            (string)
            $request->ip();


        return
            'heartscope-login:'
            . hash(
                'sha256',
                $email
                . '|'
                . $ip
            );
    }
}