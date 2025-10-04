<?php

namespace KayedSpace\N8n\Testing\Factories;

class CredentialFactory
{
    protected array $attributes = [];

    public static function make(array $attributes = []): array
    {
        return (new static)->withAttributes($attributes)->build();
    }

    public function withAttributes(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    public function withType(string $type): static
    {
        $this->attributes['type'] = $type;

        return $this;
    }

    public function withName(string $name): static
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function withData(array $data): static
    {
        $this->attributes['data'] = $data;

        return $this;
    }

    public function build(): array
    {
        return array_merge([
            'id' => 'cred_'.uniqid(),
            'name' => 'Test Credential '.rand(1, 1000),
            'type' => 'httpBasicAuth',
            'data' => [
                'user' => 'testuser',
                'password' => 'testpass',
            ],
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ], $this->attributes);
    }
}
