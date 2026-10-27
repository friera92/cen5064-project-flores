<?php

namespace App\Domain\Models;

use App\Domain\States\ReservationState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\States\ToolReturnedCondition;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'borrower_id',
        'tool_id',
        'start_date',
        'end_date',
        'status',
        'total_cost',
        'returned_condition'
    ];

    public function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => ReservationState::class,
            'returned_condition' => ToolReturnedCondition::class,
            'total_cost' => 'decimal:2',
        ];
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id')
            ->withTrashed();
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class, 'tool_id')
            ->withTrashed();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reservation_id');
    }
}
