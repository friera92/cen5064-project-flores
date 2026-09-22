<?php

namespace App\Repositories;

use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;
use App\Repositories\Interfaces\ReservationRepositoryInterface;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function findById(int $id): ?Reservation
    {
        return Reservation::find($id);
    }

    public function create(array $data): Reservation
    {
        return Reservation::create($data);
    }

    public function save(Reservation $reservation): bool
    {
        return $reservation->save();
    }

    public function findByUserId(int $userId): array
    {
        return Reservation::with('tool')
            ->where('borrower_id', $userId)
            ->get()
            ->toArray();
    }

    public function findByToolId(int $toolId): array
    {
        return Reservation::where('tool_id', $toolId)->get()->toArray();
    }

    public function findByToolIdAndStatus(int $toolId, string $status): array
    {
        return Reservation::where('tool_id', $toolId)
            ->where('status', $status)
            ->get()
            ->toArray();
    }

    public function hasOverlappingReservations(int $toolId, string $startDate, string $endDate): bool
    {
        return Reservation::where('tool_id', $toolId)
            // Only consider bookings that actively block the tool
            ->whereIn('status', ['REQUESTED', 'APPROVED', 'ACTIVE'])
            // The universal overlap condition:
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->exists();
    }
}
