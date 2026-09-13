<?php

namespace App\Domain\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Domain\Models\Reservation;
use App\Domain\Models\Review;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'name',
        'password',
        'phone',
        'address',
        'picture',
        'is_admin'
    ];

    // 2. Hidden attributes (Never sent to the Vue frontend)
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Tools this user is borrowing (The Borrower)
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'borrower_id');
    }

    // Reviews written BY this user
    public function authoredReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    // Reviews written ABOUT this user
    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
