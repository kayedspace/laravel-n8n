# Laravel N8N: A Fluent Client for n8n Automation Workflows

A complete, expressive, and fluent Laravel client for the n8n public REST API and Webhooks Triggering, empowering Laravel
developers to interact
seamlessly with n8n webhooks, workflows, executions, credentials, tags, users, variables, projects, source control
operations and more.

## Table of Contents

* [📦 Installation](#-installation)
* [⚙️ Configuration](#-configuration)
* [🚀 Quick Start](#-quick-start)
* [⚡ Webhooks Trigger](#-webhooks-trigger)
* [📚 Full API Resource Reference](#-full-api-resource-reference)
    * [🕵 Audit](#-audit)
    * [🔑 Credentials](#-credentials)
    * [⏯️ Executions](#-executions)
    * [🚧 Projects](#-projects)
    * [📝 Source Control](#-source-control)
    * [🏷️ Tags](#-tags)
    * [🙍 Users](#-users)
    * [🔠 Variables](#-variables)
    * [🔄 Workflows](#-workflows)
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

// trigger webhook
$webhookTrigger =N8nClient::webhooks()->request("path-to-webhook",$payload);

// List all workflows
$workflows = N8nClient::workflows()->list();


// Retrieve execution status with data
$status = N8nClient::executions()->get($execution['id'], includeData: true);

```

## ⚡ Webhooks Trigger

The Webhooks class enables sending HTTP requests to n8n workflow webhook trigger URLs, supporting multiple HTTP verbs (
GET, POST, etc.) and basic authentication (if configured).

> basic auth is applied by default if `N8N_WEBHOOK_USERNAME`, `N8N8_WEBHOOK_PASSOWRD` are set in the .env file.

**Example:**

```php
//request a webhook
$webhookTrigger =N8nClient::webhooks()->request("path-to-webhook",$payload);

//request a webhook with custom basic auth credentials
//overwrites values provided on .env` file 
$webhookTrigger =N8nClient::withBasicAuth("custom-username","custom-password")->request("path-to-webhook",$payload);

//request a  webhook without  auth
$webhookTrigger =N8nClient::withoutBasicAuth()->request("path-to-webhook",$payload);

```

## 📚 Full API Resource Reference

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
| `list(int $limit = 100, ?string $cursor = null)`     | `GET /credentials`                   | List stored credentials with optional pagination.      |
| `get(string $id)`                                    | `GET /credentials/{id}`              | Retrieve details of a specific credential by ID.       |
| `delete(string $id)`                                 | `DELETE /credentials/{id}`           | Delete a credential permanently.                       |
| `schema(string $typeName)`                           | `GET /credentials/schema/{typeName}` | Get the schema definition for a credential type.       |
| `transfer(string $id, string $destinationProjectId)` | `PUT /credentials/{id}/transfer`     | Move a credential to another project using its ID.     |

**Example:**

```php
$schema = N8nClient::credentials()->schema('slackApi');

N8nClient::credentials()->create([
    'name' => 'Slack Token',
    'type' => 'slackApi',
    'data' => [
        'token' => 'xoxb-123456789',
    ]
]);
```

### ⏯️ Executions

| Method Signature                          | HTTP Method & Path        | Description                                                                                                                                       |
|-------------------------------------------|---------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `list(array $filters = [])`               | `GET /executions`         | Retrieve a paginated list of workflow executions. Supports filters such as `status`, `workflowId`, `projectId`, `includeData`, `limit`, `cursor`. |
| `get(int $id, bool $includeData = false)` | `GET /executions/{id}`    | Retrieve detailed information for a specific execution. Optionally include execution data.                                                        |
| `delete(int $id)`                         | `DELETE /executions/{id}` | Delete an execution record by ID.                                                                                                                 |

**Example:**

```php
// Get a list of executions filtered by status
$executions = N8nClient::executions()->list(['status' => 'success']);

// Get detailed execution data
$execution = N8nClient::executions()->get(101, true);

// Delete an execution
N8nClient::executions()->delete(101);
```

### 🚧 Projects

| Method Signature                                                  | HTTP Method & Path                            | Description                                                            |
|-------------------------------------------------------------------|-----------------------------------------------|------------------------------------------------------------------------|
| `create(array $payload)`                                          | `POST /projects`                              | Create a new project with name, description, etc.                      |
| `list(int $limit = 100, ?string $cursor = null)`                  | `GET /projects`                               | Retrieve a paginated list of projects.                                 |
| `update(string $projectId, array $payload)`                       | `PUT /projects/{projectId}`                   | Update project name or metadata. Returns 204 No Content on success.    |
| `delete(string $projectId)`                                       | `DELETE /projects/{projectId}`                | Delete a project by ID. Returns 204 No Content on success.             |
| `addUsers(string $projectId, array $relations)`                   | `POST /projects/{projectId}/users`            | Add users to a project with specified roles via the `relations` array. |
| `changeUserRole(string $projectId, string $userId, string $role)` | `PATCH /projects/{projectId}/users/{userId}`  | Change the role of an existing user within a project.                  |
| `removeUser(string $projectId, string $userId)`                   | `DELETE /projects/{projectId}/users/{userId}` | Remove a user from a project.                                          |

**Example Usage:**

```php
// Create a project
$project = N8nClient::projects()->create(['name' => 'DevOps', 'description' => 'CI/CD flows']);

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

| Method Signature                                 | HTTP Method & Path  | Description                                                |
|--------------------------------------------------|---------------------|------------------------------------------------------------|
| `create(array $payload)`                         | `POST /tags`        | Create a new tag with the given name or properties.        |
| `list(int $limit = 100, ?string $cursor = null)` | `GET /tags`         | List all tags with optional pagination using limit/cursor. |
| `get(string $id)`                                | `GET /tags/{id}`    | Retrieve a single tag by its ID.                           |
| `update(string $id, array $payload)`             | `PUT /tags/{id}`    | Update the name or properties of a specific tag.           |
| `delete(string $id)`                             | `DELETE /tags/{id}` | Delete a tag permanently by its ID.                        |

**Example:**

```php
// List all tags
$tags = N8nClient::tags()->list();

// Create a tag
$newTag = N8nClient::tags()->create(['name' => 'marketing']);

// Update a tag
N8nClient::tags()->update($newTag['id'], ['name' => 'marketing-2025']);

// Delete a tag
N8nClient::tags()->delete($newTag['id']);
```

### 🙍 Users

| Method Signature                                     | HTTP Method & Path              | Description                                                                      |
|------------------------------------------------------|---------------------------------|----------------------------------------------------------------------------------|
| `list(array $filters = [])`                          | `GET /users`                    | List users with optional filters: `limit`, `cursor`, `includeRole`, `projectId`. |
| `create(array $userPayloads)`                        | `POST /users`                   | Create (invite) one or more users by providing user objects.                     |
| `get(string $idOrEmail, bool $includeRole = false)`  | `GET /users/{idOrEmail}`        | Get a user by ID or email. Optionally include role.                              |
| `delete(string $idOrEmail)`                          | `DELETE /users/{idOrEmail}`     | Delete a user by ID or email.                                                    |
| `changeRole(string $idOrEmail, string $newRoleName)` | `PATCH /users/{idOrEmail}/role` | Change the user's role to the new role name.                                     |

**Example:**

```php
//list users 
N8nClient::users()->list();

// Invite users
N8nClient::users()->create([
  ['email' => 'dev@example.com', 'role' => 'member']
]);

// Promote to admin
N8nClient::users()->changeRole('dev@example.com', 'admin');

// delete a user
N8nClient::users()->delete('dev@example.com');
```

### 🔠 Variables

| Method Signature                                 | HTTP Method & Path       | Description                                                     |
|--------------------------------------------------|--------------------------|-----------------------------------------------------------------|
| `create(array $payload)`                         | `POST /variables`        | Create a new variable with a key-value pair.                    |
| `list(int $limit = 100, ?string $cursor = null)` | `GET /variables`         | List variables with optional pagination using limit and cursor. |
| `update(string $id, array $payload)`             | `PUT /variables/{id}`    | Update the value of an existing variable.                       |
| `delete(string $id)`                             | `DELETE /variables/{id}` | Permanently delete a variable.                                  |

**Example:**

```php
// list all variable
N8nClient::variables()->list()

// Create a new variable
N8nClient::variables()->create(['key' => 'ENV_MODE', 'value' => 'production']);

// Update the variable
N8nClient::variables()->update('ENV_MODE', ['value' => 'staging']);

// Delete the variable
N8nClient::variables()->delete('ENV_MODE');
```

### 🔄 Workflows

| Method Signature                                     | HTTP Method & Path                | Description                                                               |
|------------------------------------------------------|-----------------------------------|---------------------------------------------------------------------------|
| `create(array $payload)`                             | `POST /workflows`                 | Create a new workflow using a flow definition.                            |
| `list(array $filters = [])`                          | `GET /workflows`                  | List workflows with optional filters: `active`, `tags`, `projectId`, etc. |
| `get(string $id, bool $excludePinnedData = false)`   | `GET /workflows/{id}`             | Retrieve a specific workflow; optionally exclude pinned node data.        |
| `update(string $id, array $payload)`                 | `PUT /workflows/{id}`             | Update the workflow definition.                                           |
| `delete(string $id)`                                 | `DELETE /workflows/{id}`          | Delete the specified workflow.                                            |
| `activate(string $id)`                               | `POST /workflows/{id}/activate`   | Activate the workflow.                                                    |
| `deactivate(string $id)`                             | `POST /workflows/{id}/deactivate` | Deactivate the workflow.                                                  |
| `transfer(string $id, string $destinationProjectId)` | `PUT /workflows/{id}/transfer`    | Move a workflow to a different project.                                   |
| `tags(string $id)`                                   | `GET /workflows/{id}/tags`        | Get all tags associated with the workflow.                                |
| `updateTags(string $id, array $tagIds)`              | `PUT /workflows/{id}/tags`        | Update the list of tag IDs for a workflow.                                |

**Example:**

```php
// Create and  a workflow
$workflow = N8nClient::workflows()->create([
    'name'   => 'Daily Report',
    'nodes'  => [...], // full JSON definition
    'connections' => [...],
]);

//Activate the workflow
N8nClient::workflows()->activate($workflow['id']);

//Deactivate the workflow
N8nClient::workflows()->deactivate($workflow['id']);

//update the workflow.
N8nClient::workflows()->update($workflow['id'],[...]);

//Move a workflow to a different project.
N8nClient::workflows()->transfer($workflow['id'],$destinationProjectId);

//Get all tags associated with the workflow.
N8nClient::workflows()->tags($workflow['id']);

// update workflow tags.
N8nClient::workflows()->updateTags($workflow['id'],[...]);

//Delete the workflow.
N8nClient::workflows()->delete($workflow['id']);

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

