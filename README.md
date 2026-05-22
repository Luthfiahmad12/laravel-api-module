# Laravel API Module Architecture

This project follows a strictly modular, clean architecture designed for high-performance and scalable APIs. It utilizes the `nwidart/laravel-modules` package with a customized, lean pattern focused on the **Controller -> Service -> Repository -> Model** flow.

---

## 1. Core Architecture Pattern

To maintain a clean separation of concerns, every module must follow this execution flow:

1.  **Controller**: Handles HTTP requests, performs basic validation (via FormRequests), and calls the Service.
2.  **Service**: Contains business logic. It orchestrates the flow and handles data manipulation. It interacts with the Repository.
3.  **Repository**: Dedicated to data access. It encapsulates Eloquent queries and keeps the Service clean.
4.  **Model**: The Eloquent representation of the data. Models are encapsulated within their respective modules.

---

## 2. Standardized API Response

All API responses must use the `App\Base\ApiResponse` class to ensure a uniform JSON structure.

### Success Response

```php
return ApiResponse::success($data, 'Message retrieved successfully.', 200);
```

### Error Response

```php
return ApiResponse::error('The given data was invalid.', 422, $errors);
```

**JSON Structure:**

```json
// Success
{
    "success": true,
    "message": "Success Message",
    "data": []
}

// Error (Validation)
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

---

## 3. API Resources (Transformers) Standardization

To maintain consistency in how entity data is returned, every resource should follow this standardized format. Even though we use standard Laravel `JsonResource`, the fields and formatting must be uniform.

### Resource Guidelines

1.  **Namespace**: `Modules\{Module}\Transformers`
2.  **Common Fields**: Always include `created_at` and `updated_at`.
3.  **Date Formatting**: Use `->format('Y-m-d H:i:s')` for all timestamp fields.

### Creating a Resource

To create a new resource for a module, use the following Artisan command:

```bash
php artisan module:make-resource MyResource MyModule
```

---

## 4. Module Structure

Modules are located in the `Modules/` directory. An API-only module should look like this:

```text
Modules/MyModule/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Models/             # Encapsulated Models
│   ├── Repositories/       # Data Access Logic
│   ├── Services/           # Business Logic
│   ├── Transformers/       # Eloquent API Resources
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── routes/
│   └── api.php             # Only API routes allowed
├── tests/
└── composer.json
```

---

## 5. Development Workflow

### Step 1: Create a Module

```bash
php artisan module:make MyModule
```

### Step 2: Create Data Access & Business Logic

```bash
# Create Repository
php artisan module:make-repository MyModule

# Create Service
php artisan module:make-service MyModule
```

### Step 3: Create Validation Request

```bash
php artisan module:make-request StoreMyRequest MyModule
```

### Step 4: Implement Business Logic (BaseService)

All services should extend `App\Base\Services\BaseService`. Use built-in helpers:

- **Transactions**: Use `$this->transactional(fn() => ...)` for multi-step operations.
- **Audit Trails**: Use `$this->logActivity('Action Name', $details)` for tracking.

---

## 6. Coding Standards

- **Models**: Must be placed inside the module (`Modules/X/app/Models`).
- **Repositories**: Every repository must extend `App\Base\Repositories\BaseRepository` and implement the `resolveModel()` method.
    ```php
    namespace Modules\MyModule\Repositories;

    use App\Base\Repositories\BaseRepository;
    use Modules\MyModule\Models\MyModel;

    class MyRepository extends BaseRepository
    {
        protected function resolveModel(): MyModel
        {
            return new MyModel();
        }
    }
    ```
    - Use `with(['relation'])` for eager loading.
    - Use `query()` for complex query builder access.
    - Keep logic focused on data retrieval.
- **Routes**: Use `api.php` only. Do not create `web.php` in modules.
- **Validation**: Always use `FormRequest` classes.
- **Format**: Run Pint before committing: `vendor/bin/pint --dirty`.

---

## 7. Contribution Guide

1.  Follow the **Lean Pattern**: Don't create unnecessary Interfaces or ServiceProviders.
2.  Keep Controllers "Thin": Only call a Service method and return a response.
3.  Error Handling: Let the `BaseService` or Global Exception Handler handle common errors. Use `ApiResponse::error` for custom failures.
4.  Documentation: For more details on the modular system, please refer to the [Laravel Modules Documentation](https://laravelmodules.com/).
