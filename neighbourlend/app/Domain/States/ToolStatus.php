<?php

namespace App\Domain\States;

enum ToolStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case MAINTENANCE = 'MAINTENANCE';
    case IN_USE = 'IN_USE';
    case RETIRED = 'RETIRED';
}
