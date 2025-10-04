<?php

namespace KayedSpace\N8n\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use KayedSpace\N8n\Client\Webhook\Webhooks;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nWebhook
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $secret = null): Response
    {
        if (! Webhooks::verifySignature($request, $secret)) {
            abort(403, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
