<?php

namespace App\Domain\States;

enum ReservationSate: string
{
    case REQUESTED = 'REQUESTED';
    case APPROVED = 'APPROVED';
    case ACTIVE = 'ACTIVE';
    case RETURNED = 'RETURNED';
    case CLOSED = 'CLOSED';
    case DISPUTED = 'DISPUTED';
}
