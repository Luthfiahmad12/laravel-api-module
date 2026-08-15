---
name: laravel-api-module-generator
description: "Apply this skill whenever creating, scaffolding, or refactoring modules, features, endpoints, controllers, services, repositories, resources, or tests in this laravel-api-module application. Ensures strict adherence to the project's Modular architecture, BaseRepository, BaseService, ApiResponse, Artisan scaffolding commands, and Pest testing patterns."
license: MIT
---

# Laravel API Module Development Skills

This skill defines the mandatory guidelines, Artisan scaffolding commands, and code patterns for building new modules, features, API endpoints, controllers, services, repositories, resources, and tests in this **laravel-api-module** application.

---

## 1. Core Architecture Pattern

All module features **MUST** follow a strict 5-layer execution flow:

```
[ HTTP Request ]
       ↓
[ Controller ]       ---> Form Request Validation & ApiResponse Formatting (No DB Query)
       ↓
[ Service Layer ]    ---> Business Logic, DB Transactions, & Activity Logging
       ↓
[ Repository Layer ] ---> Eloquent Query Abstraction (Custom Queries, Filtering, Aggregations, with())
       ↓
[ Eloquent Model ]   ---> Schema Mapping, Relationships, & PHP 8.4 Attributes
```

### Architectural Rules

1. **Thin Controllers**: Controllers must ONLY handle request validation, call a Service method, and return an `ApiResponse`. No direct DB queries or business logic allowed in controllers.
2. **Rich Services**: All business logic, DB transactions (`$this->transactional(...)`), and audit trails (`$this->logActivity(...)`) belong in the Service class extending `BaseService`.
3. **Repository Abstraction**: All database interactions (CRUD, complex filtering, eager loading `with()`, multi-table joins, search, aggregations) must be encapsulated in a Repository extending `BaseRepository`. Services must NEVER write raw DB queries.
4. **Standardized API Responses**: All responses must be wrapped using `App\Base\ApiResponse::success()` or `App\Base\ApiResponse::error()`.

### Layer Boundaries (DOs & DON'Ts)

| Layer | DO (Allowed & Required) | DON'T (Strictly Forbidden) |
| :--- | :--- | :--- |
| **Controller** | • Validate requests via FormRequest.<br>• Call Service methods.<br>• Return `ApiResponse::success()` or `error()`. | • NEVER call Repositories directly.<br>• NEVER execute direct Eloquent queries (`Model::where(...)`).<br>• NEVER contain business logic or transactions. |
| **Service** | • Implement business logic and workflows.<br>• Wrap mutations in `$this->transactional(...)`.<br>• Log audit trails via `$this->logActivity(...)`.<br>• Return Models, Collections, or primitive data. | • NEVER return `JsonResponse` or HTTP responses.<br>• NEVER read global `request()` directly (accept data via method arguments). |
| **Repository** | • Abstract Eloquent queries (`find`, `paginate`, `with`).<br>• Implement complex queries and filtering.<br>• Extend `BaseRepository` and define `resolveModel()`. | • NEVER put business rules or password hashing here.<br>• NEVER handle HTTP requests or responses. |
| **Model** | • Define schema attributes `#[Fillable]`, `#[Hidden]`.<br>• Define table relationships (`hasMany`, `belongsTo`).<br>• Define datetime serialization formats in `casts()` (e.g. `'created_at' => 'datetime:Y-m-d H:i:s'`). | • NEVER put heavy business logic or API formatting in Models. |
| **Transformer**| • Map Models into serialized JSON structures.<br>• Directly use model attributes (`$this->created_at`) without inline formatting. | • NEVER perform database queries inside `toArray()`. |

---

## 2. Mandatory Artisan Scaffolding Workflow (STRICT)

> **CRITICAL RULE FOR AI AGENTS**:
>
> - **NEVER** create new module files from scratch using manual file writing tools (`write_to_file`).
> - You **MUST ALWAYS** run the official `php artisan module:make-*` commands with `--no-interaction` via `run_command` first to generate the files and register them properly in the module lifecycle.
> - After running the Artisan commands, use `replace_file_content` to customize the generated stubs according to the layer reference below.

### Standard Scaffolding Command Sequence

When building a new module or feature, execute these commands in sequence:

```bash
# 1. Scaffold the Module (if creating a new module)
php artisan module:make {ModuleName} --no-interaction

# 2. Scaffold Database Components
php artisan module:make-model {ModelName} {ModuleName} --no-interaction
php artisan module:make-migration create_{table_name}_table {ModuleName} --no-interaction

# 3. Scaffold Repository & Service
php artisan module:make-repository {ModelName}Repository {ModuleName} --no-interaction
php artisan module:make-service {ModelName}Service {ModuleName} --no-interaction

# 4. Scaffold Validation Requests & Resource Transformer
php artisan module:make-request Store{ModelName}Request {ModuleName} --no-interaction
php artisan module:make-request Update{ModelName}Request {ModuleName} --no-interaction
php artisan module:make-resource {ModelName}Resource {ModuleName} --no-interaction

# 5. Scaffold Controller & Pest Feature Test
php artisan module:make-controller {ModelName}Controller {ModuleName} --no-interaction
php artisan module:make-test {ModelName}Test {ModuleName} --pest --no-interaction
```

> **Refactoring Phase**: After executing all generation commands, adapt the generated stubs:
>
> 1. Ensure `{ModelName}Repository` extends `App\Base\Repositories\BaseRepository`.
> 2. Ensure `{ModelName}Service` extends `App\Base\Services\BaseService` and injects `{ModelName}Repository` via constructor.
> 3. Ensure `{ModelName}Controller` returns `App\Base\ApiResponse`.
> 4. Ensure `{ModelName}Test` has comprehensive Pest test coverage.

### Extended Component Generators Reference

For non-CRUD or advanced components, ALWAYS use these dedicated Artisan commands:

| Component Type | Artisan Generator Command | Target Path |
| :--- | :--- | :--- |
| **Model Factory** | `php artisan module:make-factory {ModelName}Factory {ModuleName} --no-interaction` | `database/factories/` |
| **Database Seeder** | `php artisan module:make-seed {ModelName}Seeder {ModuleName} --no-interaction` | `database/seeders/` |
| **Queue / Job** | `php artisan module:make-job {JobName} {ModuleName} --no-interaction` | `app/Jobs/` |
| **Event** | `php artisan module:make-event {EventName} {ModuleName} --no-interaction` | `app/Events/` |
| **Event Listener** | `php artisan module:make-listener {ListenerName} {ModuleName} --no-interaction` | `app/Listeners/` |
| **Notification** | `php artisan module:make-notification {NotificationName} {ModuleName} --no-interaction` | `app/Notifications/` |
| **Mail** | `php artisan module:make-mail {MailName} {ModuleName} --no-interaction` | `app/Emails/` |
| **Validation Rule** | `php artisan module:make-rule {RuleName} {ModuleName} --no-interaction` | `app/Rules/` |
| **Policy** | `php artisan module:make-policy {PolicyName} {ModuleName} --no-interaction` | `app/Policies/` |
| **Observer** | `php artisan module:make-observer {ObserverName} {ModuleName} --no-interaction` | `app/Observers/` |
| **Middleware** | `php artisan module:make-middleware {MiddlewareName} {ModuleName} --no-interaction` | `app/Http/Middleware/` |
| **Enum** | `php artisan module:make-enum {EnumName} {ModuleName} --no-interaction` | `app/Enums/` |
| **Eloquent Scope** | `php artisan module:make-scope {ScopeName} {ModuleName} --no-interaction` | `app/Models/Scopes/` |
| **Eloquent Cast** | `php artisan module:make-cast {CastName} {ModuleName} --no-interaction` | `app/Casts/` |
| **Interface** | `php artisan module:make-interface {InterfaceName} {ModuleName} --no-interaction` | `app/Interfaces/` |
| **Single Action** | `php artisan module:make-action {ActionName} {ModuleName} --no-interaction` | `app/Actions/` |
| **Console Command**| `php artisan module:make-command {CommandName} {ModuleName} --no-interaction` | `app/Console/` |

> **Non-Workflow Components (Events, Jobs, Queues, Observers, Notifications, etc.)**
> These components are NOT part of the core Controller → Service → Repository workflow, but when needed they MUST still be scaffolded via `module:make-*` commands so they are registered and live inside the module directory (`Modules/{ModuleName}/app/`), not in the global `app/` directory.
> For implementation patterns of these components, refer to the `laravel-best-practices` skill.

---

## 3. Layer Implementation Reference

### A. Model Layer (`Modules/{Module}/app/Models/`)

- **File Path**: `Modules/{ModuleName}/app/Models/{ModelName}.php`
- **Rules**:
    - Use PHP 8.4 Attributes: `#[Fillable(['...'])]` and `#[Hidden(['...'])]`.
    - Include required traits such as `HasApiTokens` (if auth-related), `HasFactory`, and `Notifiable`.
    - Define datetime serialization formats directly in the `casts()` method (e.g. `'created_at' => 'datetime:Y-m-d H:i:s'`).
    - Define ALL Eloquent relationships as explicit methods with return type declarations.

```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\{RelatedModule}\Models\{RelatedModel};

#[Fillable(['name', 'status', 'user_id'])]
class {ModelName} extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    // BelongsTo: this model owns a foreign key (e.g. user_id)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // HasMany: related model owns the foreign key pointing back to this model
    public function items(): HasMany
    {
        return $this->hasMany({RelatedModel}::class);
    }
}
```

---

### B. Repository Layer (`Modules/{Module}/app/Repositories/`)

- **File Path**: `Modules/{ModuleName}/app/Repositories/{ModelName}Repository.php`
- **Rules**:
    - MUST extend `App\Base\Repositories\BaseRepository`.
    - MUST implement the `resolveModel(): Model` abstract method.
    - For simple CRUD modules, `resolveModel()` alone is sufficient — `BaseRepository` already provides `create`, `update`, `delete`, `find`, `findOrFail`, `paginate`, `all`, etc.
    - Add custom methods ONLY when the business requires queries that `BaseRepository` cannot serve (e.g. filtering, eager loading relations, joins, aggregations, complex conditions).
    - MUST place all custom/complex database queries (filtering, joins, eager loading `with()`, aggregations) in the Repository — never in Service or Controller.
    - Use `$this->query()` helper to build query pipelines with `when()` for optional filters.
    - ALWAYS define eager loading (`with()`) explicitly in custom methods.
    - **Custom method naming MUST be domain-specific** — NEVER use generic names that overlap with `BaseRepository` methods (`find`, `findBy`, `all`, `paginate`, etc.).

> **Naming Convention for Custom Methods:**
> Custom methods should describe the **business action**, not just the technical operation.
>
> | Avoid (too generic, mirrors base) | Use (domain-specific) |
> |:---|:---|
> | `findWithRelations($id)` | `fetchOrderWithItems($id)`, `getPromoDetail($id)` |
> | `findAll()` | `getActivePromos()`, `getPublishedProducts()` |
> | `search($filters)` | `searchProductsByCategory($id)`, `filterOrdersByStatus($status)` |
> | `getFiltered($filters)` | `getPendingOrdersByUser($userId)` |

```php
<?php

namespace Modules\{ModuleName}\Repositories;

use App\Base\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\{ModuleName}\Models\{ModelName};

class {ModelName}Repository extends BaseRepository
{
    protected function resolveModel(): {ModelName}
    {
        return new {ModelName};
    }

    /**
     * Fetch paginated {modelName}s with eager-loaded relations and optional filters.
     * Method name describes the business action — not just a generic "search".
     *
     * @param array{search?: string, status?: string} $filters
     */
    public function search{ModelName}WithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $result = $this->query()
            ->with(['user', 'items'])           // Eager load BelongsTo + HasMany
            ->when($filters['search'] ?? null, fn ($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
            )
            ->when($filters['status'] ?? null, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $this->resetQuery();

        return $result;
    }

    /**
     * Fetch a single {modelName} with all its relations for detail/show endpoints.
     * Named "fetch{ModelName}Detail" to clearly distinguish from BaseRepository::findOrFail().
     */
    public function fetch{ModelName}Detail(int $id): Model
    {
        $record = $this->query()
            ->with(['user', 'items'])
            ->findOrFail($id);

        $this->resetQuery();

        return $record;
    }
}
```

---



### C. Service Layer (`Modules/{Module}/app/Services/`)

- **File Path**: `Modules/{ModuleName}/app/Services/{ModelName}Service.php`
- **Rules**:
    - MUST extend `App\Base\Services\BaseService`.
    - MUST inject the Repository into the constructor and call `parent::__construct($repository)`.
    - MUST wrap data-mutating operations (create, update, delete) inside `$this->transactional(...)`.
    - MUST record audit trails using `$this->logActivity(...)`.
    - **NEVER write Eloquent queries inside Service** — all database access MUST delegate to the Repository, either via base or custom methods.
    - For simple CRUD modules, override only `create`, `update`, `delete` as needed to add transactions and audit logging — `BaseService` already provides `find`, `findOrFail`, `paginate`, `all`, etc.
    - Add custom methods ONLY when the business requires calling a custom Repository method (one that does not exist in `BaseRepository`).
    - **Custom method naming MUST be domain-specific** — NEVER use generic names that overlap with `BaseService` methods (`find`, `findOrFail`, `all`, `paginate`, `create`, `update`, `delete`).
    - Use `$this->repository` to call methods inherited from `BaseRepository` (CRUD, paginate, findOrFail, etc.).
    - Use `$this->{modelName}Repository` (typed property) ONLY to call custom methods defined in the concrete Repository.

> **Naming Convention for Custom Methods:**
> Custom methods should describe the **business use-case**, not the technical layer operation.
>
> | Avoid (too generic, mirrors base) | Use (domain-specific) |
> |:---|:---|
> | `findWithRelations($id)` | `fetchOrderDetail($id)`, `getPromoWithItems($id)` |
> | `getFiltered($filters)` | `searchActivePromos($filters)`, `filterProductsByCategory($filters)` |
> | `getAll()` | `getPublishedArticles()`, `getActiveUsers()` |

```php
<?php

namespace Modules\{ModuleName}\Services;

use App\Base\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\{ModuleName}\Repositories\{ModelName}Repository;

class {ModelName}Service extends BaseService
{
    public function __construct(
        protected {ModelName}Repository ${modelName}Repository,
    ) {
        parent::__construct(${modelName}Repository);
    }

    /**
     * Search {modelName}s with filters — delegates to concrete Repository method.
     * Use $this->{modelName}Repository (typed) to call concrete-only methods.
     *
     * @param array{search?: string, status?: string} $filters
     */
    public function search{ModelName}WithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->{modelName}Repository->search{ModelName}WithFilters($filters, $perPage);
    }

    /**
     * Fetch a single {modelName} with all relations for detail/show.
     * Named "fetch{ModelName}Detail" to distinguish from BaseService::findOrFail().
     */
    public function fetch{ModelName}Detail(int $id): Model
    {
        return $this->{modelName}Repository->fetch{ModelName}Detail($id);
    }

    public function create(array $data): Model
    {
        return $this->transactional(function () use ($data) {
            $record = $this->repository->create($data);
            $this->logActivity('{ModelName} Created', ['id' => $record->id, 'created_by' => Auth::id()]);

            return $record;
        }, 'Failed to create {modelName}');
    }

    public function update(Model|int|string $model, array $data): Model
    {
        return $this->transactional(function () use ($model, $data) {
            $record = $this->repository->update($model, $data);
            $this->logActivity('{ModelName} Updated', ['id' => $record->id, 'updated_by' => Auth::id()]);

            return $record;
        }, 'Failed to update {modelName}');
    }

    public function delete(Model|int|string $model): bool
    {
        return $this->transactional(function () use ($model) {
            $id = ($model instanceof Model) ? $model->id : $model;
            $deleted = $this->repository->delete($model);

            if ($deleted) {
                $this->logActivity('{ModelName} Deleted', ['id' => $id, 'deleted_by' => Auth::id()]);
            }

            return $deleted;
        }, 'Failed to delete {modelName}');
    }
}
```

---

### D. Transformers / JsonResource (`Modules/{Module}/app/Transformers/`)

- **File Path**: `Modules/{ModuleName}/app/Transformers/{ModelName}Resource.php`
- **Rules**:
    - MUST extend `Illuminate\Http\Resources\Json\JsonResource`.
    - Directly return model attributes (`$this->created_at`, `$this->updated_at`) relying on Model `casts()` for datetime formatting.
    - **NEVER** query inside `toArray()`. All data MUST already be eager-loaded via the Repository.
    - For relations, ALWAYS use `$this->whenLoaded('relation')` to prevent N+1 queries. Never access `$this->relation` directly without `whenLoaded`.

**Base template (scalar fields only):**

```php
<?php

namespace Modules\{ModuleName}\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {ModelName}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**Extended template (with relations):**

```php
<?php

namespace Modules\{ModuleName}\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\{RelatedModule}\Transformers\{RelatedModel}Resource;

class {ModelName}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,

            // BelongsTo: single related resource
            'user'       => new {RelatedModel}Resource($this->whenLoaded('user')),

            // HasMany / BelongsToMany: collection of related resources
            'items'      => {RelatedModel}Resource::collection($this->whenLoaded('items')),

            // Conditional: transform relation data inline when loaded
            'category'   => $this->whenLoaded('category', fn () => new {RelatedModel}Resource($this->category)),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

> **Note**: `whenLoaded()` safely returns `null` (not an error) if the relation was not eager-loaded.
> Always pair with `with('relation')` in the Repository query to avoid N+1 issues.

---

### E. Controller Layer (`Modules/{Module}/app/Http/Controllers/`)

- **File Path**: `Modules/{ModuleName}/app/Http/Controllers/{ModelName}Controller.php`
- **Rules**:
    - Inject the Service class into the constructor.
    - Use dedicated Form Request classes (`Store{ModelName}Request`, `Update{ModelName}Request`).
    - MUST return JSON responses wrapped via `App\Base\ApiResponse::success()` or `App\Base\ApiResponse::error()`.

```php
<?php

namespace Modules\{ModuleName}\Http\Controllers;

use App\Base\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\{ModuleName}\Http\Requests\Store{ModelName}Request;
use Modules\{ModuleName}\Http\Requests\Update{ModelName}Request;
use Modules\{ModuleName}\Services\{ModelName}Service;
use Modules\{ModuleName}\Transformers\{ModelName}Resource;

class {ModelName}Controller extends Controller
{
    public function __construct(
        protected {ModelName}Service $service,
    ) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['search', 'status']);
        $perPage = request()->integer('per_page', 15);
        $records = $this->service->search{ModelName}WithFilters($filters, $perPage);

        return ApiResponse::success({ModelName}Resource::collection($records), '{ModelName}s retrieved successfully.');
    }

    public function show(int $id): JsonResponse
    {
        // Use fetch{ModelName}Detail to ensure all relations are eager-loaded
        $record = $this->service->fetch{ModelName}Detail($id);

        return ApiResponse::success(new {ModelName}Resource($record), '{ModelName} retrieved successfully.');
    }

    public function store(Store{ModelName}Request $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return ApiResponse::success(new {ModelName}Resource($record), '{ModelName} created successfully.', 201);
    }

    public function update(Update{ModelName}Request $request, int $id): JsonResponse
    {
        $record = $this->service->update($id, $request->validated());

        return ApiResponse::success(new {ModelName}Resource($record), '{ModelName} updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::success(null, '{ModelName} deleted successfully.');
    }
}
```

---

### F. Routing (`Modules/{Module}/routes/api.php`)

- **File Path**: `Modules/{ModuleName}/routes/api.php`
- **Rules**:
    - Wrap protected endpoints with `auth:sanctum` middleware.
    - Prefer `Route::apiResource(...)` for standard CRUD controllers.

---

### G. Feature Testing (`Modules/{Module}/tests/Feature/`)

- **File Path**: `Modules/{ModuleName}/tests/Feature/{ModelName}Test.php`
- **Rules**:
    - Use Pest PHP syntax (`test()`, `it()`, `expect()`).
    - Always replace `{ModuleName}` and `{ModelName}` with the actual module and model names.
    - Use `actingAs($user, 'sanctum')` for authenticated requests.
    - Always assert `success`, `message`, `data` keys from `ApiResponse` structure.
    - Verify all HTTP status codes: 200 (index/show), 201 (store), 422 (validation), 401 (unauthenticated), 404 (not found).

```php
<?php

use Modules\{ModuleName}\Models\{ModelName};
use Modules\User\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

// Test: Guest cannot access protected endpoints
test('guest cannot access {modelName} endpoints', function () {
    getJson('/api/{endpoint}')->assertStatus(401);
});

// Test: Authenticated user can retrieve paginated list
test('can retrieve paginated list of {modelName}s', function () {
    $user = User::factory()->create();
    {ModelName}::factory()->count(3)->create();

    actingAs($user, 'sanctum')
        ->getJson('/api/{endpoint}')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'links',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
});

// Test: Authenticated user can create a {modelName}
test('can create a {modelName}', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/{endpoint}', [/* valid payload */])
        ->assertStatus(201)
        ->assertJsonPath('success', true);
});

// Test: Returns 422 on invalid payload
test('returns 422 when payload is invalid', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->postJson('/api/{endpoint}', [])
        ->assertStatus(422);
});

// Test: Returns 404 when {modelName} not found
test('returns 404 when {modelName} not found', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->getJson('/api/{endpoint}/999999')
        ->assertStatus(404);
});
```

---

## 4. Quality Control & Pre-Commit Checklist

1. **Format Code**: Run Pint code formatter: `vendor/bin/pint --dirty --format agent`
2. **Execute Tests**: Run Pest test suite: `php artisan test --compact`
3. **Check Performance**: Ensure relationship queries use eager loading (`with(...)`) to prevent N+1 issues.
