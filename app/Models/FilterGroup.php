<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilterGroup extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order groups by order field then label.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('label');
    }

    /**
     * Get the filter fields belonging to this group.
     */
    public function filterFields(): HasMany
    {
        return $this->hasMany(FilterField::class, 'group', 'name');
    }
}
