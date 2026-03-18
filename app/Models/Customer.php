<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'city',
        'postal_code',
        'average_balance',
        'monthly_income',
        'has_life_insurance',
        'has_home_loan',
        'has_car_loan',
        'insurance_count',
        'payment_incidents',
        'last_contact_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'average_balance' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'has_life_insurance' => 'boolean',
            'has_home_loan' => 'boolean',
            'has_car_loan' => 'boolean',
            'last_contact_at' => 'datetime',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }
}
