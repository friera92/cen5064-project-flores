<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Review extends Model
{
    protected $fillable = [
        'comment',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'reservation_id',
        'tool_id',
        'end_date',
        'visible'
    ];

    protected function casts(): array
    {
        return [
            'visible'  => 'boolean',
            'end_date' => 'datetime',
            'rating'   => 'integer',
        ];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class, 'tool_id');
    }

    /**
     * Scope a query to only include actively visible reviews.
     * Criteria:
     * 1. 'visible' flag must be true.
     * 2. 'end_date' must be either NULL (permanent) or in the future (> now).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('visible', true)
            ->where(function (Builder $q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    /**
     * Helper method to check active state in memory.
     */
    public function isActive(): bool
    {
        if (!$this->visible) {
            return false;
        }

        return is_null($this->end_date) || $this->end_date->isFuture();
    }
}
