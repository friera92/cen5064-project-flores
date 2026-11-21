<?php

namespace App\Services;

use App\Domain\States\ToolStatus;
use App\Domain\States\ReservationState;
use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;
use App\Domain\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Repositories\Interfaces\ToolRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Exception;
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
                throw new DomainException('Tool ID is required to create a reservation.');
            }

            $tool = $this->toolRepository->findByIdWithLock($data['tool_id']);
            if (!$tool) {
                throw (new ModelNotFoundException())->setModel(Tool::class, [$data['tool_id']]);
            }

            // Enforce the reservation authorization policy.
            $borrower = User::findOrFail($data['borrower_id']);

            Gate::forUser($borrower)->authorize('reserve', $tool);

            if ($tool->availability_status !== ToolStatus::AVAILABLE) {
                throw new DomainException('Tool is not available for reservation.');
            }

            // 3. Chronological Sanity Check
            if ($data['start_date'] > $data['end_date']) {
                throw new DomainException('End date must be on or after the start date');
            }

            // 4. Overlap Conflict Resolution (Anti-Double Booking)
            $hasConflict = $this->reservationRepository->hasOverlappingReservations(
                $data['tool_id'],
                $data['start_date'],
                $data['end_date']
            );

            if ($hasConflict) {
                throw new DomainException('The tool is already booked or requested for the selected dates.');
            }

            $cost = $this->calculateCost(
                (float) $tool->daily_rate,
                $data['start_date'],
                $data['end_date']
            );

            $data['total_cost'] = $cost['total_cost'];
            $data['status'] = ReservationState::REQUESTED;

            return $this->reservationRepository->create($data);
        });
    }

    public function approveReservation(int $reservationId, int $userId)
    {
        return DB::transaction(function () use ($reservationId, $userId) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
            }

            $tool = $this->toolRepository->findByIdWithLock($reservation->tool_id);
            if (!$tool) {
                throw (new ModelNotFoundException())->setModel(Tool::class, [$reservation->tool_id]);
            }

            if ($tool->owner_id !== $userId) {
                throw new AuthorizationException('Only the tool owner can approve this reservation.');
            }

            // Reload after obtaining the tool lock to prevent approval from
            // using a stale reservation state in concurrent requests.
            $reservation->refresh();

            // Verify state transition & Update the reservation status to APPROVED
            $reservation->transitionTo(ReservationState::APPROVED);

            $result = $this->reservationRepository->save($reservation);

            if (!$result) {
                throw new Exception('Failed to approve the reservation.');
            }

            return $reservation;
        });
    }

    public function cancelReservation(int $reservationId, int $userId, ?string $reason = null)
    {
        return DB::transaction(function () use ($reservationId, $userId, $reason) {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
            }

            $isBorrower = $reservation->borrower_id === $userId;
            $isLender = $reservation->tool && $reservation->tool->owner_id === $userId;

            if (!$reason && $isLender) {
                throw new InvalidArgumentException('Cancellation reason is required.');
            }

            if (!$isBorrower && !$isLender) {
                throw new AuthorizationException('You are not authorized to cancel this reservation.');
            }

            if ($reservation->status === ReservationState::APPROVED && !$isBorrower) {
                throw new AuthorizationException(
                    'Only the borrower can cancel an approved reservation.'
                );
            }

            // Verify state transition & Update the reservation status to CANCELED
            $reservation->transitionTo(ReservationState::CANCELED);

            $reservation->cancellation_reason = $reason;
            $reservation->canceled_by_user_id = $userId;
            $reservation->canceled_at = now();

            $result = $this->reservationRepository->save($reservation);

            if (!$result) {
                throw new Exception('Failed to cancel the reservation.');
            }

            return $reservation;
        });
    }

    public function getReservarionById(int $id)
    {
        $reservation = $this->reservationRepository->findById($id);
        if (!$reservation) {
            throw (new ModelNotFoundException())->setModel(Reservation::class, [$id]);
        }

        return $reservation;
    }

    public function calculateCost(float $dailyRate, string $startDate, string $endDate): array
    {
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
