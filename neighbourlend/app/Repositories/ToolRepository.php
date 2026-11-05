<?php

namespace App\Repositories;

use App\Domain\Models\Tool;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ToolRepository implements ToolRepositoryInterface
{
    public function getAllTools()
    {
        return Tool::all();
    }

    public function getToolById($id)
    {
        return Tool::find($id);
    }

    public function create(array $data): ?Tool
    {
        return Tool::create($data);
    }

    public function save(Tool $tool): bool
    {
        return $tool->save();
    }

    public function delete(int $id): bool
    {
        $tool = Tool::find($id);
        return $tool ? (bool) $tool->delete() : false;
    }

    public function findById(int $id): ?Tool
    {
        return Tool::find($id);
    }

    public function findAll(): array
    {
        return Tool::all()->toArray();
    }

    public function findByCategoryId(int $categoryId): array
    {
        return Tool::where('category_id', $categoryId)->get()->toArray();
    }

    public function findByOwnerId(int $ownerId): array
    {
        return Tool::where('owner_id', $ownerId)->get()->toArray();
    }

    public function findByName(string $name): array
    {
        return Tool::where('title', 'like', '%' . $name . '%')->get()->toArray();
    }

    public function findByCondition(string $condition): array
    {
        return Tool::where('condition', $condition)->get()->toArray();
    }

    public function findByAvailabilityStatus(string $availabilityStatus): array
    {
        return Tool::where('availability_status', $availabilityStatus)->get()->toArray();
    }

    public function findByIdWithLock(int $id): ?Tool
    {
        return Tool::where('id', $id)->lockForUpdate()->first();
    }

    public function search(array $filters): LengthAwarePaginator
    {
        // SoftDeletes on Tool automatically excludes logically deleted tools.
        $query = Tool::query()->with('category:id,name');

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (!empty($filters['q'])) {
            $term = '%' . mb_strtolower(trim($filters['q'])) . '%';
            // Compatible with PostgreSQL; bound parameter protects from SQL injection.
            $query->whereRaw('LOWER(title) LIKE ?', [$term]);
        }

        foreach (['category_id', 'condition', 'availability_status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }

        if (isset($filters['min_rate'])) {
            $query->where('daily_rate', '>=', $filters['min_rate']);
        }
        if (isset($filters['max_rate'])) {
            $query->where('daily_rate', '<=', $filters['max_rate']);
        }

        return $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }
}
