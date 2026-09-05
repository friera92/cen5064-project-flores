<?php

namespace App\Repositories\Interfaces;

use App\Domain\Models\Tool;

interface ToolRepositoryInterface
{
    public function create(array $data): ?Tool;
    public function save(Tool $tool): bool;
    public function delete(int $id): bool;
    public function findById(int $id): ?Tool;
    public function findByCategoryId(int $categoryId): array;
    public function findByOwnerId(int $ownerId): array;
    public function findByName(string $name): array;
    public function findByCondition(string $condition): array;
    public function findByAvailabilityStatus(string $availabilityStatus): array;
}
