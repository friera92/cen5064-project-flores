<?php

namespace App\Domain\Models;

use App\Domain\States\ReservationSate;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

    protected $fillable = [
        'borrower_id',
        'tool_id',
        'start_date',
        'end_date',
        'status'
    ];

    public function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => ReservationSate::class
        ];
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class, 'tool_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reservation_id');
    }
}
