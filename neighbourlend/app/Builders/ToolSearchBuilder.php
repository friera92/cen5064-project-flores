<?php

declare(strict_types=1);

namespace App\Builders;

use App\Domain\Models\Tool;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Creational Builder: assembles an Eloquent tool-catalog query step by step.
 *
 * The repository decides how to execute the completed query (sort/paginate).
 * Tool's SoftDeletes global scope remains active because we start with Tool::query().
 */
final class ToolSearchBuilder
{
    /** @var EloquentBuilder<Tool> */
    private EloquentBuilder $query;

    public function __construct()
    {
        $this->query = Tool::query()->with('category:id,name');
    }

    public function forOwner(?int $ownerId): self
    {
        if ($ownerId !== null) {
            $this->query->where('owner_id', $ownerId);
        }

        return $this;
    }

    public function matchingText(?string $text): self
    {
        if ($text !== null && trim($text) !== '') {
            $term = '%' . mb_strtolower(trim($text)) . '%';
            // PostgreSQL-compatible, case-insensitive search with bound input.
            $this->query->whereRaw('LOWER(title) LIKE ?', [$term]);
        }

        return $this;
    }

    public function inCategory(?int $categoryId): self
    {
        if ($categoryId !== null) {
            $this->query->where('category_id', $categoryId);
        }

        return $this;
    }

    public function withCondition(?string $condition): self
    {
        if ($condition !== null && $condition !== '') {
            $this->query->where('condition', $condition);
        }

        return $this;
    }

    public function withAvailability(?string $availabilityStatus): self
    {
        if ($availabilityStatus !== null && $availabilityStatus !== '') {
            $this->query->where('availability_status', $availabilityStatus);
        }

        return $this;
    }

    public function minimumRate(int|float|string|null $minimum): self
    {
        if ($minimum !== null) {
            $this->query->where('daily_rate', '>=', $minimum);
        }

        return $this;
    }

    public function maximumRate(int|float|string|null $maximum): self
    {
        if ($maximum !== null) {
            $this->query->where('daily_rate', '<=', $maximum);
        }

        return $this;
    }

    /** @return EloquentBuilder<Tool> */
    public function build(): EloquentBuilder
    {
        return $this->query;
    }
}
