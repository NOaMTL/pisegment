<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserColumnPreference extends Model
{
    protected $fillable = [
        'user_id',
        'page_identifier',
        'visible_columns',
    ];

    protected $casts = [
        'visible_columns' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
