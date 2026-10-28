<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response =
            $next(
                $request
            );


        /*
        |--------------------------------------------------------------------------
        | MIME-Type Protection
        |--------------------------------------------------------------------------
        |
        | Prevent browsers from attempting to guess the content type of a
        | response instead of respecting the declared Content-Type.
        |
        */

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );


        /*
        |--------------------------------------------------------------------------
        | Clickjacking Protection
        |--------------------------------------------------------------------------
        |
        | HeartScope pages may only be embedded by pages from the same origin.
        |
        */

        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );


        /*
        |--------------------------------------------------------------------------
        | Referrer Policy
        |--------------------------------------------------------------------------
        |
        | Prevent full internal URLs from being unnecessarily exposed when the
        | user navigates away from HeartScope.
        |
        */

        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );


        /*
        |--------------------------------------------------------------------------
        | Browser Feature Restrictions
        |--------------------------------------------------------------------------
        |
        | HeartScope currently does not require geolocation, camera access,
        | payment APIs or USB device access.
        |
        | Microphone is intentionally not disabled here so that a future
        | auscultation recording feature can be introduced without this
        | middleware unexpectedly blocking it.
        |
        */

        $response->headers->set(
            'Permissions-Policy',
            implode(
                ', ',
                [
                    'geolocation=()',
                    'camera=()',
                    'payment=()',
                    'usb=()',
                ]
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Cross-Domain Policy
        |--------------------------------------------------------------------------
        */

        $response->headers->set(
            'X-Permitted-Cross-Domain-Policies',
            'none'
        );


        /*
        |--------------------------------------------------------------------------
        | Legacy XSS Header
        |--------------------------------------------------------------------------
        |
        | Modern browsers primarily rely on CSP rather than X-XSS-Protection.
        | Setting it to 0 avoids enabling obsolete browser XSS filters that can
        | sometimes create their own security problems.
        |
        */

        $response->headers->set(
            'X-XSS-Protection',
            '0'
        );


        /*
        |--------------------------------------------------------------------------
        | HTTPS Strict Transport Security
        |--------------------------------------------------------------------------
        |
        | HSTS must never be forced during normal Laragon HTTP development.
        | It is therefore enabled only when HeartScope is running in production
        | and the current request is already using HTTPS.
        |
        */

        if (
            app()->environment(
                'production'
            )
            &&
            $request->isSecure()
        ) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }


        return $response;
    }
}