---
name: laravel-api-module-generator
description: "Apply this skill whenever creating, scaffolding, or refactoring modules, features, endpoints, controllers, services, repositories, resources, or tests in this laravel-api-module application. Ensures strict adherence to the project's Modular architecture, BaseRepository, BaseService, ApiResponse, Artisan scaffolding commands, and Pest testing patterns."
license: MIT
---

# Laravel API Module Development Skill

This skill defines the mandatory guidelines, Artisan scaffolding commands, and code patterns for building new modules, features, API endpoints, controllers, services, repositories, resources, and tests in this **laravel-api-module** application.

---

## 1. Core Architecture Pattern

All module features **MUST** follow a strict 5-layer execution flow:

```
[ HTTP Request ] 
       ↓
[ Controller ]  ---> Form Request Validation & ApiResponse Formatting
       ↓
[ Service Layer ] ---> Business Logic, DB Transactions, & Activity Logging
       ↓
[ Repository Layer ] ---> Eloquent Query Abstraction (BaseRepository)
       ↓
[ Eloquent Model ] ---> PHP 8.4 Attributes & Schema Mapping
```

### Architectural Rules
1. **Thin Controllers**: Controllers must ONLY handle request validation, call a Service method, and return an `ApiResponse`. No direct DB queries or business logic allowed in controllers.
2. **Rich Services**: All business logic, DB transactions (`$this->transactional(...)`), and audit trails (`$this->logActivity(...)`) belong in the Service class extending `BaseService`.
3. **Repository Abstraction**: All database interactions must be encapsulated in a Repository extending `BaseRepository`.
4. **Standardized API Responses**: All responses must be wrapped using `App\Base\ApiResponse::success()` or `App\Base\ApiResponse::error()`.

---

## 2. Recommended Artisan Scaffolding Workflow

When building a new module or feature, **ALWAYS** use the official Artisan module generator commands with `--no-interaction` first to scaffold the file structure quickly:

### Step 1: Scaffold a New Module
```bash
php artisan module:make {ModuleName} --no-interaction
```

### Step 2: Scaffold Module Components
```bash
# Generate Model and Migration
php artisan module:make-model {ModelName} {ModuleName} --no-interaction
php artisan module:make-migration create_{table_name}_table {ModuleName} --no-interaction

# Generate Repository & Service
php artisan module:make-repository {ModelName}Repository {ModuleName} --no-interaction
php artisan module:make-service {ModelName}Service {ModuleName} --no-interaction

# Generate Validation Requests & Resource Transformer
php artisan module:make-request Store{ModelName}Request {ModuleName} --no-interaction
php artisan module:make-request Update{ModelName}Request {ModuleName} --no-interaction
php artisan module:make-resource {ModelName}Resource {ModuleName} --no-interaction

# Generate Controller & Pest Test
php artisan module:make-controller {ModelName}Controller {ModuleName} --no-interaction
php artisan module:make-test {ModelName}Test {ModuleName} --pest --no-interaction
```

> **IMPORTANT**: After generating files via Artisan, you **MUST** refactor the generated stubs to extend project base classes (`BaseRepository`, `BaseService`) and format responses using `ApiResponse`.

---

## 3. Layer Implementation Reference

### A. Model Layer (`Modules/{Module}/app/Models/`)
* **File Path**: `Modules/{ModuleName}/app/Models/{ModelName}.php`
* **Rules**:
  * Use PHP 8.4 Attributes: `#[Fillable(['...'])]` and `#[Hidden(['...'])]`.
  * Include required traits such as `HasApiTokens` (if auth-related), `HasFactory`, and `Notifiable`.
  * Define explicit return type hints for the `casts()` method.

```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'status', 'user_id'])]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

---

### B. Repository Layer (`Modules/{Module}/app/Repositories/`)
* **File Path**: `Modules/{ModuleName}/app/Repositories/{ModelName}Repository.php`
* **Rules**:
  * MUST extend `App\Base\Repositories\BaseRepository`.
  * MUST implement the `resolveModel(): Model` abstract method.

```php
<?php

namespace Modules\{ModuleName}\Repositories;

use App\Base\Repositories\BaseRepository;
use Modules\{ModuleName}\Models\{ModelName};

class {ModelName}Repository extends BaseRepository
{
    protected function resolveModel(): {ModelName}
    {
        return new {ModelName};
    }
}
```

---

### C. Service Layer (`Modules/{Module}/app/Services/`)
* **File Path**: `Modules/{ModuleName}/app/Services/{ModelName}Service.php`
* **Rules**:
  * MUST extend `App\Base\Services\BaseService`.
  * MUST inject the Repository into the constructor and call `parent::__construct($repository)`.
  * MUST wrap data-mutating operations (create, update, delete) inside `$this->transactional(...)`.
  * MUST record audit trails using `$this->logActivity(...)`.

```php
<?php

namespace Modules\{ModuleName}\Services;

use App\Base\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\{ModuleName}\Repositories\{ModelName}Repository;

class {ModelName}Service extends BaseService
{
    public function __construct(
        protected {ModelName}Repository $repository,
    ) {
        parent::__construct($repository);
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

### D. Transformers / JsonResource (`Modules/{Module}/Transformers/`)
* **File Path**: `Modules/{ModuleName}/Transformers/{ModelName}Resource.php`
* **Rules**:
  * MUST extend `Illuminate\Http\Resources\Json\JsonResource`.
  * Format timestamps consistently as `Y-m-d H:i:s`.

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
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
```

---

### E. Controller Layer (`Modules/{Module}/app/Http/Controllers/`)
* **File Path**: `Modules/{ModuleName}/app/Http/Controllers/{ModelName}Controller.php`
* **Rules**:
  * Inject the Service class into the constructor.
  * Use dedicated Form Request classes (`Store{ModelName}Request`, `Update{ModelName}Request`).
  * MUST return JSON responses wrapped via `App\Base\ApiResponse::success()` or `App\Base\ApiResponse::error()`.

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
        $perPage = request()->integer('per_page', 15);
        $records = $this->service->paginate($perPage);

        return ApiResponse::success({ModelName}Resource::collection($records), '{ModelName}s retrieved successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->service->findOrFail($id);

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
* **File Path**: `Modules/{ModuleName}/routes/api.php`
* **Rules**:
  * Wrap protected endpoints with `auth:sanctum` middleware.
  * Prefer `Route::apiResource(...)` for standard CRUD controllers.

---

### G. Feature Testing (`Modules/{Module}/tests/Feature/`)
* **File Path**: `Modules/{ModuleName}/tests/Feature/{ModelName}Test.php`
* **Rules**:
  * Use Pest PHP syntax (`test()`, `it()`, `expect()`).
  * Verify HTTP status codes (200, 201, 404, 422, 401) and response JSON structure.

```php
<?php

use Modules\User\Models\User;

test('can retrieve list of items', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/{endpoint}')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});
```

---

## 4. Quality Control & Pre-Commit Checklist

1. **Format Code**: Run Pint code formatter: `vendor/bin/pint --dirty --format agent`
2. **Execute Tests**: Run Pest test suite: `php artisan test --compact`
3. **Check Performance**: Ensure relationship queries use eager loading (`with(...)`) to prevent N+1 issues.
