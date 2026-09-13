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
        return DB::transaction(function () use ($tool, $data) {
            // 1. Domain Guard: Prevent modifying tools currently with a borrower
            if ($tool->availability_status === ToolStatus::IN_USE) {
                throw new Exception('Cannot update a tool that is currently in use by a borrower.');
            }

            // 2. Prevent tampering with ownership
            unset($data['owner_id'], $data['id']);

            // 3. Update dirty attributes and persist
            $tool->fill($data);

            $saved = $this->toolRepository->save($tool);
            if (!$saved) {
                throw new Exception('Failed to save updated tool.');
            }

            return $tool;
        });
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
        return DB::transaction(function () use ($id) {
            return $this->toolRepository->findByIdWithLock($id);
        });
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
