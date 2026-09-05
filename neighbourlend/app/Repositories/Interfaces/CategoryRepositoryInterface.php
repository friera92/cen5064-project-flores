<?php

namespace App\Repositories\Interfaces;

use App\Domain\Models\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;
    public function create(array $data): Category;
    public function save(Category $category): bool;
    public function delete(int $id): bool;
}
