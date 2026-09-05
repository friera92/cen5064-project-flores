<?php

namespace App\Repositories;

use App\Domain\Models\Reservation;
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
        return Reservation::where('user_id', $userId)->get()->toArray();
    }

    public function findByToolId(int $toolId): array
    {
        return Reservation::where('tool_id', $toolId)->get()->toArray();
    }
}
