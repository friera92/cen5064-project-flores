<?php

namespace App\Domain\States;

enum ToolReturnedCondition: string
{
    case GOOD = 'GOOD';
    case DAMAGED = 'DAMAGED';
    case BROKEN = 'BROKEN';
    case MISSING_PARTS = 'MISSING_PARTS';
}
