<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'author_type',
        'author_user_id',
        'body',
        'attachments',
        'is_internal',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'bool',
        'read_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
