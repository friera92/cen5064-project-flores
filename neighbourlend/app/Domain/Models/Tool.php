<?php

namespace App\Domain\Models;

use App\Domain\States\ToolStatus;
use App\Domain\States\ToolCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Tool extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'owner_id',
        'availability_status',
        'condition',
        'picture',
        'daily_rate'
    ];

    protected $appends = ['picture_url'];

    public function getPictureUrlAttribute(): ?string
    {
        if (!$this->picture) {
            return null;
        }

        // Backward compatibility with existing external URLs.
        if (filter_var($this->picture, FILTER_VALIDATE_URL)) {
            return $this->picture;
        }

        return Storage::disk('public')->url($this->picture);
    }

    public function casts(): array
    {
        return [
            'availability_status' => ToolStatus::class,
            'condition' => ToolCondition::class
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
