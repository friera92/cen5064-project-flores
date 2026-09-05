<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'owner_id',
        'availability_status',
        'condition',
        'picture'
    ];

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
