<?php

namespace Modules\Auth\Services;

use App\Base\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class AuthService extends BaseService
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {
        parent::__construct($userRepository);
    }

    public function register(array $data): User
    {
        return $this->transactional(function () use ($data) {
            $data['password'] = Hash::make($data['password']);
            $user = $this->userRepository->create($data);

            $this->logActivity('User Registered', ['email' => $user->email]);

            return $user;
        }, 'Failed to register user');
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findBy('email', $email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->logActivity('User Logged In', ['user_id' => $user->id]);

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
        $this->logActivity('User Logged Out', ['user_id' => $user->id]);
    }
}
