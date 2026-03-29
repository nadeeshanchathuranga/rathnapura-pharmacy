<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'division_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user_type attribute (maps to role for backwards compatibility).
     *
     * @return int
     */
    public function getUserTypeAttribute(): int
    {
        return $this->role;
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 0;
    }

    /**
     * Check if the user is backoffice.
     */
    public function isBackoffice(): bool
    {
        return $this->role === 1;
    }

    /**
     * Check if the user is a cashier.
     */
    public function isCashier(): bool
    {
        return $this->role === 2;
    }

    /**
     * Check if user belongs to a specific division.
     */
    public function hasDivision(): bool
    {
        return !is_null($this->division_id);
    }

    /**
     * Get the division name attribute.
     */
    public function getDivisionNameAttribute(): ?string
    {
        return $this->division?->name;
    }
}
