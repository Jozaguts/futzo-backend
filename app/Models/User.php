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
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailWithToken($this->verification_token));
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->verified_at);
    }

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'verification_token',
        'password',
        'facebook_id',
        'google_id',
        'phone',
        'image',
        'verified_at',
        'league_id',
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
        'password' => 'hashed',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => "$this->name $this->last_name",
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
            ->storeConversionsOnDisk('s3')
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumbnail')
                    ->width(150)
                    ->height(150);
                $this->addMediaConversion('default')
                    ->width(400)
                    ->height(400);
            });
    }


}
