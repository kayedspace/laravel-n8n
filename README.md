# Laravel N8N: A Fluent Client for n8n Automation Workflows

A complete, expressive, and fluent Laravel client for the n8n public REST API and Webhooks Triggering, empowering Laravel
developers to interact
seamlessly with n8n webhooks, workflows, executions, credentials, tags, users, variables, projects, source control
operations and more.

## Table of Contents

* [📦 Installation](#-installation)
* [⚙️ Configuration](#-configuration)
* [🚀 Quick Start](#-quick-start)
* [⚡ Webhooks](#-webhooks)
* [🏗️ Workflow Builder](#-workflow-builder)
* [📚 API Resources](#-api-resources)
    * [🕵 Audit](#-audit)
    * [🔑 Credentials](#-credentials)
    * [⏯️ Executions](#-executions)
    * [🚧 Projects](#-projects)
    * [📝 Source Control](#-source-control)
    * [🏷️ Tags](#-tags)
    * [🙍 Users](#-users)
    * [🔠 Variables](#-variables)
    * [🔄 Workflows](#-workflows)
* [🔧 Advanced Features](#-advanced-features)
    * [📊 Events System](#-events-system)
    * [💾 Response Caching](#-response-caching)
    * [📝 Logging](#-logging)
    * [🔌 Client Modifiers](#-client-modifiers)
    * [🔧 Macros](#-macros)
    * [⚠️ Exception Handling](#-exception-handling)
* [🏥 Health Checks](#-health-checks)
* [🧪 Testing](#-testing)
* [🎨 CLI Commands](#-cli-commands)
* [🤝 Contributing](#-contributing)
* [🛠 Support](#-support)
* [📄 License](#-license)

## 📦 Installation

Install via Composer:

```bash
composer require kayedspace/laravel-n8n
```

Service providers and facades are auto-discovered by Laravel.

## ⚙️ Configuration

Publish and customize the configuration file:

```bash
php artisan vendor:publish --tag=n8n-config
```

Set environment variables in .env:

```dotenv
N8N_TIMEOUT=120
N8N_THROW=true
N8N_RETRY=3

N8N_API_BASE_URL=https://your-n8n-instance.com/api/v1
N8N_API_KEY=your_api_key

N8N_WEBHOOK_BASE_URL=https://your-n8n-instance.com/webhook
N8N_WEBHOOK_USERNAME=your_webhook_username
N8N_WEBHOOK_PASSWORD=your_webhook_password
```

## 🚀 Quick Start

```php
use KayedSpace\N8n\Facades\N8nClient;

// Trigger webhook
$webhookTrigger = N8nClient::webhooks()->request("path-to-webhook", $payload);

// List all workflows
$workflows = N8nClient::workflows()->list();

// Retrieve execution status with data
$status = N8nClient::executions()->get($execution['id'], includeData: true);
```

## ⚡ Webhooks

The Webhooks client enables triggering n8n workflow webhooks with support for multiple HTTP methods, authentication, async processing, and signature verification.

### Basic Usage

```php
// Trigger a webhook
$response = N8nClient::webhooks()->request("path-to-webhook", $payload);

// Custom basic auth (overrides .env credentials)
$response = N8nClient::webhooks()
    ->withBasicAuth("username", "password")
    ->request("path-to-webhook", $payload);

// Without authentication
$response = N8nClient::webhooks()
    ->withoutBasicAuth()
    ->request("path-to-webhook", $payload);
```

> Basic auth is applied by default if `N8N_WEBHOOK_USERNAME` and `N8N_WEBHOOK_PASSWORD` are set in .env.

### Async Webhook Triggering

Queue webhook triggers for background processing:

```php
// Queue the webhook (returns immediately)
N8nClient::webhooks()->async()->request('/my-webhook', $data);

// Force synchronous (default)
N8nClient::webhooks()->sync()->request('/my-webhook', $data);

> Configure queue behaviour via `N8N_QUEUE_ENABLED`, `N8N_QUEUE_CONNECTION`, and `N8N_QUEUE_NAME` in your `.env` file.
```

### Webhook Signature Verification

Secure incoming n8n webhooks with HMAC verification:

```php
// Set signature key in .env: N8N_WEBHOOK_SIGNATURE_KEY=your_secret

// Apply middleware to routes
Route::post('/n8n/webhook', WebhookController::class)
    ->middleware('n8n.webhook');

// Manual verification
use KayedSpace\N8n\Client\Webhook\Webhooks;

if (Webhooks::verifySignature($request)) {
    // Valid signature - process webhook
}
```

## 🏗️ Workflow Builder

Build n8n workflows programmatically using a fluent, expressive DSL:

```php
use KayedSpace\N8n\Support\WorkflowBuilder;

$workflow = WorkflowBuilder::create('Customer Onboarding')
    ->trigger('webhook', ['path' => 'customer-created'])
    ->node('httpRequest', [
        'url' => 'https://api.example.com/customer/{{$json.id}}',
        'method' => 'GET',
    ])
    ->node('emailSend', [
        'toEmail' => '{{$json.email}}',
        'subject' => 'Welcome!',
    ])
    ->activate()
    ->saveAndActivate();

// Build without activation
$workflow = WorkflowBuilder::create('Data Pipeline')
    ->trigger('schedule', ['rule' => '0 0 * * *'])
    ->node('httpRequest', ['url' => 'https://api.example.com/data'])
    ->node('set', ['values' => ['processed' => true]])
    ->save();
```

## 📚 API Resources

Below is an exhaustive reference covering every resource and method provided.

### 🕵 Audit

| Method                                    | HTTP Method & Path | Description                                                          |
|-------------------------------------------|--------------------|----------------------------------------------------------------------|
| `generate(array $additionalOptions = [])` | `POST /audit`      | Generate a full audit report based on optional categories or filters |

**Description:**
This endpoint performs a security audit of your n8n instance and returns diagnostics grouped by category. It must be
invoked by an account with owner privileges.

### 🔑 Credentials

| Method Signature                                     | HTTP Method & Path                   | Description                                            |
|------------------------------------------------------|--------------------------------------|--------------------------------------------------------|
| `create(array $payload)`                             | `POST /credentials`                  | Create a credential using the appropriate type schema. |
| `list(array $filters = [])`                          | `GET /credentials`                   | List stored credentials with optional pagination.      |
| `all(array $filters = [])`                           | `GET /credentials`                   | Auto-paginate and retrieve all credentials.            |
| `listIterator(array $filters = [])`                  | `GET /credentials`                   | Memory-efficient generator for iterating credentials.  |
| `get(string $id)`                                    | `GET /credentials/{id}`              | Retrieve details of a specific credential by ID.       |
| `delete(string $id)`                                 | `DELETE /credentials/{id}`           | Delete a credential permanently.                       |
| `schema(string $typeName)`                           | `GET /credentials/schema/{typeName}` | Get the schema definition for a credential type.       |
| `transfer(string $id, string $destinationProjectId)` | `PUT /credentials/{id}/transfer`     | Move a credential to another project using its ID.     |

**Examples:**

```php
// Get credential schema
$schema = N8nClient::credentials()->schema('slackApi');

// Create a credential
N8nClient::credentials()->create([
    'name' => 'Slack Token',
    'type' => 'slackApi',
    'data' => [
        'token' => 'xoxb-123456789',
    ]
]);

// Get all credentials
$allCredentials = N8nClient::credentials()->all();
```

### ⏯️ Executions

| Method Signature                                                   | HTTP Method & Path        | Description                                                                                                                                       |
|--------------------------------------------------------------------|---------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `list(array $filters = [])`                                        | `GET /executions`         | Retrieve a paginated list of workflow executions. Supports filters such as `status`, `workflowId`, `projectId`, `includeData`, `limit`, `cursor`. |
| `all(array $filters = [])`                                         | `GET /executions`         | Auto-paginate and retrieve all executions matching filters.                                                                                       |
| `listIterator(array $filters = [])`                                | `GET /executions`         | Memory-efficient generator for iterating through all executions.                                                                                  |
| `get(int $id, bool $includeData = false)`                          | `GET /executions/{id}`    | Retrieve detailed information for a specific execution. Optionally include execution data.                                                        |
| `wait(int $id, int $timeout = 60, int $interval = 2)`              | `GET /executions/{id}`    | Poll an execution until completion or timeout. Throws `ExecutionFailedException` on failure.                                                      |
| `delete(int $id)`                                                  | `DELETE /executions/{id}` | Delete an execution record by ID.                                                                                                                 |
| `deleteMany(array $ids)`                                           | `DELETE /executions/{id}` | Delete multiple executions. Returns results array with success/error for each ID.                                                                 |

**Examples:**

```php
// List executions with pagination
$executions = N8nClient::executions()->list(['status' => 'success', 'limit' => 50]);

// Get all executions (auto-paginate)
$allExecutions = N8nClient::executions()->all(['workflowId' => 'wf1']);

// Memory-efficient iteration
foreach (N8nClient::executions()->listIterator(['status' => 'error']) as $execution) {
    // Process one at a time
}

// Get detailed execution data
$execution = N8nClient::executions()->get(101, includeData: true);

// Wait for execution to complete
$result = N8nClient::executions()->wait($executionId, timeout: 120, interval: 3);

// Batch delete
$results = N8nClient::executions()->deleteMany([101, 102, 103]);
```

### 🚧 Projects

| Method Signature                                                  | HTTP Method & Path                            | Description                                                            |
|-------------------------------------------------------------------|-----------------------------------------------|------------------------------------------------------------------------|
| `create(array $payload)`                                          | `POST /projects`                              | Create a new project with name, description, etc.                      |
| `list(array $filters = [])`                                       | `GET /projects`                               | Retrieve a paginated list of projects.                                 |
| `all(array $filters = [])`                                        | `GET /projects`                               | Auto-paginate and retrieve all projects.                               |
| `listIterator(array $filters = [])`                               | `GET /projects`                               | Memory-efficient generator for iterating through all projects.         |
| `update(string $projectId, array $payload)`                       | `PUT /projects/{projectId}`                   | Update project name or metadata. Returns 204 No Content on success.    |
| `delete(string $projectId)`                                       | `DELETE /projects/{projectId}`                | Delete a project by ID. Returns 204 No Content on success.             |
| `addUsers(string $projectId, array $relations)`                   | `POST /projects/{projectId}/users`            | Add users to a project with specified roles via the `relations` array. |
| `changeUserRole(string $projectId, string $userId, string $role)` | `PATCH /projects/{projectId}/users/{userId}`  | Change the role of an existing user within a project.                  |
| `removeUser(string $projectId, string $userId)`                   | `DELETE /projects/{projectId}/users/{userId}` | Remove a user from a project.                                          |

**Examples:**

```php
// Create a project
$project = N8nClient::projects()->create(['name' => 'DevOps', 'description' => 'CI/CD flows']);

// Get all projects
$allProjects = N8nClient::projects()->all();

// Add users
N8nClient::projects()->addUsers($project['id'], [
  ['userId' => 'abc123', 'role' => 'member'],
]);

// Promote user role
N8nClient::projects()->changeUserRole($project['id'], 'abc123', 'admin');

// Delete the project
N8nClient::projects()->delete($project['id']);
```

### 📝 Source Control

| Method Signature       | HTTP Method & Path          | Description                                                              |
|------------------------|-----------------------------|--------------------------------------------------------------------------|
| `pull(array $payload)` | `POST /source-control/pull` | Trigger a pull operation from the connected Git source for all projects. |

**Example:**

```php
$syncStatus = N8nClient::sourceControl()->pull([
    'projectIds' => ['project-1', 'project-2'],
]);
```

> Requires source control integration to be configured in the n8n instance.

### 🏷️ Tags

| Method Signature                     | HTTP Method & Path  | Description                                                |
|--------------------------------------|---------------------|------------------------------------------------------------|
| `create(array $payload)`             | `POST /tags`        | Create a new tag with the given name or properties.        |
| `list(array $filters = [])`          | `GET /tags`         | List all tags with optional pagination using limit/cursor. |
| `all(array $filters = [])`           | `GET /tags`         | Auto-paginate and retrieve all tags.                       |
| `listIterator(array $filters = [])`  | `GET /tags`         | Memory-efficient generator for iterating through all tags. |
| `get(string $id)`                    | `GET /tags/{id}`    | Retrieve a single tag by its ID.                           |
| `update(string $id, array $payload)` | `PUT /tags/{id}`    | Update the name or properties of a specific tag.           |
| `delete(string $id)`                 | `DELETE /tags/{id}` | Delete a tag permanently by its ID.                        |
| `createMany(array $tags)`            | `POST /tags`        | Create multiple tags. Returns results with success/error.  |
| `deleteMany(array $ids)`             | `DELETE /tags/{id}` | Delete multiple tags. Returns results with success/error.  |

**Examples:**

```php
// Create tags
$tag = N8nClient::tags()->create(['name' => 'Marketing']);

// Batch create
$results = N8nClient::tags()->createMany([
    ['name' => 'Production'],
    ['name' => 'Development'],
    ['name' => 'Testing'],
]);

// Get all tags
$allTags = N8nClient::tags()->all();

// Update and delete
N8nClient::tags()->update($tag['id'], ['name' => 'Sales']);
N8nClient::tags()->deleteMany(['tag1', 'tag2']);
```

### 🙍 Users

| Method Signature                                     | HTTP Method & Path              | Description                                                                      |
|------------------------------------------------------|---------------------------------|----------------------------------------------------------------------------------|
| `list(array $filters = [])`                          | `GET /users`                    | List users with optional filters: `limit`, `cursor`, `includeRole`, `projectId`. |
| `all(array $filters = [])`                           | `GET /users`                    | Auto-paginate and retrieve all users.                                            |
| `listIterator(array $filters = [])`                  | `GET /users`                    | Memory-efficient generator for iterating through all users.                      |
| `create(array $userPayloads)`                        | `POST /users`                   | Create (invite) one or more users by providing user objects.                     |
| `get(string $idOrEmail, bool $includeRole = false)`  | `GET /users/{idOrEmail}`        | Get a user by ID or email. Optionally include role.                              |
| `delete(string $idOrEmail)`                          | `DELETE /users/{idOrEmail}`     | Delete a user by ID or email.                                                    |
| `changeRole(string $idOrEmail, string $newRoleName)` | `PATCH /users/{idOrEmail}/role` | Change the user's role to the new role name.                                     |

**Examples:**

```php
// Invite users
N8nClient::users()->create([
  ['email' => 'dev@example.com', 'role' => 'member']
]);

// Get all users
$allUsers = N8nClient::users()->all();

// Get users with roles included
foreach (N8nClient::users()->listIterator(['includeRole' => true]) as $user) {
    // Process each user
}

// Promote to admin
N8nClient::users()->changeRole('dev@example.com', 'admin');
```

### 🔠 Variables

| Method Signature                     | HTTP Method & Path       | Description                                                     |
|--------------------------------------|--------------------------|-----------------------------------------------------------------|
| `create(array $payload)`             | `POST /variables`        | Create a new variable with a key-value pair.                    |
| `list(array $filters = [])`          | `GET /variables`         | List variables with optional pagination using limit and cursor. |
| `all(array $filters = [])`           | `GET /variables`         | Auto-paginate and retrieve all variables.                       |
| `listIterator(array $filters = [])`  | `GET /variables`         | Memory-efficient generator for iterating through all variables. |
| `update(string $id, array $payload)` | `PUT /variables/{id}`    | Update the value of an existing variable.                       |
| `delete(string $id)`                 | `DELETE /variables/{id}` | Permanently delete a variable.                                  |
| `createMany(array $variables)`       | `POST /variables`        | Create multiple variables. Returns results with success/error.  |
| `deleteMany(array $ids)`             | `DELETE /variables/{id}` | Delete multiple variables. Returns results with success/error.  |

**Examples:**

```php
// Create a new variable
N8nClient::variables()->create(['key' => 'ENV_MODE', 'value' => 'production']);

// Batch create
$results = N8nClient::variables()->createMany([
    ['key' => 'API_URL', 'value' => 'https://api.example.com'],
    ['key' => 'DEBUG_MODE', 'value' => 'false'],
    ['key' => 'MAX_RETRIES', 'value' => '3'],
]);

// Get all variables
$allVariables = N8nClient::variables()->all();

// Update a variable
N8nClient::variables()->update('ENV_MODE', ['value' => 'staging']);

// Batch delete
N8nClient::variables()->deleteMany(['var1', 'var2', 'var3']);
```

### 🔄 Workflows

| Method Signature                                     | HTTP Method & Path                | Description                                                               |
|------------------------------------------------------|-----------------------------------|---------------------------------------------------------------------------|
| `create(array $payload)`                             | `POST /workflows`                 | Create a new workflow using a flow definition.                            |
| `list(array $filters = [])`                          | `GET /workflows`                  | List workflows with optional filters: `active`, `tags`, `projectId`, etc. |
| `all(array $filters = [])`                           | `GET /workflows`                  | Auto-paginate and retrieve all workflows matching filters.                |
| `listIterator(array $filters = [])`                  | `GET /workflows`                  | Memory-efficient generator for iterating through all workflows.           |
| `get(string $id, bool $excludePinnedData = false)`   | `GET /workflows/{id}`             | Retrieve a specific workflow; optionally exclude pinned node data.        |
| `update(string $id, array $payload)`                 | `PUT /workflows/{id}`             | Update the workflow definition.                                           |
| `delete(string $id)`                                 | `DELETE /workflows/{id}`          | Delete the specified workflow.                                            |
| `activate(string $id)`                               | `POST /workflows/{id}/activate`   | Activate the workflow.                                                    |
| `deactivate(string $id)`                             | `POST /workflows/{id}/deactivate` | Deactivate the workflow.                                                  |
| `activateMany(array $ids)`                           | `POST /workflows/{id}/activate`   | Activate multiple workflows. Returns results with success/error per ID.   |
| `deactivateMany(array $ids)`                         | `POST /workflows/{id}/deactivate` | Deactivate multiple workflows. Returns results with success/error per ID. |
| `deleteMany(array $ids)`                             | `DELETE /workflows/{id}`          | Delete multiple workflows. Returns results with success/error per ID.     |
| `export(string $id)`                                 | `GET /workflows/{id}`             | Export workflow definition (for backup/migration).                        |
| `import(array $workflow)`                            | `POST /workflows`                 | Import a workflow definition.                                             |
| `transfer(string $id, string $destinationProjectId)` | `PUT /workflows/{id}/transfer`    | Move a workflow to a different project.                                   |
| `tags(string $id)`                                   | `GET /workflows/{id}/tags`        | Get all tags associated with the workflow.                                |
| `updateTags(string $id, array $tagIds)`              | `PUT /workflows/{id}/tags`        | Update the list of tag IDs for a workflow.                                |

**Examples:**

```php
// Create and activate a workflow
$workflow = N8nClient::workflows()->create([
    'name' => 'My Workflow',
    'nodes' => [...],
    'connections' => [...],
]);
N8nClient::workflows()->activate($workflow['id']);

// Get all active workflows
$activeWorkflows = N8nClient::workflows()->all(['active' => true]);

// Batch activate
$results = N8nClient::workflows()->activateMany(['wf1', 'wf2', 'wf3']);

// Export and import
$exported = N8nClient::workflows()->export('wf1');
$imported = N8nClient::workflows()->import($exported);
```

## 🔧 Advanced Features

### 📊 Events System

Every mutating request (create/update/delete/transfer/role-change, etc.) dispatches a strongly-typed Laravel event so you can observe n8n activity in real time. Cached reads still trigger the `ApiRequestCompleted` event, so metrics and listeners remain consistent even when responses are served from cache.

**Available events**

| Resource    | Events Fired                                                                                 |
|-------------|-----------------------------------------------------------------------------------------------|
| API Client  | `ApiRequestCompleted`, `RateLimitEncountered`                                                 |
| Workflows   | `WorkflowCreated`, `WorkflowUpdated`, `WorkflowDeleted`, `WorkflowActivated`, `WorkflowDeactivated`, `WorkflowTransferred`, `WorkflowTagsUpdated` |
| Executions  | `ExecutionCompleted`, `ExecutionFailed`, `ExecutionDeleted`                                   |
| Credentials | `CredentialCreated`, `CredentialDeleted`, `CredentialTransferred`                             |
| Projects    | `ProjectCreated`, `ProjectUpdated`, `ProjectDeleted`, `ProjectUsersAdded`, `ProjectUserRoleChanged`, `ProjectUserRemoved` |
| Users       | `UserCreated`, `UserDeleted`, `UserRoleChanged`                                               |
| Variables   | `VariableCreated`, `VariableUpdated`, `VariableDeleted`                                       |
| Tags        | `TagCreated`, `TagUpdated`, `TagDeleted`                                                      |
| Webhooks    | `WebhookTriggered` (sync and queued)                                                          |

Register listeners just like any other Laravel event:

```php
// In EventServiceProvider
use KayedSpace\N8n\Events\WorkflowCreated;
use KayedSpace\N8n\Events\WebhookTriggered;

protected $listen = [
    WorkflowCreated::class => [LogWorkflowActivity::class],
    WebhookTriggered::class => [TrackWebhookUsage::class],
];
```

Events can be enabled or disabled in config:

```php
// config/n8n.php or .env
N8N_EVENTS_ENABLED=true
```

### 💾 Response Caching

Improve performance by caching API responses:

```php
// Enable in .env
N8N_CACHE_ENABLED=true
N8N_CACHE_TTL=300  // seconds

// Use cached responses
$workflows = N8nClient::workflows()->cached()->list();

// Force fresh data (bypass cache)
$workflow = N8nClient::workflows()->fresh()->get($id);
```

Cache is automatically invalidated on create/update/delete operations.

### 📈 Metrics

Enable lightweight request metrics that are incremented during every API call (including cache hits when caching is enabled):

```php
// config/n8n.php or .env
N8N_METRICS_ENABLED=true
N8N_METRICS_STORE=redis   # Any Laravel cache store
```

Metrics are grouped per hour and include total requests, counts per HTTP method, status code, and average duration. Consume them from your chosen cache store to drive dashboards or alerts.

### 📝 Logging

Comprehensive request/response logging for debugging and monitoring:

```php
// Enable in .env
N8N_LOGGING_ENABLED=true
N8N_LOGGING_CHANNEL=stack        // Laravel logging channel
N8N_LOGGING_LEVEL=debug          // Logging level
N8N_LOG_REQUEST_BODY=true        // Log request payloads
N8N_LOG_RESPONSE_BODY=true       // Log response data
```

Logs include:
- HTTP method and URI
- Status codes
- Request/response duration
- Request and response bodies (if enabled)
- Error details

### 🔌 Client Modifiers

Customize HTTP client behavior by adding modifiers to the request pipeline:

```php
// Add custom headers
$workflows = N8nClient::workflows()
    ->withClientModifier(function ($client) {
        return $client->withHeaders([
            'X-Custom-Header' => 'value',
            'X-Request-ID' => uniqid(),
        ]);
    })
    ->list();

// Add authentication token
$workflows = N8nClient::workflows()
    ->withClientModifier(function ($client) {
        return $client->withToken('bearer-token');
    })
    ->get($id);

// Add timeout for specific request
$execution = N8nClient::executions()
    ->withClientModifier(function ($client) {
        return $client->timeout(300); // 5 minutes
    })
    ->wait($executionId, timeout: 300);

// Chain multiple modifiers
$response = N8nClient::workflows()
    ->withClientModifier(function ($client) {
        return $client->withHeaders(['X-Tenant-ID' => tenant()->id]);
    })
    ->withClientModifier(function ($client) {
        return $client->retry(5, 1000);
    })
    ->list();

// Add request interceptor for debugging
$workflows = N8nClient::workflows()
    ->withClientModifier(function ($client) {
        return $client->beforeSending(function ($request, $options) {
            logger()->debug('N8N Request', [
                'url' => $request->url(),
                'method' => $request->method(),
            ]);
        });
    })
    ->list();
```

Client modifiers receive the `PendingRequest` instance and must return a modified `PendingRequest`.

### 🔧 Macros

Extend API resource classes with custom methods using Laravel's Macroable trait:

```php
use KayedSpace\N8n\Client\Api\Workflows;
use KayedSpace\N8n\Facades\N8nClient;

// Register a macro in a service provider
Workflows::macro('findByName', function (string $name) {
    $workflows = $this->list(['name' => $name]);
    return $workflows->firstWhere('name', $name);
});

Workflows::macro('activateAll', function (array $filters = []) {
    $workflows = $this->all($filters);
    $results = [];
    foreach ($workflows as $workflow) {
        $results[] = $this->activate($workflow['id']);
    }
    return $results;
});

// Use the macro
$workflow = N8nClient::workflows()->findByName('My Workflow');
N8nClient::workflows()->activateAll(['tags' => ['production']]);
```

All API resource classes support macros.

### ⚠️ Exception Handling

The package provides domain-specific exceptions with detailed context:

```php
use KayedSpace\N8n\Exceptions\{
    N8nException,
    WorkflowNotFoundException,
    ExecutionFailedException,
    RateLimitException,
    AuthenticationException,
    ValidationException
};

try {
    $workflow = N8nClient::workflows()->get('invalid-id');
} catch (WorkflowNotFoundException $e) {
    // Handle 404 workflow error
    $statusCode = $e->getCode(); // 404
    $response = $e->getResponse(); // Full response object
    $context = $e->getContext(); // Additional context
}

try {
    $execution = N8nClient::executions()->wait($id, timeout: 30);
} catch (ExecutionFailedException $e) {
    // Handle failed/crashed execution
    logger()->error('Execution failed', [
        'id' => $id,
        'message' => $e->getMessage(),
    ]);
}

try {
    $workflows = N8nClient::workflows()->list();
} catch (RateLimitException $e) {
    // Handle rate limiting
    $retryAfter = $e->getRetryAfter(); // Seconds to wait
    sleep($retryAfter);
}

try {
    $users = N8nClient::users()->list();
} catch (AuthenticationException $e) {
    // Handle 401/403 errors
    logger()->alert('N8N authentication failed');
}
```

Exception hierarchy:
- `N8nException` - Base exception (all others extend this)
- `WorkflowNotFoundException` - Workflow not found (404)
- `ExecutionNotFoundException` - Execution not found (404)
- `ExecutionFailedException` - Execution failed or crashed
- `CredentialException` - Credential-related errors
- `RateLimitException` - Rate limiting (429)
- `AuthenticationException` - Auth errors (401/403)
- `ValidationException` - Validation errors (422)

All exceptions provide:
- `getCode()` - HTTP status code
- `getResponse()` - Full HTTP response object
- `getContext()` - Additional context array

## 🏥 Health Checks

Monitor your n8n instance connectivity and health:

```php
use KayedSpace\N8n\Support\HealthCheck;

$health = HealthCheck::run();

if ($health->isHealthy()) {
    echo "All systems operational";
}

// Get detailed results
$results = $health->toArray();
/*
[
    'overall_status' => 'healthy',
    'checks' => [
        'connectivity' => ['status' => 'pass', 'duration_ms' => 45],
        'api_response' => ['status' => 'pass', 'duration_ms' => 120],
        'workflows' => ['status' => 'pass', 'count' => 15],
        'metrics' => ['workflows' => 15, 'active_workflows' => 8]
    ]
]
*/
```

CLI command also available:

```bash
php artisan n8n:health
```

## 🧪 Testing

Comprehensive testing utilities for your n8n integrations:

### N8nFake

Mock n8n API responses in your tests:

```php
use KayedSpace\N8n\Testing\N8nFake;

// Setup fake
N8nFake::fake();

// Queue custom responses
N8nFake::workflows([
    'data' => [
        ['id' => 'wf1', 'name' => 'Test Workflow', 'active' => true]
    ]
]);

// Your application code
$workflows = N8nClient::workflows()->list();

// Assertions
N8nFake::assertWorkflowCreated();
N8nFake::assertWorkflowActivated('wf1');
N8nFake::assertWebhookTriggered('/my-webhook');
N8nFake::assertSentCount(3);

// Custom assertions
N8nFake::assertSent(function ($request) {
    return $request['method'] === 'POST'
        && str_contains($request['url'], '/workflows');
});

N8nFake::assertNotSent(function ($request) {
    return $request['method'] === 'DELETE';
});
```

### Test Data Factories

Generate realistic test data:

```php
use KayedSpace\N8n\Testing\Factories\{WorkflowFactory, ExecutionFactory, CredentialFactory};

// Workflow factory
$workflow = WorkflowFactory::make()
    ->active()
    ->withName('Test Workflow')
    ->withTags(['tag1', 'tag2'])
    ->build();

// Execution factory
$execution = ExecutionFactory::make()
    ->success()
    ->withWorkflow('wf1')
    ->withData(['result' => 'success'])
    ->build();

$failedExecution = ExecutionFactory::make()
    ->failed()
    ->build();

$runningExecution = ExecutionFactory::make()
    ->running()
    ->build();

// Credential factory
$credential = CredentialFactory::make()
    ->withType('slackApi')
    ->withName('My Slack')
    ->withData(['token' => 'xoxb-123'])
    ->build();
```

## 🎨 CLI Commands

Manage n8n directly from your terminal:

```bash
# Health check
php artisan n8n:health

# List workflows
php artisan n8n:workflows:list
php artisan n8n:workflows:list --limit=50
php artisan n8n:workflows:list --active=true

# Activate/deactivate workflows
php artisan n8n:workflows:activate {workflow-id}
php artisan n8n:workflows:deactivate {workflow-id}

# Check execution status
php artisan n8n:executions:status {execution-id}

# Test webhook
php artisan n8n:test-webhook {path}
php artisan n8n:test-webhook /my-webhook --data='{"key":"value"}'
```

## 🤝 Contributing

Contributions are welcome! If you have a feature request, bug report, or improvement:

1. **Fork the repository**

2. **Create a topic branch:**
   Choose the prefix that matches the purpose of your work:
   * `feature/your-description` – new functionality
   * `bugfix/your-description` – fix for an existing issue
   * `hotfix/your-description` – urgent production fix

3. **Run Laravel Pint to ensure code style is consistent**`composer pint`
4. **Add or update tests and make sure they pass** `composer test`
5. **Commit your changes**`git commit -am "Add: my awesome addition"`
6. **Push to your branch** `git push origin feature/my-awesome-addition`
7. **Open a pull request**

Please adhere to laravel pint and include tests where applicable.

## 🛠 Support

If you encounter any issues or have questions:

* Open an issue in the GitHub repository
* Use Discussions for non-bug topics or feature proposals
* Pull requests are always welcome for fixes and improvements

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
