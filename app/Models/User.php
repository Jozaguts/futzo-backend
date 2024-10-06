<?php

namespace App\Models;

use App\Notifications\VerifyEmailWithToken;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property string $email_verification_token
 */
class User extends Authenticatable implements MustVerifyEmail, HasMedia

{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailWithToken($this->email_verification_token));
    }

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'email_verification_token',
        'password',
        'facebook_id',
        'google_id',
        'phone',
        'image',
        'email_verified_at',
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
        'email_verification_token',
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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function registerMediaCollections(?Media $media = null): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->storeConversionsOnDisk('s3')
            ->registerMediaConversions(function (Media $media = null) {
                $this->addMediaConversion('thumbnail')
                    ->width(150)
                    ->height(150);
                $this->addMediaConversion('default')
                    ->width(400)
                    ->height(400);
            });
    }
}
