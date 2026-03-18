<?php

namespace App\Models;

use App\SegmentTemplateStatus;
use Database\Factories\SegmentTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SegmentTemplate extends Model
{
    /** @use HasFactory<SegmentTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'conditions',
        'editable_parameters',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'editable_parameters' => 'array',
            'status' => SegmentTemplateStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SegmentTemplateRequest::class);
    }

    public function isActive(): bool
    {
        return $this->status === SegmentTemplateStatus::Active;
    }
}
