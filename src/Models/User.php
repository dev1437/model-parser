<?php

namespace Dev1437\ModelParser\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Dev1437\ModelParser\Enums\ReservedRoleEnum;
use Dev1437\ModelParser\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => UserRoleEnum::class,
        'name' => AsStringable::class,
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function pirateName(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes): ?string => "Argh! {$attributes['name']}"
        );
    }

    public function reservedRole(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes): ReservedRoleEnum => ReservedRoleEnum::FRONTEND
        );
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class);
    }

    public function phone(): HasOne
    {
        return $this->hasOne(Phone::class);
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function car()
    {
        return $this->hasOne(Car::class);
    }

    public function latestOrder(): HasOneOrMany
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }
}
