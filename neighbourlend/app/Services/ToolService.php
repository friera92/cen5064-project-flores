<?php

namespace App\Services;

use App\Domain\Models\Tool;
use App\Domain\States\ToolStatus;
use App\Domain\States\ToolCondition;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use App\Domain\States\ReservationState;
use Illuminate\Support\Facades\DB;
use Exception;

class ToolService
{
    public function __construct(
        private ToolRepositoryInterface $toolRepository
    ) {}

    public function createTool(array $data): Tool
    {
        if (!isset($data['owner_id'])) {
            throw new Exception('Owner ID is required to register a tool.');
        }

        // Set default availability if not provided
        $data['availability_status'] = $data['availability_status'] ?? ToolStatus::AVAILABLE;

        return $this->toolRepository->create($data);
    }

    public function updateTool(Tool $tool, array $data): Tool
    {
        $tool->fill($data);
        $this->toolRepository->save($tool);
        return $tool;
    }

    public function deleteTool(Tool $tool): bool
    {
        return DB::transaction(function () use ($tool) {
            if ($this->toolRepository->findById($tool->id) === null) {
                throw new Exception("Tool not found");
            }

            $hasActiveReservations = $tool->reservations()
                ->whereIn('status', [
                    ReservationState::REQUESTED->value,
                    ReservationState::APPROVED->value,
                    ReservationState::ACTIVE->value,
                ])
                ->exists();

            if ($hasActiveReservations) {
                throw new Exception('Cannot delete tool while you have active or pending reservations.');
            }

            $deleted = $this->toolRepository->delete($tool->id);

            if (!$deleted) {
                throw new Exception('Failed to delete the tool.');
            }
        });
    }

    public function getToolById(int $id): ?Tool
    {
        return $this->toolRepository->findById($id);
    }

    public function getAllTools(): array
    {
        return $this->toolRepository->findAll();
    }

    public function updateToolAvailabilityStatus(Tool $tool, string $status): void
    {
        if (!in_array($status, ToolStatus::cases())) {
            throw new Exception("Invalid tool status: $status");
        }

        if ($this->toolRepository->findById($tool->id) === null) {
            throw new Exception("Tool not found with ID: {$tool->id}");
        }

        $tool->availability_status = $status;
        $this->toolRepository->save($tool);
    }

    public function updateToolCondition(Tool $tool, string $condition): void
    {
        if (!in_array($condition, ToolCondition::cases())) {
            throw new Exception("Invalid tool condition: $condition");
        }

        if ($this->toolRepository->findById($tool->id) === null) {
            throw new Exception("Tool not found with ID: {$tool->id}");
        }

        $tool->condition = $condition;
        $this->toolRepository->save($tool);
    }
}
