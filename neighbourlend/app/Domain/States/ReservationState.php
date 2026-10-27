<?php

namespace App\Domain\States;

enum ReservationState: string
{
    case REQUESTED = 'REQUESTED';
    case CANCELED = 'CANCELED';
    case APPROVED = 'APPROVED';
    case ACTIVE = 'ACTIVE';
    case RETURNED = 'RETURNED';
    case CLOSED = 'CLOSED';
    case DISPUTED = 'DISPUTED';

    /**
     * Determine whether this reservation state
     * can transition to the given state.
     */
    public function canTransitionTo(self $newState): bool
    {
        $allowedTransitions = match ($this) {
            self::REQUESTED => [
                self::APPROVED,
                self::CANCELED,
            ],

            self::APPROVED => [
                self::ACTIVE,
                self::CANCELED,
            ],

            self::ACTIVE => [
                self::RETURNED,
            ],

            self::RETURNED => [
                self::CLOSED,
                self::DISPUTED,
            ],

            self::CANCELED,
            self::CLOSED,
            self::DISPUTED => [],
        };

        return in_array($newState, $allowedTransitions, true);
    }

    public function canBeInspected(): bool
    {
        return $this === self::RETURNED;
    }
}
