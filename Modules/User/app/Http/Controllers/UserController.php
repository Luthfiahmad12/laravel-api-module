<?php

namespace Modules\User\Http\Controllers;

use App\Base\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Services\UserService;
use Modules\User\Transformers\UserResource;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {}

    public function index()
    {
        $perPage = request()->integer('per_page', 15);
        $users = $this->userService->paginate($perPage);

        return ApiResponse::success(UserResource::collection($users), 'Users retrieved successfully.');
    }

    public function show(int $id)
    {
        $user = $this->userService->findOrFail($id);

        return ApiResponse::success(new UserResource($user), 'User retrieved successfully.');
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        return ApiResponse::success(new UserResource($user), 'User created successfully.', 201);
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->userService->update($id, $request->validated());

        return ApiResponse::success(new UserResource($user), 'User updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->userService->delete($id);

        return ApiResponse::success([], 'User deleted successfully.');
    }
}
