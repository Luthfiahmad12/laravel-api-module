<?php

namespace App\Base\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    public function all(): Collection;

    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    public function findBy(string $field, mixed $value): ?Model;

    public function findWhere(string $field, mixed $value): Collection;

    public function create(array $data): Model;

    public function update(Model|int|string $model, array $data): Model;

    public function delete(Model|int|string $model): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function count(): int;

    public function with(array $relations): self;

    public function query(): Builder;
}
