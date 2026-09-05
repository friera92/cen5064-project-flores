<?php

namespace App\Domain\States;

enum ToolCondition: string
{
    case CREATED = 'CREATED';
    case NEW = 'NEW';
    case GOOD = 'GOOD';
    case FAIR = 'FAIR';
    case POOR = 'POOR';
}
