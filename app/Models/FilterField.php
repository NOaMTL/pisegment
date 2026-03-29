<?php

namespace App\Models;

use Database\Factories\FilterFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilterField extends Model
{
    /** @use HasFactory<FilterFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'field_type',
        'sql_column',
        'group',
        'description',
        'options',
        'operators',
        'validation_rules',
        'order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'operators' => 'array',
        'validation_rules' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active fields
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order fields by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('label');
    }
}
