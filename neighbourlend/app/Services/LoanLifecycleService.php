<?php

namespace App\Services;

use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;
use App\Domain\States\ToolStatus;
use App\Domain\States\ToolReturnedCondition;
use App\Domain\States\ReservationState;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use InvalidArgumentException;
use DomainException;

class LoanLifecycleService
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private ToolRepositoryInterface $toolRepository
    ) {}

    public function handOffTool(int $reservationId, int $userId)
    {
        return DB::transaction(function () use ($reservationId, $userId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw (new ModelNotFoundException()->setModel(Reservation::class, [$reservationId]));
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw (new ModelNotFoundException()->setModel(Tool::class, [$reservation->tool_id]));
            }

            // Authorization: Lender only
            if ($tool->owner_id !== $userId) {
                throw new AuthorizationException('Only the tool owner can hand off the tool.');
            }

            // Verify state transition & Update the reservation status to ACTIVE
            $reservation->transitionTo(ReservationState::ACTIVE);

            $result = $this->reservationRepository->save($reservation);

            $tool->availability_status = ToolStatus::IN_USE;
            $saveTool = $this->toolRepository->save($tool);

            if (!$result || !$saveTool) {
                throw new Exception('Failed to hand off the tool.');
            }

            return $reservation;
        });
    }

    public function returnTool(int $reservationId, int $userId)
    {
        return DB::transaction(function () use ($reservationId, $userId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw (new ModelNotFoundException()->setModel(Reservation::class, [$reservationId]));
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw (new ModelNotFoundException()->setModel(Tool::class, [$reservation->tool_id]));
            }

            // Authorization: Borrower only
            if ($reservation->borrower_id !== $userId) {
                throw new AuthorizationException('Only the borrower can return the tool.');
            }

            // Verify state transition & Update the reservation status to RETURNED
            $reservation->transitionTo(ReservationState::RETURNED);

            $result = $this->reservationRepository->save($reservation);

            $tool->availability_status = ToolStatus::INSPECTION; // Set the tool status to INSPECTION after return
            $saveTool = $this->toolRepository->save($tool);

            if (!$result || !$saveTool) {
                throw new Exception('Failed to return the tool.');
            }

            return $reservation;
        });
    }

    public function inspectTool(int $reservationId, string $returnedCondition, int $userId)
    {
        return DB::transaction(function () use ($reservationId, $returnedCondition, $userId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw (new ModelNotFoundException()->setModel(Reservation::class, [$reservationId]));
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw (new ModelNotFoundException()->setModel(Tool::class, [$reservation->tool_id]));
            }

            // Authorization: Lender only
            if ($tool->owner_id !== $userId) {
                throw new AuthorizationException('Only the tool owner can inspect the tool.');
            }

            if (!$reservation->status->canBeInspected()) {
                throw new DomainException(
                    'Only returned reservations can be inspected.'
                );
            }

            $reservation->returned_condition = $returnedCondition;

            switch ($returnedCondition) {
                case 'GOOD':
                    // Reservation remains RETURNED until closing.
                    break;
                case 'DAMAGED':
                    $tool->availability_status = ToolStatus::MAINTENANCE;
                    $reservation->transitionTo(ReservationState::DISPUTED);
                    break;
                case 'BROKEN':
                case 'MISSING_PARTS':
                    $tool->availability_status = ToolStatus::RETIRED;
                    $reservation->transitionTo(ReservationState::DISPUTED);
                    break;
                default:
                    throw new InvalidArgumentException(
                        'Invalid returned condition.'
                    );
            }

            $saveTool = $this->toolRepository->save($tool);
            $saveReservation = $this->reservationRepository->save($reservation);

            if (!$saveTool || !$saveReservation) {
                throw new Exception('Failed to update the tool condition after inspection.');
            }

            return $reservation;
        });
    }

    public function closeReservation(int $reservationId, int $userId)
    {
        return DB::transaction(function () use ($reservationId, $userId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw (new ModelNotFoundException()->setModel(Reservation::class, [$reservationId]));
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw (new ModelNotFoundException()->setModel(Tool::class, [$reservation->tool_id]));
            }

            // Authorization: Lender only
            if ($tool->owner_id !== $userId) {
                throw new AuthorizationException('Only the tool owner can close the reservation.');
            }

            if ($tool->availability_status === ToolStatus::INSPECTION && $reservation->returned_condition !== ToolReturnedCondition::GOOD) {
                throw new DomainException('Tool is still under inspection or has pending issues.');
            }

            if ($reservation->returned_condition == ToolReturnedCondition::GOOD) {
                $tool->availability_status = ToolStatus::AVAILABLE;
            } else {
                throw new DomainException('The tool condition is not valid for closing the reservation and might be on dispute.');
            }

            // Verify state transition & Update the reservation status to CLOSED
            $reservation->transitionTo(ReservationState::CLOSED);

            $toolSaved = $this->toolRepository->save($tool);

            $result = $this->reservationRepository->save($reservation);

            if (!$result || !$toolSaved) {
                throw new Exception('Failed to close the reservation.');
            }

            return $reservation;
        });
    }
}
