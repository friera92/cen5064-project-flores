<?php

namespace App\Repositories\Interfaces;

use App\Domain\Models\Reservation;
use App\Domain\Models\Tool;

interface ReservationRepositoryInterface
{
    public function findById(int $id): ?Reservation;
    public function create(array $data): Reservation;
    public function save(Reservation $reservation): bool;
    public function findByUserId(int $userId): array;
    public function findByToolId(int $toolId): array;
    public function findByToolIdAndStatus(int $toolId, string $status): array;
    public function hasOverlappingReservations(int $toolId, string $startDate, string $endDate): bool;
}
