<?php

namespace App\Services;

use App\Domain\States\ToolStatus;
use App\Domain\States\ToolReturnedCondition;
use App\Domain\States\ReservationState;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use Illuminate\Support\Facades\DB;
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
                throw new Exception('Reservation not found.');
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            // Authorization: Lender only
            if ($tool->owner_id !== $userId) {
                throw new Exception('Only the tool owner can hand off the tool.');
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
                throw new Exception('Reservation not found.');
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            // Authorization: Borrower only
            if ($reservation->borrower_id !== $userId) {
                throw new Exception('Only the borrower can return the tool.');
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
                throw new Exception('Reservation not found.');
            }

            if (!$reservation->status->canBeInspected()) {
                throw new DomainException(
                    'Only returned reservations can be inspected.'
                );
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            // Authorization: Lender only
            if ($tool->owner_id !== $userId) {
                throw new Exception('Only the tool owner can inspect the tool.');
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
                throw new Exception('Reservation not found.');
            }

            // Verify state transition & Update the reservation status to CLOSED
            $reservation->transitionTo(ReservationState::CLOSED);

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            // Authorization: Lender only
            if ($tool->owner_id !== $userId) {
                throw new Exception('Only the tool owner can close the reservation.');
            }

            if ($tool->availability_status === ToolStatus::INSPECTION && $reservation->returned_condition !== ToolReturnedCondition::GOOD) {
                throw new Exception('Tool is still under inspection or has pending issues.');
            }

            if ($reservation->returned_condition == ToolReturnedCondition::GOOD) {
                $tool->availability_status = ToolStatus::AVAILABLE;
            } else {
                throw new Exception('The tool condition is not valid for closing the reservation and might be on dispute.');
            }

            $toolSaved = $this->toolRepository->save($tool);

            $result = $this->reservationRepository->save($reservation);

            if (!$result || !$toolSaved) {
                throw new Exception('Failed to close the reservation.');
            }

            return $reservation;
        });
    }
}
