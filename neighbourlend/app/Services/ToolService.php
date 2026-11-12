<?php

namespace App\Services;

use App\Domain\Models\Tool;
use App\Domain\States\ToolStatus;
use App\Domain\States\ToolCondition;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use App\Domain\States\ReservationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use DomainException;
use RuntimeException;

class ToolService
{
    public function __construct(
        private ToolRepositoryInterface $toolRepository
    ) {}

    public function searchTools(array $filters): LengthAwarePaginator
    {
        return $this->toolRepository->search($filters);
    }
    public function getToolById(int $id): ?Tool
    {
        $tool = $this->toolRepository->findByIdWithLock($id);
        if (!$tool) {
            throw new DomainException("Tool not found");
        }
        return $tool;
    }

    public function createTool(array $data): Tool
    {
        if (!isset($data['owner_id'])) {
            throw new DomainException('Owner ID is required to register a tool.');
        }

        // Ownership and availability are controlled by backend, not browser payload.
        $data['availability_status'] = ToolStatus::AVAILABLE;
        $tool = $this->toolRepository->create($data);
        if (!$tool) {
            throw new RuntimeException('Failed to create tool.');
        }
        return $tool;
    }

    public function updateTool(Tool $tool, array $data): Tool
    {
        return DB::transaction(function () use ($tool, $data) {
            // Same lock used by booking workflow, avoiding concurrent status changes.
            $lockedTool = $this->toolRepository->findByIdWithLock($tool->id);
            if (!$lockedTool) {
                throw new DomainException('Tool not found.');
            }
            if ($lockedTool->availability_status === ToolStatus::IN_USE) {
                throw new DomainException('Cannot update a tool while it is in use.');
            }

            unset($data['id'], $data['owner_id'], $data['availability_status']);
            $lockedTool->fill($data);
            if (!$this->toolRepository->save($lockedTool)) {
                throw new RuntimeException('Failed to save updated tool.');
            }
            return $lockedTool;
        });
    }

    public function deleteTool(Tool $tool): bool
    {
        return DB::transaction(function () use ($tool) {
            $lockedTool = $this->toolRepository->findByIdWithLock($tool->id);
            if (!$lockedTool) {
                throw new DomainException('Tool not found.');
            }

            // Never remove a tool that has unfinished reservations/disputes.
            $blocked = $lockedTool->reservations()->whereIn('status', [
                ReservationState::REQUESTED->value,
                ReservationState::APPROVED->value,
                ReservationState::ACTIVE->value,
                ReservationState::RETURNED->value,
                ReservationState::DISPUTED->value,
            ])->exists();

            if ($blocked) {
                throw new DomainException(
                    'Cannot delete a tool with pending, active, returned or disputed reservations.'
                );
            }

            // With SoftDeletes enabled, repository->delete sets deleted_at.
            if (!$this->toolRepository->delete($lockedTool->id)) {
                throw new RuntimeException('Failed to delete tool.');
            }
            return true;
        });
    }

    public function getAllTools(): array
    {
        return $this->toolRepository->findAll();
    }

    public function updateToolAvailabilityStatus(Tool $tool, string $status): void
    {
        $value = ToolStatus::tryFrom($status);
        if (!$value) {
            throw new DomainException("Invalid tool status: {$status}");
        }
        if (!$this->toolRepository->findById($tool->id)) {
            throw new DomainException('Tool not found.');
        }
        $tool->availability_status = $value;
        if (!$this->toolRepository->save($tool)) {
            throw new RuntimeException('Failed to update tool availability.');
        }
    }

    public function updateToolCondition(Tool $tool, string $condition): void
    {
        $value = ToolCondition::tryFrom($condition);
        if (!$value) {
            throw new DomainException("Invalid tool condition: {$condition}");
        }
        if (!$this->toolRepository->findById($tool->id)) {
            throw new DomainException('Tool not found.');
        }
        $tool->condition = $value;
        if (!$this->toolRepository->save($tool)) {
            throw new RuntimeException('Failed to update tool condition.');
        }
    }
}
