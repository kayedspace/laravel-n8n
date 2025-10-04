<?php

namespace KayedSpace\N8n\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class N8nException extends Exception
{
    protected ?Response $response = null;

    protected array $context = [];

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, ?Response $response = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->context = $context;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getN8nErrorDetails(): ?array
    {
        if ($this->response) {
            return $this->response->json();
        }

        return null;
    }

    public static function fromResponse(Response $response, string $message = '', array $context = []): static
    {
        $errorMessage = $message ?: 'N8n API request failed';

        $body = $response->json();
        if (isset($body['message'])) {
            $errorMessage .= ': '.$body['message'];
        }

        return new static(
            $errorMessage,
            $response->status(),
            null,
            $response,
            $context
        );
    }
}
