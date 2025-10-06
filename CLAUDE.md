# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel N8N is a Laravel package that provides a fluent client for the n8n public REST API and webhook triggering. It enables Laravel applications to interact with n8n workflows, executions, credentials, users, projects, and other n8n resources.

- **Namespace**: `KayedSpace\N8n`
- **PHP Version**: >=8.2
- **Laravel Version**: >=10

## Project Structure

```
src/
├── Client/
│   ├── Api/               # API resource classes (Workflows, Executions, etc.)
│   │   └── AbstractApi.php  # Base class with logging, caching, events, metrics
│   ├── Webhook/
│   │   └── Webhooks.php   # Webhook triggering with async queue support
│   └── N8nClient.php      # Main client class
├── Concerns/
│   └── HasPagination.php  # Trait for auto-pagination (all(), listIterator())
├── Console/               # Artisan commands for n8n management
│   ├── HealthCheckCommand.php
│   ├── ListWorkflowsCommand.php
│   ├── ActivateWorkflowCommand.php
│   ├── DeactivateWorkflowCommand.php
│   ├── ExecutionStatusCommand.php
│   └── TestWebhookCommand.php
├── Events/                # Laravel events for observability
│   ├── N8nEvent.php       # Base event class
│   ├── WorkflowCreated.php, WorkflowUpdated.php, etc.
│   ├── ExecutionCompleted.php, ExecutionFailed.php
│   ├── WebhookTriggered.php
│   ├── ApiRequestCompleted.php
│   └── RateLimitEncountered.php
├── Exceptions/            # Domain-specific exceptions
│   ├── N8nException.php   # Base exception with response context
│   ├── WorkflowNotFoundException.php
│   ├── ExecutionFailedException.php
│   ├── RateLimitException.php
│   └── AuthenticationException.php
├── Http/
│   └── Middleware/
│       └── VerifyN8nWebhook.php  # Webhook signature verification middleware
├── Jobs/
│   └── TriggerN8nWebhook.php     # Queue job for async webhooks
├── Facades/
│   └── N8nClient.php      # Laravel facade
├── Enums/
│   └── RequestMethod.php  # HTTP method enum
└── N8nServiceProvider.php # Service provider

config/
└── n8n.php               # Comprehensive configuration file

tests/
├── Unit/                 # Unit tests organized by class
└── Architecture/         # Architecture tests
```

## Architecture

### Client Design

The package follows a resource-based architecture with extensive features:

1. **N8nClient**: Main entry point that instantiates resource classes
2. **AbstractApi**: Enhanced base class for API resources that handles:
   - Authentication via `X-N8N-API-KEY` header
   - Request preparation (query parameter cleaning, boolean conversion)
   - **Logging**: PSR-3 compatible logging with configurable channels
   - **Caching**: Response caching with automatic invalidation on mutations
   - **Events**: Dispatches Laravel events for all operations
   - **Metrics**: Tracks request counts, duration, and status codes
   - **Retry Strategy**: Exponential/linear backoff with configurable delays
   - **Rate Limiting**: Auto-wait functionality for 429 responses
   - **Middleware**: Request pipeline customization via `middleware()` method
   - **Macros**: Extensibility via Laravel's Macroable trait
   - **Debug Mode**: Verbose request/response dumping
3. **Resource Classes**: Each n8n API resource extends AbstractApi and includes:
   - **Pagination Helpers**: `all()` and `listIterator()` methods
   - **Batch Operations**: `activateMany()`, `deleteMany()`, etc.
   - **Event Dispatching**: Resource-specific events (WorkflowCreated, etc.)
4. **Webhooks**: Enhanced webhook class with:
   - Async queue support via `async()` method
   - Signature verification (HMAC SHA-256)
   - Event dispatching for webhook triggers
   - Middleware for route protection

### Response Format

**Backward Compatible**: All methods return `Collection|array` union types:
- Returns `Collection` when `n8n.return_type = 'collection'` (default)
- Returns `array` when `n8n.return_type = 'array'`
- **Collections implement `ArrayAccess`** so `$data['key']` still works!
- This means **zero breaking changes** - existing code continues to work

### HTTP Client

- Uses Laravel's HTTP client (`Illuminate\Http\Client\PendingRequest`)
- Comprehensive configuration via `config/n8n.php`:
  - Timeout, retry count, throw on errors
  - Smart retry strategies (exponential, linear, constant)
  - Rate limit auto-wait with configurable max wait time
  - Request/response logging with body inclusion control
- API resources automatically add API key authentication
- Webhooks support Basic Auth with per-request override

### Events System

All operations dispatch Laravel events for observability:
- **Workflow Events**: WorkflowCreated, WorkflowUpdated, WorkflowDeleted, WorkflowActivated, WorkflowDeactivated
- **Execution Events**: ExecutionCompleted, ExecutionFailed, ExecutionDeleted
- **Webhook Events**: WebhookTriggered
- **API Events**: ApiRequestCompleted (every request), RateLimitEncountered
- Events can be disabled via `n8n.events.enabled = false`

### Exception Hierarchy

Domain-specific exceptions with response context:
- `N8nException` - Base exception with response data and context
- `WorkflowNotFoundException` - 404 workflow errors
- `ExecutionNotFoundException` - 404 execution errors
- `ExecutionFailedException` - Failed/crashed executions
- `CredentialException` - Credential-related errors
- `RateLimitException` - 429 responses with retry-after info
- `AuthenticationException` - 401/403 errors
- `ValidationException` - 422 validation errors with error details

### Configuration

Comprehensive configuration structure:
```
n8n.api.base_url          # n8n REST API URL
n8n.api.key               # API authentication key
n8n.api.version           # API version (default: v1)
n8n.webhook.base_url      # Webhook trigger URL
n8n.webhook.username      # Basic auth username
n8n.webhook.password      # Basic auth password
n8n.webhook.signature_key # HMAC signature verification key
n8n.timeout               # Request timeout (seconds)
n8n.throw                 # Throw exceptions on errors
n8n.retry                 # Number of retry attempts
n8n.retry_strategy        # Retry configuration
n8n.rate_limiting         # Auto-wait configuration
n8n.logging               # Logging configuration
n8n.events                # Events toggle
n8n.cache                 # Caching configuration
n8n.queue                 # Async webhook queue config
n8n.debug                 # Debug mode toggle
n8n.return_type           # 'collection' or 'array'
n8n.metrics               # Metrics tracking config
```

## Common Commands

### Artisan Commands

The package provides CLI commands for n8n management:

```bash
# Check n8n instance connectivity
php artisan n8n:health

# List workflows
php artisan n8n:workflows:list --limit=20 --active=true

# Activate/deactivate workflows
php artisan n8n:workflows:activate {workflow-id}
php artisan n8n:workflows:deactivate {workflow-id}

# Check execution status
php artisan n8n:executions:status {execution-id}

# Test webhook trigger
php artisan n8n:test-webhook {path} --data='{"key":"value"}'
```

### Testing
```bash
# Run all tests
composer test
# or
vendor/bin/pest

# Run without coverage
vendor/bin/pest --no-coverage

# Run specific test file
vendor/bin/pest tests/Unit/Client/Api/WorkflowsTest.php
```

### Code Style
```bash
# Run Laravel Pint to fix code style
composer pint
# or
vendor/bin/pint
```

### Publishing Config
```bash
php artisan vendor:publish --tag=n8n-config
```

## Development Guidelines

### Adding New API Resources

1. Create a new class in `src/Client/Api/` extending `AbstractApi`
2. Use `HasPagination` trait if the resource supports pagination
3. Add the resource method to `N8nClient.php`
4. Create corresponding tests in `tests/Unit/Client/Api/`
5. Use the `request()` method from AbstractApi for all HTTP calls
6. Type hint with `RequestMethod` enum
7. Return `Collection|array` for backward compatibility

Example:
```php
class MyResource extends AbstractApi
{
    use HasPagination;  // Adds all() and listIterator() methods

    public function list(array $filters = []): Collection|array
    {
        return $this->request(RequestMethod::Get, '/my-resource', $filters);
    }

    public function deleteMany(array $ids): array
    {
        $results = [];
        foreach ($ids as $id) {
            try {
                $result = $this->delete($id);
                $results[$id] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }
}
```

### Enhanced Features Usage

#### Caching
```php
// Cache this request
$workflows = N8nClient::workflows()->cached()->list();

// Force fresh data
$workflow = N8nClient::workflows()->fresh()->get($id);
```

#### Pagination
```php
// Auto-paginate all items
$allWorkflows = N8nClient::workflows()->all(['active' => true]);

// Memory-efficient iterator
foreach (N8nClient::workflows()->listIterator() as $workflow) {
    // Process one at a time
}
```

#### Middleware
```php
N8nClient::workflows()
    ->middleware(fn($client) => $client->withHeaders(['X-Custom' => 'value']))
    ->list();
```

#### Batch Operations
```php
// Activate multiple workflows
$results = N8nClient::workflows()->activateMany(['id1', 'id2', 'id3']);

// Delete multiple executions
$results = N8nClient::executions()->deleteMany([101, 102, 103]);
```

#### Async Webhooks
```php
// Queue webhook trigger
N8nClient::webhooks()->async()->request('/my-webhook', $data);

// Synchronous (default)
N8nClient::webhooks()->sync()->request('/my-webhook', $data);
```

#### Execution Polling
```php
// Wait for execution to complete (max 60s, poll every 2s)
$execution = N8nClient::executions()->wait($executionId, timeout: 60, interval: 2);
```

### Testing Approach

- Uses Pest PHP for testing
- Tests extend `Tests\TestCase` (Orchestra Testbench)
- Mock HTTP responses using Laravel's HTTP fake
- All API methods should have corresponding tests
- Test both array and Collection response formats

### Query Parameter Handling

AbstractApi automatically handles:
- Null values are removed from query strings
- Boolean values converted to 'true'/'false' strings
- Nested arrays are recursively processed

### Error Handling

Enhanced exception handling:
- Domain-specific exceptions with response context
- `RateLimitException` includes retry-after information
- All exceptions provide `getResponse()` and `getContext()` methods
- Automatic conversion of HTTP errors to domain exceptions

## Environment Variables

### Required for API Access
```env
N8N_API_BASE_URL=https://your-n8n-instance.com/api/v1
N8N_API_KEY=your_api_key
```

### Webhooks Configuration
```env
N8N_WEBHOOK_BASE_URL=https://your-n8n-instance.com/webhook
N8N_WEBHOOK_USERNAME=username                    # Basic auth username
N8N_WEBHOOK_PASSWORD=password                    # Basic auth password
N8N_WEBHOOK_SIGNATURE_KEY=your_secret_key        # For signature verification
```

### HTTP Client Configuration
```env
N8N_TIMEOUT=120                                  # Request timeout (seconds)
N8N_THROW=true                                   # Throw exceptions on errors
N8N_RETRY=3                                      # Number of retry attempts
N8N_RETRY_STRATEGY=exponential                   # constant, linear, exponential
N8N_RETRY_MAX_DELAY=10000                       # Max retry delay (ms)
```

### Rate Limiting
```env
N8N_RATE_LIMIT_AUTO_WAIT=true                   # Auto-wait on 429 responses
N8N_RATE_LIMIT_MAX_WAIT=60                      # Max wait time (seconds)
```

### Logging
```env
N8N_LOGGING_ENABLED=false                        # Enable API request logging
N8N_LOGGING_CHANNEL=stack                        # Laravel log channel
N8N_LOGGING_LEVEL=debug                          # debug, info, error
N8N_LOG_REQUEST_BODY=true                        # Include request bodies
N8N_LOG_RESPONSE_BODY=true                       # Include response bodies
```

### Events
```env
N8N_EVENTS_ENABLED=true                          # Enable Laravel events
```

### Caching
```env
N8N_CACHE_ENABLED=false                          # Enable response caching
N8N_CACHE_STORE=default                          # Cache store to use
N8N_CACHE_TTL=300                                # Cache TTL (seconds)
N8N_CACHE_PREFIX=n8n                             # Cache key prefix
```

### Queue (Async Webhooks)
```env
N8N_QUEUE_ENABLED=false                          # Enable async webhooks
N8N_QUEUE_CONNECTION=default                     # Queue connection
N8N_QUEUE_NAME=default                           # Queue name
```

### General
```env
N8N_DEBUG=false                                  # Enable debug mode
N8N_RETURN_TYPE=collection                       # collection or array
N8N_METRICS_ENABLED=false                        # Enable metrics tracking
N8N_METRICS_STORE=default                        # Metrics cache store
```

## Advanced Features

### Event Listeners

Listen to n8n events in your Laravel application:

```php
// In EventServiceProvider
protected $listen = [
    WorkflowCreated::class => [
        LogWorkflowCreation::class,
        NotifyTeam::class,
    ],
    ExecutionFailed::class => [
        SendFailureAlert::class,
    ],
    RateLimitEncountered::class => [
        LogRateLimit::class,
    ],
];
```

### Webhook Signature Verification

Protect your webhook endpoints:

```php
// In routes/web.php or api.php
Route::post('/n8n/webhook', function (Request $request) {
    // Webhook data is verified by middleware
    $data = $request->all();
    // Process webhook...
})->middleware('n8n.webhook');

// Register middleware in Http/Kernel.php
protected $routeMiddleware = [
    'n8n.webhook' => \KayedSpace\N8n\Http\Middleware\VerifyN8nWebhook::class,
];
```

Manual verification:
```php
use KayedSpace\N8n\Client\Webhook\Webhooks;

if (Webhooks::verifySignature($request)) {
    // Valid signature
}
```

### Macros for Extensibility

Extend the client with custom methods:

```php
// In a service provider
use KayedSpace\N8n\Client\Api\Workflows;

Workflows::macro('findByName', function (string $name) {
    $workflows = $this->list(['name' => $name]);
    return $workflows[0] ?? null;
});

// Usage
$workflow = N8nClient::workflows()->findByName('My Workflow');
```

### Metrics Tracking

Track API usage metrics:

```php
// Metrics are automatically stored in cache
// Access via cache store
$totalRequests = Cache::get('n8n:metrics:'.date('Y-m-d-H').':total');
$avgDuration = Cache::get('n8n:metrics:'.date('Y-m-d-H').':duration');
```

## CI/CD

The project uses GitHub Actions for:
- **Tests**: Runs on PHP 8.2, 8.3, 8.4 with Laravel 10+
- **Linting**: Automatically runs Pint and commits fixes
- **Changelog**: Automated changelog updates

## Backward Compatibility

**All enhancements maintain 100% backward compatibility:**

1. **Return Types**: Methods return `Collection|array` union types
   - Collections implement `ArrayAccess` so `$data['key']` works
   - Set `n8n.return_type = 'array'` for pure arrays

2. **Method Signatures**: All existing methods maintain same parameters
   - New methods are additions, not replacements
   - Optional parameters have sensible defaults

3. **Configuration**: New config keys have defaults
   - Existing configs remain unchanged
   - New features opt-in via config

4. **Events**: Can be disabled via `n8n.events.enabled = false`

5. **Logging**: Disabled by default (`n8n.logging.enabled = false`)

6. **Caching**: Disabled by default (`n8n.cache.enabled = false`)

**Migration from v1.x**: No code changes required! Just update and optionally enable new features via config.