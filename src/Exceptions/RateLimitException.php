<?php

namespace KayedSpace\N8n\Exceptions;

use Illuminate\Http\Client\Response;

class RateLimitException extends N8nException
{
    protected int $retryAfter = 0;

    public function setRetryAfter(int $seconds): self
    {
        $this->retryAfter = $seconds;

        return $this;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public static function fromResponse(Response $response, string $message = '', array $context = []): static
    {
        $exception = parent::fromResponse($response, $message ?: 'Rate limit exceeded', $context);

        if ($response->header('Retry-After')) {
            $exception->setRetryAfter((int) $response->header('Retry-After'));
        } elseif ($response->header('X-RateLimit-Reset')) {
            $resetTime = (int) $response->header('X-RateLimit-Reset');
            $exception->setRetryAfter(max(0, $resetTime - time()));
        }

        return $exception;
    }
}
