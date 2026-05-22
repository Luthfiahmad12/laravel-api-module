<?php

namespace App\Base\Repositories;

use App\Base\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    protected Builder $query;

    public function __construct()
    {
        $this->model = $this->resolveModel();
        $this->resetQuery();
    }

    abstract protected function resolveModel(): Model;

    public function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function with(array $relations): self
    {
        $this->query->with($relations);

        return $this;
    }

    public function all(): Collection
    {
        $result = $this->query->get();
        $this->resetQuery();

        return $result;
    }

    public function find(int|string $id): ?Model
    {
        $result = $this->query->find($id);
        $this->resetQuery();

        return $result;
    }

    public function findOrFail(int|string $id): Model
    {
        $result = $this->query->findOrFail($id);
        $this->resetQuery();

        return $result;
    }

    public function findBy(string $field, mixed $value): ?Model
    {
        $result = $this->query->where($field, $value)->first();
        $this->resetQuery();

        return $result;
    }

    public function findWhere(string $field, mixed $value): Collection
    {
        $result = $this->query->where($field, $value)->get();
        $this->resetQuery();

        return $result;
    }

    public function create(array $data): Model
    {
        $result = $this->model->create($data);
        $this->resetQuery();

        return $result;
    }

    public function update(Model|int|string $model, array $data): Model
    {
        if (! ($model instanceof Model)) {
            $model = $this->findOrFail($model);
        }

        $model->update($data);
        $this->resetQuery();

        return $model->fresh();
    }

    public function delete(Model|int|string $model): bool
    {
        if (! ($model instanceof Model)) {
            $model = $this->findOrFail($model);
        }

        $this->resetQuery();

        return $model->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage);
        $this->resetQuery();

        return $result;
    }

    public function count(): int
    {
        $result = $this->query->count();
        $this->resetQuery();

        return $result;
    }
}
