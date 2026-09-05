<?php

namespace App\Domain\States;

enum ToolCondition: string
{
    case NEW = 'NEW';
    case LIKE_NEW = 'LIKE_NEW';
    case GOOD = 'GOOD';
    case FAIR = 'FAIR';
    case POOR = 'POOR';
}
