<?php

namespace KayedSpace\N8n\Testing\Factories;

class WorkflowFactory
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

    public function active(): static
    {
        $this->attributes['active'] = true;

        return $this;
    }

    public function inactive(): static
    {
        $this->attributes['active'] = false;

        return $this;
    }

    public function withName(string $name): static
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function withId(string $id): static
    {
        $this->attributes['id'] = $id;

        return $this;
    }

    public function withTags(array $tags): static
    {
        $this->attributes['tags'] = $tags;

        return $this;
    }

    public function build(): array
    {
        return array_merge([
            'id' => 'wf_'.uniqid(),
            'name' => 'Test Workflow '.rand(1, 1000),
            'active' => false,
            'nodes' => [
                [
                    'name' => 'Start',
                    'type' => 'n8n-nodes-base.start',
                    'position' => [250, 300],
                    'parameters' => [],
                ],
            ],
            'connections' => [],
            'settings' => [],
            'tags' => [],
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ], $this->attributes);
    }
}
