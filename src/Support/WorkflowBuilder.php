<?php

namespace KayedSpace\N8n\Support;

use KayedSpace\N8n\Facades\N8nClient;

class WorkflowBuilder
{
    protected string $name;

    protected array $nodes = [];

    protected array $connections = [];

    protected array $settings = [];

    protected bool $active = false;

    protected ?string $projectId = null;

    protected array $tags = [];

    public static function create(string $name): static
    {
        return (new static)->setName($name);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function activate(): static
    {
        $this->active = true;

        return $this;
    }

    public function deactivate(): static
    {
        $this->active = false;

        return $this;
    }

    public function project(string $projectId): static
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function tags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function trigger(string $type, array $parameters = []): static
    {
        $this->nodes[] = [
            'name' => ucfirst($type).' Trigger',
            'type' => 'n8n-nodes-base.'.$type,
            'typeVersion' => 1,
            'position' => [count($this->nodes) * 250, 300],
            'parameters' => $parameters,
        ];

        return $this;
    }

    public function node(string $type, array $parameters = [], ?string $name = null): static
    {
        $nodeName = $name ?? ucfirst($type);

        $this->nodes[] = [
            'name' => $nodeName,
            'type' => str_starts_with($type, 'n8n-nodes-base.') ? $type : 'n8n-nodes-base.'.$type,
            'typeVersion' => 1,
            'position' => [count($this->nodes) * 250, 300],
            'parameters' => $parameters,
        ];

        // Auto-connect to previous node
        if (count($this->nodes) > 1) {
            $previousNode = $this->nodes[count($this->nodes) - 2]['name'];
            $this->connect($previousNode, $nodeName);
        }

        return $this;
    }

    public function connect(string $fromNode, string $toNode, int $fromIndex = 0, int $toIndex = 0): static
    {
        if (! isset($this->connections[$fromNode])) {
            $this->connections[$fromNode] = ['main' => []];
        }

        if (! isset($this->connections[$fromNode]['main'][$fromIndex])) {
            $this->connections[$fromNode]['main'][$fromIndex] = [];
        }

        $this->connections[$fromNode]['main'][$fromIndex][] = [
            'node' => $toNode,
            'type' => 'main',
            'index' => $toIndex,
        ];

        return $this;
    }

    public function settings(array $settings): static
    {
        $this->settings = array_merge($this->settings, $settings);

        return $this;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'nodes' => $this->nodes,
            'connections' => $this->connections,
            'active' => $this->active,
            'settings' => $this->settings,
            'tags' => $this->tags,
        ];
    }

    public function save(): array
    {
        $workflow = N8nClient::workflows()->create($this->toArray());

        // Set project if specified
        if ($this->projectId && is_array($workflow)) {
            $workflowId = $workflow['id'] ?? null;
            if ($workflowId) {
                N8nClient::workflows()->transfer($workflowId, $this->projectId);
            }
        }

        return is_array($workflow) ? $workflow : $workflow->toArray();
    }

    public function saveAndActivate(): array
    {
        $this->activate();

        return $this->save();
    }
}
