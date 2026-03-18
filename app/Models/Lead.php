<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'segment_template_id',
        'generated_by',
        'segment_parameters_used',
        'status',
        'notes',
        'contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'segment_parameters_used' => 'array',
            'contacted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function segmentTemplate(): BelongsTo
    {
        return $this->belongsTo(SegmentTemplate::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
