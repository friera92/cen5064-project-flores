<?php

namespace App\Domain\Models;

use App\Domain\States\ToolStatus;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolReturnedCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'owner_id',
        'availability_status',
        'condition',
        'returned_condition',
        'picture',
        'daily_rate'
    ];

    public function casts(): array
    {
        return [
            'availability_status' => ToolStatus::class,
            'condition' => ToolCondition::class,
            'returned_condition' => ToolReturnedCondition::class,
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'tool_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'tool_id');
    }
}
