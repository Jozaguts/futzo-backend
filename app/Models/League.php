<?php

namespace App\Models;

use App\Observers\LeagueObserver;
use Database\Factories\LeagueFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([LeagueObserver::class])]
class League extends Model
{
    use HasFactory, SoftDeletes;

    public const string STATUS_DRAFT     = 'draft';
    public const string STATUS_READY     = 'ready';
    public const string STATUS_SUSPENDED = 'suspended';
    public const string STATUS_ARCHIVED  = 'archived';

    protected $fillable = [
        'name',
        'description',
        'creation_date',
        'logo',
        'banner',
        'status',
        'location',
        'football_type_id',
        'owner_id',
        'timezone',
    ];
    protected $casts = [
        'creation_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): LeagueFactory
    {
        return LeagueFactory::new();
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'league_team');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, LeagueLocation::class)
            ->withTimestamps()
            ->with('tags:id,name');
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, LeagueField::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

	public function QRConfigurations()
	{
		return $this->hasMany(QrConfiguration::class);
	}
}
