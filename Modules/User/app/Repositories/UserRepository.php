<?php

namespace Modules\User\Repositories;

use App\Base\Repositories\BaseRepository;
use Modules\User\Models\User;

class UserRepository extends BaseRepository
{
    protected function resolveModel(): User
    {
        return new User;
    }
}
