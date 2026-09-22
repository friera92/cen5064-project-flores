<?php

namespace App\Services;

use App\Domain\States\ToolStatus;
use App\Domain\States\ToolReturnedCondition;
use App\Domain\States\ReservationState;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class ReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $reservationRepository,
        private ToolRepositoryInterface $toolRepository
    ) {}

    public function requestTool(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create a new reservation
            if (!isset($data['tool_id'])) {
                throw new Exception('Tool ID is required to create a reservation.');
            }

            $tool = $this->toolRepository->findByIdWithLock($data['tool_id']);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            if ($tool->availability_status !== ToolStatus::AVAILABLE) {
                throw new Exception('Tool is not available for reservation.');
            }

            // 3. Chronological Sanity Check
            if ($data['start_date'] > $data['end_date']) {
                throw new Exception('End date must be on or after the start date');
            }

            // 4. Overlap Conflict Resolution (Anti-Double Booking)
            $hasConflict = $this->reservationRepository->hasOverlappingReservations(
                $data['tool_id'],
                $data['start_date'],
                $data['end_date']
            );

            if ($hasConflict) {
                throw new Exception('The tool is already booked or requested for the selected dates.');
            }

            $cost = $this->calculateCost(
                (float) $tool->daily_rate,
                $data['start_date'],
                $data['end_date']
            );

            $data['total_cost'] = $cost['total_cost'];

            return $this->reservationRepository->create($data);
        });
    }

    public function approveReservation(int $reservationId, int $userId)
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

            if ($tool->owner_id !== $userId) {
                throw new Exception('You are not authorized to approve this reservation.');
            }

            if ($reservation->status == ReservationState::CANCELED) {
                throw new Exception('The reservation has been canceled and cannot be approved.');
            }

            // Update the reservation status to APPROVED
            $reservation->status = ReservationState::APPROVED;
            $result = $this->reservationRepository->save($reservation);

            if (!$result) {
                throw new Exception('Failed to approve the reservation.');
            }

            return $reservation;
        });
    }

    public function cancelReservation(int $reservationId, int $userId)
    {
        return DB::transaction(function () use ($reservationId, $userId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw new Exception('Reservation not found.');
            }

            $isBorrower = $reservation->borrower_id === $userId;
            $isLender = $reservation->tool && $reservation->tool->user_id === $userId;

            if (!$isBorrower && !$isLender) {
                throw new Exception('You are not authorized to cancel this reservation.');
            }

            if ($reservation->status != ReservationState::REQUESTED && $reservation->status != ReservationState::APPROVED) {
                throw new Exception('Active or past reservations cannot be canceled.');
            }

            if ($reservation->status === ReservationState::APPROVED && !$isBorrower) {
                throw new Exception('Only the borrower can cancel an approved reservation.');
            }

            // Update the reservation status to CANCELED
            $reservation->status = ReservationState::CANCELED;
            $result = $this->reservationRepository->save($reservation);

            if (!$result) {
                throw new Exception('Failed to cancel the reservation.');
            }

            return $reservation;
        });
    }

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
            if ($tool->user_id !== $userId) {
                throw new Exception('Only the tool owner can hand off the tool.');
            }

            if ($reservation->status != ReservationState::APPROVED) {
                throw new Exception('Only approved reservations can be handed off.');
            }

            // Update the reservation status to ACTIVE
            $reservation->status = ReservationState::ACTIVE;
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

            if ($reservation->status != ReservationState::ACTIVE) {
                throw new Exception('Only active reservations can be returned.');
            }

            // Update the reservation status to COMPLETED
            $reservation->status = ReservationState::RETURNED;
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

            if ($reservation->status != ReservationState::RETURNED) {
                throw new Exception('Only returned reservations can be inspected.');
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            // Authorization: Lender only
            if ($tool->user_id !== $userId) {
                throw new Exception('Only the tool owner can inspect the tool.');
            }

            $reservation->returned_condition = $returnedCondition;

            switch ($returnedCondition) {
                case 'DAMAGED':
                    $tool->availability_status = ToolStatus::MAINTENANCE;
                    $reservation->status = ReservationState::DISPUTED;
                    break;
                case 'BROKEN':
                case 'MISSING_PARTS':
                    $tool->availability_status = ToolStatus::RETIRED;
                    $reservation->status = ReservationState::DISPUTED;
                    break;
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

            if ($reservation->status != ReservationState::RETURNED) {
                throw new Exception('Reservation cannot be closed unless it is returned and free of disputes.');
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw new Exception('Tool not found.');
            }

            // Authorization: Lender only
            if ($tool->user_id !== $userId) {
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

            // Update the reservation status to COMPLETED
            $reservation->status = ReservationState::CLOSED;
            $result = $this->reservationRepository->save($reservation);

            if (!$result || !$toolSaved) {
                throw new Exception('Failed to close the reservation.');
            }

            return $reservation;
        });
    }

    public function getReservarionById(int $id)
    {
        $reservation = $this->reservationRepository->findById($id);
        if (!$reservation) {
            throw new Exception("Reservation not found");
        }

        return $reservation;
    }

    public function calculateCost(
        float $dailyRate,
        string $startDate,
        string $endDate
    ): array {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $startDate);
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $endDate);

        if (!$start || !$end || $start->gt($end)) {
            throw new InvalidArgumentException(
                'End date must be on or after the start date.'
            );
        }

        // Both the start and end dates count as rental days.
        $days = (int) $start->diffInDays($end) + 1;

        $subtotal = round($dailyRate * $days, 2);

        $feeRate = (float) config('reservations.fee_rate', 0);
        $fee = round($subtotal * $feeRate, 2);

        return [
            'days' => $days,
            'daily_rate' => round($dailyRate, 2),
            'subtotal' => $subtotal,
            'tax_rate' => $feeRate,
            'tax' => $fee,
            'total_cost' => round($subtotal + $fee, 2),
        ];
    }
}
