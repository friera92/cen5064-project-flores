<?php

namespace App\Repositories\Interfaces;

use App\Domain\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): ?User;
    public function save(User $user): bool;
    public function delete(int $id): bool;
}
