<?php

namespace Modules\User\Services;

use App\Base\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\User\Repositories\UserRepository;

class UserService extends BaseService
{
    public function __construct(
        protected UserRepository $repository,
    ) {}

    public function create(array $data): Model
    {
        return $this->transactional(function () use ($data) {
            $data['password'] = Hash::make($data['password']);
            $user = $this->repository->create($data);

            $this->logActivity('User Created', ['email' => $user->email, 'created_by' => Auth::id()]);

            return $user;
        }, 'Failed to create user');
    }

    public function update(Model|int|string $model, array $data): Model
    {
        return $this->transactional(function () use ($model, $data) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = $this->repository->update($model, $data);

            $this->logActivity('User Updated', ['user_id' => $user->id, 'updated_by' => Auth::id()]);

            return $user;
        }, 'Failed to update user');
    }

    public function delete(Model|int|string $model): bool
    {
        return $this->transactional(function () use ($model) {
            $userId = ($model instanceof Model) ? $model->id : $model;
            $deleted = $this->repository->delete($model);

            if ($deleted) {
                $this->logActivity('User Deleted', ['user_id' => $userId, 'deleted_by' => Auth::id()]);
            }

            return $deleted;
        }, 'Failed to delete user');
    }
}
