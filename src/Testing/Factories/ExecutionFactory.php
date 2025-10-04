<?php

namespace KayedSpace\N8n\Testing\Factories;

class ExecutionFactory
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

    public function success(): static
    {
        $this->attributes['status'] = 'success';
        $this->attributes['stoppedAt'] = now()->toIso8601String();

        return $this;
    }

    public function failed(): static
    {
        $this->attributes['status'] = 'error';
        $this->attributes['stoppedAt'] = now()->toIso8601String();

        return $this;
    }

    public function running(): static
    {
        $this->attributes['status'] = 'running';
        unset($this->attributes['stoppedAt']);

        return $this;
    }

    public function withWorkflow(string $workflowId): static
    {
        $this->attributes['workflowId'] = $workflowId;

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
            'id' => rand(1, 10000),
            'workflowId' => 'wf_'.uniqid(),
            'status' => 'success',
            'mode' => 'manual',
            'startedAt' => now()->subMinutes(5)->toIso8601String(),
            'stoppedAt' => now()->toIso8601String(),
            'data' => [
                'resultData' => [
                    'runData' => [],
                ],
            ],
        ], $this->attributes);
    }
}
