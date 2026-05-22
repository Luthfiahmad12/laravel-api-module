<?php

namespace App\Base\Services;

use App\Base\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * @param  BaseRepository  $repository
     */
    public function __construct(
        protected $repository,
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int|string $id): ?Model
    {
        return $this->repository->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(Model|int|string $model, array $data): Model
    {
        return $this->repository->update($model, $data);
    }

    public function delete(Model|int|string $model): bool
    {
        return $this->repository->delete($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Execute a callback within a database transaction with automatic logging.
     */
    public function transactional(callable $callback, string $logMessage = 'Transaction failed'): mixed
    {
        return DB::transaction(function () use ($callback, $logMessage) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                Log::error($logMessage, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Helper to log activities (e.g., audit trails for records).
     */
    public function logActivity(string $action, mixed $details = []): void
    {
        Log::info("Activity: {$action}", [
            'user_id' => Auth::id(),
            'details' => $details,
            'ip' => request()->ip(),
        ]);
    }
}
