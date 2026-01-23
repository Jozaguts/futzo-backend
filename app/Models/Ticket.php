<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Ticket extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'status',
        'priority',
        'last_message_at',
        'closed_at',
        'meta',
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
}
