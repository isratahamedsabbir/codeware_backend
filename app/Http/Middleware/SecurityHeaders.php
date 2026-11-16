<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // A per-request nonce: Vite stamps it on the tags it emits, and views
        // can use $cspNonce on their own inline <script>/<style>.
        $nonce = base64_encode(Str::random(16));
        Vite::useCspNonce($nonce);
        View::share('cspNonce', $nonce);

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', config('security.headers.permissions_policy'));

        // Back-office and sign-in screens never belong in a search index. The
        // meta tag in the layouts covers HTML; this header covers everything
        // else (redirects, Livewire/JSON responses, error pages).
        if ($this->isPrivateArea($request)) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        if (! app()->environment(['local', 'testing']) && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', config('security.headers.hsts'));
        }

        if (config('security.csp.enabled')) {
            $name = config('security.csp.report_only')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $headers->set($name, $this->policy($nonce));
        }

        return $response;
    }

    /**
     * Admin / vendor / delivery hosts in full, plus the sign-in pages and the
     * legacy /admin prefix on the main host.
     */
    private function isPrivateArea(Request $request): bool
    {
        $host = $request->getHost();

        foreach (['admin_host', 'vendor_host', 'delivery_host'] as $key) {
            $panelHost = config('app.'.$key);

            if (is_string($panelHost) && $panelHost !== '' && $panelHost === $host) {
                return true;
            }
        }

        return $request->is(
            'admin', 'admin/*',
            'login', 'register', 'logout',
            'forgot-password', 'reset-password', 'reset-password/*',
            'two-factor-challenge', 'confirm-password', 'mfa-required',
            'email/verify', 'email/verify/*',
            'user/*',
        );
    }

    private function policy(string $nonce): string
    {
        $cfg = config('security.csp');
        $dev = app()->isLocal() ? ['http://localhost:*', 'http://127.0.0.1:*', 'http://[::1]:*', 'ws://localhost:*', 'ws://127.0.0.1:*', 'ws://[::1]:*'] : [];
        $ws = ['wss:'];

        $directives = [
            'default-src' => ["'self'"],
            // Alpine's standard build evaluates expressions, hence unsafe-eval;
            // inline scripts are only allowed with the nonce.
            'script-src' => ["'self'", "'nonce-{$nonce}'", "'unsafe-eval'", ...$cfg['script_src'], ...$dev],
            'style-src' => ["'self'", "'unsafe-inline'", ...$cfg['style_src'], ...$dev],
            'font-src' => ["'self'", 'data:', ...$cfg['font_src']],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'connect-src' => ["'self'", ...$ws, ...$cfg['connect_src'], ...$dev],
            'frame-src' => ["'self'", ...$cfg['frame_src']],
            'frame-ancestors' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $name) => $name.' '.implode(' ', array_unique($sources)))
            ->implode('; ');

        if ($cfg['report_uri']) {
            $policy .= '; report-uri '.$cfg['report_uri'];
        }

        return $policy;
    }
}
