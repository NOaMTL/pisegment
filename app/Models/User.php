<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    public function createdSegmentTemplates(): HasMany
    {
        return $this->hasMany(SegmentTemplate::class, 'created_by');
    }

    public function approvedSegmentTemplates(): HasMany
    {
        return $this->hasMany(SegmentTemplate::class, 'approved_by');
    }

    public function segmentTemplateRequests(): HasMany
    {
        return $this->hasMany(SegmentTemplateRequest::class, 'requested_by');
    }

    public function generatedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'generated_by');
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function isAgencyManager(): bool
    {
        return $this->role === UserRole::AgencyManager;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }
}
