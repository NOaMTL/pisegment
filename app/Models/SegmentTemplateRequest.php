<?php

namespace App\Models;

use App\RequestStatus;
use Database\Factories\SegmentTemplateRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentTemplateRequest extends Model
{
    /** @use HasFactory<SegmentTemplateRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'conditions',
        'status',
        'requested_by',
        'reviewed_by',
        'segment_template_id',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'status' => RequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function segmentTemplate(): BelongsTo
    {
        return $this->belongsTo(SegmentTemplate::class);
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === RequestStatus::Approved;
    }
}
