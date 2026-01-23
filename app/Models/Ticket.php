<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Ticket extends Model
{
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'league_id',
        'tournament_id',
        'subject',
        'category',
        'status',
        'priority',
        'last_message_at',
        'closed_at',
        'meta',
        'contact_method',
        'contact_value',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    /**
     * Mensajes visibles para el usuario (excluye notas internas).
     */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
