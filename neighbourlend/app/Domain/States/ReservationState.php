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
}
