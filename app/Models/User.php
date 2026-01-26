<?php

namespace App\Models;

use App\Notifications\VerifyEmailWithToken;
use App\Observers\UserObserver;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $name
 * @property string $lastname
 * @property string $email
 * @property string $password
 * @property int $facebook_id
 * @property int $google_id
 * @property string $verification_token
 * @method static create(array $userData)
 */
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    public const string PENDING_ONBOARDING_STATUS = 'pending_onboarding';
    public const string ACTIVE_STATUS = 'active';
    public const string SUSPENDED_STATUS = 'suspended';

    public const string PLAN_FREE = 'free';
    public const string PLAN_KICKOFF = 'kickoff';
    public const string PLAN_PRO_PLAY = 'pro_play';
    public const string PLAN_ELITE_LEAGUE = 'elite_league';

    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia, Billable;
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'contact_method',
        'verification_token',
        'password',
        'facebook_id',
        'google_id',
        'fbp',
        'fbc',
        'fbclid',
        'capi_consent',
        'phone',
        'image',
        'verified_at',
        'league_id',
        'stripe_customer_id',
        'status',
        'stripe_id',
        'pm_type'  ,
        'pm_last_four' ,
        'trial_ends_at',
        'plan',
        'tournaments_quota',
        'tournaments_used',
        'plan_started_at',
        'plan_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'deleted_at',
        'facebook_id',
        'google_id',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    protected $casts = [
        'verified_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'password' => 'hashed',
        'plan_started_at' => 'datetime',
        'plan_expires_at' => 'datetime',
    ];
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailWithToken($this->verification_token));
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->verified_at);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => "$this->name $this->last_name",
        );
    }
    protected function image(): Attribute
    {
        return Attribute::make(
            get:  fn ($value) => $value ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name)
        );
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function registerMediaCollections(?Media $media= null): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumbnail')
                    ->width(150)
                    ->height(150);
                $this->addMediaConversion('default')
                    ->width(400)
                    ->height(400);
            });
    }
    public function hasActiveSubscription(): bool
    {
        return $this->subscribed() && optional($this->subscription())?->valid();
    }

    public function isOperationalForBilling(): bool
    {
        if ($this->isOnFreePlan()) {
            return true;
        }

        return $this->hasActiveSubscription();
    }

    public function planDefinition(?string $plan = null): array
    {
        $plans = config('billing.plans', []);
        $planSlug = $plan ?? $this->planSlug();

        return $plans[$planSlug] ?? ['name' => ucfirst(str_replace('_', ' ', (string) $planSlug))];
    }

    public function planSlug(): string
    {
        return $this->plan ?? config('billing.default_plan', self::PLAN_FREE);
    }

    public function planLabel(): string
    {
        return (string) ($this->planDefinition()['name'] ?? ucfirst($this->planSlug()));
    }

    public function tournamentsQuota(): ?int
    {
        $quota = $this->tournaments_quota ?? ($this->planDefinition()['tournaments_quota'] ?? null);

        return is_null($quota) ? null : (int) $quota;
    }

    public function canCreateTournament(): bool
    {
        $quota = $this->tournamentsQuota();
        if (is_null($quota)) {
            return true;
        }

        return (int) $this->tournaments_used < $quota;
    }

    public function incrementTournamentUsage(int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->forceFill([
            'tournaments_used' => max(0, (int) $this->tournaments_used + $amount),
        ])->saveQuietly();
    }

    public function decrementTournamentUsage(int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->forceFill([
            'tournaments_used' => max(0, (int) $this->tournaments_used - $amount),
        ])->saveQuietly();
    }

    public function switchPlan(string $planSlug, ?int $quotaOverride = null): void
    {
        $definition = $this->planDefinition($planSlug);

        $this->forceFill([
            'plan' => $planSlug,
            'plan_started_at' => now(),
            'plan_expires_at' => null,
            'tournaments_quota' => $quotaOverride ?? ($definition['tournaments_quota'] ?? null),
        ])->save();
    }

    public function isOnFreePlan(): bool
    {
        return $this->planSlug() === self::PLAN_FREE;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
    public function openedTicketsCount() : int
    {
        return $this->tickets()->where('status', 'open')->count();
    }
}
